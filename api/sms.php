<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * SMS/Virtual Number Controller (5sim.net Integration)
 * Endpoints:
 * GET /api/sms.php?action=buy&country=X&product=Y
 * GET /api/sms.php?action=check&id=X
 * GET /api/sms.php?action=cancel&id=X
 */

$user   = verifyAuthToken();
$uid    = (int) $user['id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$pdo = Database::get();

switch ($action) {
    case 'buy':
        handleBuyNumber($uid, $pdo);
        break;
    case 'check':
        handleCheckSMS($uid, $pdo);
        break;
    case 'cancel':
        handleCancelNumber($uid, $pdo);
        break;
    default:
        jsonError('Ação inválida.');
}

/**
 * Handles purchase of a new virtual number
 */
function handleBuyNumber(int $uid, PDO $pdo): void
{
    $country = strtolower(trim($_GET['country'] ?? ''));
    $product = strtolower(trim($_GET['product'] ?? ''));

    if (empty($country) || empty($product)) {
        jsonError('País e produto são obrigatórios.');
    }

    // Normalize country name for 5sim API
    $countryMap = [
        'united_kingdom' => 'england',
        'uk'             => 'england',
        'eua'            => 'usa',
    ];
    $fivesimCountry = $countryMap[$country] ?? $country;

    // Determine the cost in BRL cents based on pricing policy
    $costCents = 150; // default R$ 1,50
    if ($product === 'whatsapp') {
        $costCents = ($country === 'brazil') ? 2490 : 490; // R$ 24,90 or R$ 4,90
    } elseif ($product === 'telegram') {
        $costCents = 290; // R$ 2,90
    } elseif ($product === 'economico') {
        $costCents = 50; // R$ 0,50
    }

    // Check user balance
    $stmt = $pdo->prepare('SELECT credits FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $userRow = $stmt->fetch();
    $balance = (int) ($userRow['credits'] ?? 0);

    if ($balance < $costCents) {
        $costFormatted = number_format($costCents / 100, 2, ',', '.');
        $userBalanceFormatted = number_format($balance / 100, 2, ',', '.');
        jsonError("Saldo insuficiente (Seu saldo atual: R$ {$userBalanceFormatted}). Este número custa R$ {$costFormatted}. Faça uma recarga via PIX para prosseguir.", 400);
    }

    // 5sim API parameters
    $operator = 'any';
    $productMap = [
        'whatsapp'  => 'whatsapp',
        'telegram'  => 'telegram',
        'google'    => 'google',
        'microsoft' => 'microsoft',
        'tiktok'    => 'tiktok',
        'economico' => 'opt',
    ];
    $fivesimProduct = $productMap[$product] ?? $product;

    // Make cURL call to 5sim
    $url = "https://5sim.net/v1/user/buy/activation/{$fivesimCountry}/{$operator}/{$fivesimProduct}";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . FIVESIM_API_KEY,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $err = json_decode($response, true);
        $errMsg = $err['error'] ?? (is_string($response) ? $response : 'Nenhum número disponível no momento.');
        
        // Detailed user-friendly translations for 5sim responses
        if (str_contains(strtolower($errMsg), 'no free phones') || str_contains(strtolower($errMsg), 'no_free_phones')) {
            $errMsg = 'Não há números virtuais disponíveis para o país selecionado no momento. Por favor, tente outro país (ex: Brasil, Argentina, Rússia, Indonésia).';
        } elseif (str_contains(strtolower($errMsg), 'not enough user balance') || str_contains(strtolower($errMsg), 'not_enough_user_balance') || str_contains(strtolower($errMsg), 'balance')) {
            $errMsg = 'Erro na API do provedor (Saldo da chave 5sim zerado). Por favor, contate o suporte.';
        } elseif (str_contains(strtolower($errMsg), 'bad country') || str_contains(strtolower($errMsg), 'bad_country')) {
            $errMsg = 'O país selecionado não possui serviço de SMS ativo para este app. Escolha outro país.';
        }
        
        jsonError($errMsg, 400);
    }

    $data = json_decode($response, true);
    $fivesimId = (string) ($data['id'] ?? '');
    $phone = (string) ($data['phone'] ?? '');

    if (empty($fivesimId) || empty($phone)) {
        jsonError('Falha ao obter número do provedor.');
    }

    // Begin Transaction to safeguard user balance
    $pdo->beginTransaction();
    try {
        // Deduct balance temporarily
        $pdo->prepare('UPDATE users SET credits = credits - ? WHERE id = ?')
            ->execute([$costCents, $uid]);

        // Insert SMS Order record
        $pdo->prepare('
            INSERT INTO sms_orders (user_id, fivesim_id, phone, country, product, cost_cents, status)
            VALUES (?, ?, ?, ?, ?, ?, "pending")
        ')->execute([$uid, $fivesimId, $phone, $country, $product, $costCents]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonError('Erro no banco de dados local.');
    }

    jsonResponse([
        'success'    => true,
        'order_id'   => $fivesimId,
        'phone'      => $phone,
        'country'    => $country,
        'product'    => $product,
        'cost_cents' => $costCents,
        'status'     => 'pending',
        'expires_in' => 900 // 15 mins in seconds
    ]);
}

/**
 * Checks for received SMS
 */
function handleCheckSMS(int $uid, PDO $pdo): void
{
    $fivesimId = trim($_GET['id'] ?? '');

    if (empty($fivesimId)) {
        jsonError('ID do número é obrigatório.');
    }

    // Fetch order from database
    $stmt = $pdo->prepare('SELECT * FROM sms_orders WHERE fivesim_id = ? AND user_id = ?');
    $stmt->execute([$fivesimId, $uid]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonError('Ordem não encontrada.');
    }

    if ($order['status'] !== 'pending') {
        // Already resolved (received or canceled)
        jsonResponse([
            'success'  => true,
            'status'   => $order['status'],
            'sms_code' => $order['sms_code'],
            'phone'    => $order['phone']
        ]);
    }

    // Call 5sim to check status
    $url = "https://5sim.net/v1/user/check/{$fivesimId}";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . FIVESIM_API_KEY,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        jsonError('Erro ao consultar status no provedor.');
    }

    $data = json_decode($response, true);
    $status = strtoupper($data['status'] ?? 'PENDING');
    $smsList = $data['sms'] ?? [];

    if (($status === 'FINISHED' || $status === 'RECEIVED') && !empty($smsList)) {
        // SMS received! Find code.
        $lastSms = end($smsList);
        $code = (string) ($lastSms['code'] ?? '');
        $fullText = (string) ($lastSms['text'] ?? '');

        if (empty($code)) {
            // Regex fallback to extract 6 digit code from text
            if (preg_match('/(\d{3}-\d{3})/', $fullText, $matches)) {
                $code = str_replace('-', '', $matches[1]);
            } elseif (preg_match('/(\d{6})/', $fullText, $matches)) {
                $code = $matches[1];
            }
        }

        // Update database: order is complete, finalize transaction
        $pdo->prepare('
            UPDATE sms_orders 
            SET status = "received", sms_code = ?, updated_at = datetime("now") 
            WHERE fivesim_id = ?
        ')->execute([$code, $fivesimId]);

        // Tell 5sim we are done and finished
        $finishCh = curl_init("https://5sim.net/v1/user/finish/{$fivesimId}");
        curl_setopt_array($finishCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . FIVESIM_API_KEY,
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($finishCh);
        curl_close($finishCh);

        jsonResponse([
            'success'  => true,
            'status'   => 'received',
            'sms_code' => $code,
            'phone'    => $order['phone']
        ]);
    }

    if ($status === 'CANCELED' || $status === 'TIMEOUT') {
        // Order failed/expired at provider, refund user balance
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET credits = credits + ? WHERE id = ?')
                ->execute([(int) $order['cost_cents'], $uid]);

            $pdo->prepare('UPDATE sms_orders SET status = "canceled", updated_at = datetime("now") WHERE fivesim_id = ?')
                ->execute([$fivesimId]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }

        jsonResponse([
            'success'  => true,
            'status'   => 'canceled',
            'sms_code' => null,
            'phone'    => $order['phone']
        ]);
    }

    // Still waiting
    jsonResponse([
        'success'  => true,
        'status'   => 'pending',
        'sms_code' => null,
        'phone'    => $order['phone']
    ]);
}

/**
 * Handles manual cancellation
 */
function handleCancelNumber(int $uid, PDO $pdo): void
{
    $fivesimId = trim($_GET['id'] ?? '');

    if (empty($fivesimId)) {
        jsonError('ID do número é obrigatório.');
    }

    $stmt = $pdo->prepare('SELECT * FROM sms_orders WHERE fivesim_id = ? AND user_id = ?');
    $stmt->execute([$fivesimId, $uid]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonError('Ordem não encontrada.');
    }

    if ($order['status'] !== 'pending') {
        jsonError('Este número já foi finalizado ou cancelado.');
    }

    // Request cancellation to 5sim
    $ch = curl_init("https://5sim.net/v1/user/cancel/{$fivesimId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . FIVESIM_API_KEY,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        jsonError('Não foi possível cancelar o número no provedor (SMS pode estar chegando).');
    }

    // Refund credits to user
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE users SET credits = credits + ? WHERE id = ?')
            ->execute([(int) $order['cost_cents'], $uid]);

        $pdo->prepare('UPDATE sms_orders SET status = "canceled", updated_at = datetime("now") WHERE fivesim_id = ?')
            ->execute([$fivesimId]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonError('Falha ao processar o estorno de créditos.');
    }

    jsonResponse([
        'success' => true,
        'status'  => 'canceled'
    ]);
}
