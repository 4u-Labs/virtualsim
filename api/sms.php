<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * SMS/Virtual Number Controller (5sim.net Integration)
 * Endpoints:
 * GET  /api/sms.php?action=availability&country=brazil  (public, cached 60s)
 * GET  /api/sms.php?action=list                         (auth: active + history + transactions)
 * GET  /api/sms.php?action=check&id=X                   (auth: poll SMS status)
 * POST /api/sms.php?action=buy                          (auth: atomic balance reservation + buy)
 * POST /api/sms.php?action=cancel                       (auth: cancel + atomic single-refund)
 */

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = (string) ($_GET['action'] ?? '');

// Public stock availability endpoint (no auth required)
if ($action === 'availability') {
    if ($method !== 'GET') {
        jsonError('Method not allowed.', 405);
    }
    handleAvailability();
}

$user = verifyAuthToken();
$uid  = (int) $user['id'];
$pdo  = Database::get();

switch ($action) {
    case 'list':
        if ($method !== 'GET') jsonError('Method not allowed.', 405);
        handleListOrders($uid, $pdo);
        break;
    case 'check':
        if ($method !== 'GET') jsonError('Method not allowed.', 405);
        handleCheckSMS($uid, $pdo);
        break;
    case 'buy':
        if ($method !== 'POST') jsonError('Use POST para solicitar número.', 405);
        handleBuyNumber($uid, $pdo);
        break;
    case 'cancel':
        if ($method !== 'POST') jsonError('Use POST para cancelar número.', 405);
        handleCancelNumber($uid, $pdo);
        break;
    default:
        jsonError('Ação inválida.');
}

function getProductMap(): array
{
    return [
        'whatsapp'  => 'whatsapp',
        'telegram'  => 'telegram',
        'google'    => 'google',
        'microsoft' => 'microsoft',
        'tiktok'    => 'tiktok',
        'economico' => 'opt',
    ];
}

function normalizeCountry(string $country): string
{
    $map = [
        'united_kingdom' => 'england',
        'uk'             => 'england',
        'eua'            => 'usa',
    ];
    return $map[$country] ?? $country;
}

/**
 * Real-time stock availability from 5sim guest API (cached 60s).
 */
function handleAvailability(): never
{
    $country = strtolower(trim((string) ($_GET['country'] ?? 'brazil')));
    if (!preg_match('/^[a-z_]{2,32}$/', $country)) {
        jsonError('País inválido.');
    }

    $fivesimCountry = normalizeCountry($country);
    $cacheFile = sys_get_temp_dir() . "/virtualsim_stock_{$fivesimCountry}.json";

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            jsonResponse($cached);
        }
    }

    $url = "https://5sim.net/v1/guest/prices?country=" . urlencode($fivesimCountry);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $counts = [];
    $productMap = getProductMap();
    foreach ($productMap as $localKey => $_) {
        $counts[$localKey] = null; // unknown fallback
    }

    if ($response !== false && $httpCode === 200) {
        $data = json_decode($response, true);
        $countryData = $data[$fivesimCountry] ?? [];
        if (is_array($countryData)) {
            foreach ($productMap as $localKey => $fivesimProduct) {
                $total = 0;
                if (isset($countryData[$fivesimProduct]) && is_array($countryData[$fivesimProduct])) {
                    foreach ($countryData[$fivesimProduct] as $opInfo) {
                        $total += (int) ($opInfo['count'] ?? 0);
                    }
                }
                $counts[$localKey] = $total;
            }
        }
    }

    $payload = [
        'success'    => true,
        'country'    => $country,
        'updated_at' => time(),
        'services'   => $counts,
    ];
    @file_put_contents($cacheFile, json_encode($payload));
    jsonResponse($payload);
}

/**
 * List active (pending) orders + recent history + recent PIX transactions.
 */
function handleListOrders(int $uid, PDO $pdo): never
{
    $stmt = $pdo->prepare('
        SELECT fivesim_id, phone, country, product, cost_cents, sms_code, status, created_at, updated_at
        FROM sms_orders
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 30
    ');
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll();

    $active = [];
    $history = [];
    $now = time();

    foreach ($rows as $row) {
        $createdTs = strtotime((string) $row['created_at'] . ' UTC') ?: $now;
        $remaining = max(0, 900 - ($now - $createdTs));

        $item = [
            'order_id'          => (string) $row['fivesim_id'],
            'phone'             => (string) $row['phone'],
            'country'           => (string) $row['country'],
            'product'           => (string) $row['product'],
            'cost_cents'        => (int) $row['cost_cents'],
            'sms_code'          => $row['sms_code'],
            'status'            => (string) $row['status'],
            'created_at'        => (string) $row['created_at'],
            'remaining_seconds' => $remaining,
        ];

        if ($row['status'] === 'pending') {
            $active[] = $item;
        } else {
            $history[] = $item;
        }
    }

    $txStmt = $pdo->prepare('
        SELECT mp_payment_id, package_label, amount_brl, credits_added, status, created_at
        FROM transactions
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 15
    ');
    $txStmt->execute([$uid]);
    $transactions = $txStmt->fetchAll();

    jsonResponse([
        'success'      => true,
        'active'       => $active,
        'history'      => array_slice($history, 0, 20),
        'transactions' => $transactions,
    ]);
}

/**
 * Atomic single-refund helper: only credits user if order transitions from pending -> canceled.
 */
function refundPendingOrderAtomically(PDO $pdo, string $fivesimId, int $uid, int $costCents): bool
{
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $upd = $pdo->prepare('
            UPDATE sms_orders
            SET status = "canceled", updated_at = datetime("now")
            WHERE fivesim_id = ? AND user_id = ? AND status = "pending"
        ');
        $upd->execute([$fivesimId, $uid]);

        if ($upd->rowCount() === 1) {
            $pdo->prepare('UPDATE users SET credits = credits + ?, updated_at = datetime("now") WHERE id = ?')
                ->execute([$costCents, $uid]);
            $pdo->exec('COMMIT');
            return true;
        }

        $pdo->exec('COMMIT');
        return false;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->exec('ROLLBACK');
        }
        throw $e;
    }
}

/**
 * Handles purchase of a new virtual number with atomic balance reservation.
 */
function handleBuyNumber(int $uid, PDO $pdo): never
{
    if (FIVESIM_API_KEY === '') {
        jsonError('Chave do provedor de SMS não configurada.', 503);
    }

    $body = json_decode(file_get_contents('php://input') ?: '', true) ?? [];
    $country = strtolower(trim((string) ($body['country'] ?? ($_GET['country'] ?? ''))));
    $product = strtolower(trim((string) ($body['product'] ?? ($_GET['product'] ?? ''))));

    if ($country === '' || $product === '') {
        jsonError('País e produto são obrigatórios.');
    }

    $productMap = getProductMap();
    if (!isset($productMap[$product])) {
        jsonError('Serviço selecionado inválido.');
    }
    if (!preg_match('/^[a-z_]{2,32}$/', $country)) {
        jsonError('País selecionado inválido.');
    }

    $fivesimCountry = normalizeCountry($country);
    $fivesimProduct = $productMap[$product];

    // Pricing policy (BRL cents)
    $costCents = 150;
    if ($product === 'whatsapp') {
        $costCents = ($country === 'brazil') ? 2490 : 490;
    } elseif ($product === 'telegram') {
        $costCents = 290;
    } elseif ($product === 'economico') {
        $costCents = 50;
    }

    // Step 1: Atomically reserve balance BEFORE calling 5sim (prevents double-spend race condition)
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $deduct = $pdo->prepare('
            UPDATE users
            SET credits = credits - ?, updated_at = datetime("now")
            WHERE id = ? AND credits >= ?
        ');
        $deduct->execute([$costCents, $uid, $costCents]);

        if ($deduct->rowCount() !== 1) {
            $balStmt = $pdo->prepare('SELECT credits FROM users WHERE id = ?');
            $balStmt->execute([$uid]);
            $balance = (int) ($balStmt->fetchColumn() ?: 0);
            $pdo->exec('ROLLBACK');

            $costFormatted = number_format($costCents / 100, 2, ',', '.');
            $userBalanceFormatted = number_format($balance / 100, 2, ',', '.');
            jsonError("Saldo insuficiente (Seu saldo atual: R$ {$userBalanceFormatted}). Este número custa R$ {$costFormatted}. Faça uma recarga via PIX para prosseguir.", 400);
        }
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->exec('ROLLBACK');
        }
        jsonError('Erro ao reservar saldo. Tente novamente.', 500);
    }

    // Step 2: Call 5sim API to acquire virtual number
    $operator = 'any';
    $url = "https://5sim.net/v1/user/buy/activation/{$fivesimCountry}/{$operator}/{$fivesimProduct}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . FIVESIM_API_KEY,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        // Refund reserved balance immediately
        $pdo->prepare('UPDATE users SET credits = credits + ?, updated_at = datetime("now") WHERE id = ?')
            ->execute([$costCents, $uid]);

        $err = is_string($response) ? json_decode($response, true) : null;
        $errMsg = (string) ($err['error'] ?? (is_string($response) && $response !== '' ? $response : 'Nenhum número disponível no momento.'));
        $lower = strtolower($errMsg);

        if (str_contains($lower, 'no free phones') || str_contains($lower, 'no_free_phones')) {
            $errMsg = 'Não há números virtuais disponíveis para este serviço no momento. Tente novamente em instantes ou escolha outro serviço.';
        } elseif (str_contains($lower, 'not enough user balance') || str_contains($lower, 'not_enough_user_balance') || str_contains($lower, 'balance')) {
            $errMsg = 'Serviço temporariamente indisponível no provedor. Seu saldo não foi cobrado — tente novamente em breve ou contate o suporte.';
        } elseif (str_contains($lower, 'bad country') || str_contains($lower, 'bad_country')) {
            $errMsg = 'O país selecionado não possui serviço de SMS ativo para este app.';
        }

        jsonError($errMsg, 400);
    }

    $data      = json_decode($response, true);
    $fivesimId = (string) ($data['id'] ?? '');
    $phone     = (string) ($data['phone'] ?? '');

    if ($fivesimId === '' || $phone === '') {
        $pdo->prepare('UPDATE users SET credits = credits + ?, updated_at = datetime("now") WHERE id = ?')
            ->execute([$costCents, $uid]);
        jsonError('Falha ao obter número do provedor. Seu saldo não foi cobrado.');
    }

    // Step 3: Record order in DB
    try {
        $pdo->prepare('
            INSERT INTO sms_orders (user_id, fivesim_id, phone, country, product, cost_cents, status)
            VALUES (?, ?, ?, ?, ?, ?, "pending")
        ')->execute([$uid, $fivesimId, $phone, $country, $product, $costCents]);
    } catch (Throwable $e) {
        $pdo->prepare('UPDATE users SET credits = credits + ?, updated_at = datetime("now") WHERE id = ?')
            ->execute([$costCents, $uid]);
        jsonError('Erro ao registrar pedido no banco local. Seu saldo foi devolvido.', 500);
    }

    jsonResponse([
        'success'           => true,
        'order_id'          => $fivesimId,
        'phone'             => $phone,
        'country'           => $country,
        'product'           => $product,
        'cost_cents'        => $costCents,
        'status'            => 'pending',
        'expires_in'        => 900,
        'remaining_seconds' => 900,
    ]);
}

/**
 * Checks for received SMS (and performs atomic refund on CANCELED/TIMEOUT/BANNED).
 */
function handleCheckSMS(int $uid, PDO $pdo): never
{
    $fivesimId = trim((string) ($_GET['id'] ?? ''));

    if ($fivesimId === '' || !ctype_digit($fivesimId)) {
        jsonError('ID do número inválido.');
    }

    $stmt = $pdo->prepare('SELECT * FROM sms_orders WHERE fivesim_id = ? AND user_id = ?');
    $stmt->execute([$fivesimId, $uid]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonError('Ordem não encontrada.', 404);
    }

    if ($order['status'] !== 'pending') {
        jsonResponse([
            'success'  => true,
            'status'   => $order['status'],
            'sms_code' => $order['sms_code'],
            'phone'    => $order['phone'],
        ]);
    }

    $ch = curl_init("https://5sim.net/v1/user/check/{$fivesimId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . FIVESIM_API_KEY,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        jsonError('Erro ao consultar status no provedor.', 502);
    }

    $data    = json_decode($response, true);
    $status  = strtoupper((string) ($data['status'] ?? 'PENDING'));
    $smsList = $data['sms'] ?? [];

    if (($status === 'FINISHED' || $status === 'RECEIVED') && !empty($smsList)) {
        $lastSms  = end($smsList);
        $code     = (string) ($lastSms['code'] ?? '');
        $fullText = (string) ($lastSms['text'] ?? '');

        if ($code === '') {
            if (preg_match('/(\d{3}-\d{3})/', $fullText, $matches)) {
                $code = str_replace('-', '', $matches[1]);
            } elseif (preg_match('/(\d{4,8})/', $fullText, $matches)) {
                $code = $matches[1];
            }
        }

        $pdo->prepare('
            UPDATE sms_orders
            SET status = "received", sms_code = ?, updated_at = datetime("now")
            WHERE fivesim_id = ? AND user_id = ? AND status = "pending"
        ')->execute([$code, $fivesimId, $uid]);

        // Notify 5sim order finished
        $finishCh = curl_init("https://5sim.net/v1/user/finish/{$fivesimId}");
        curl_setopt_array($finishCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . FIVESIM_API_KEY,
                'Accept: application/json',
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
            'phone'    => $order['phone'],
        ]);
    }

    if ($status === 'CANCELED' || $status === 'TIMEOUT' || $status === 'BANNED') {
        refundPendingOrderAtomically($pdo, $fivesimId, $uid, (int) $order['cost_cents']);

        jsonResponse([
            'success'  => true,
            'status'   => 'canceled',
            'sms_code' => null,
            'phone'    => $order['phone'],
        ]);
    }

    jsonResponse([
        'success'  => true,
        'status'   => 'pending',
        'sms_code' => null,
        'phone'    => $order['phone'],
    ]);
}

/**
 * Handles manual cancellation with atomic single-refund protection.
 */
function handleCancelNumber(int $uid, PDO $pdo): never
{
    $body = json_decode(file_get_contents('php://input') ?: '', true) ?? [];
    $fivesimId = trim((string) ($body['id'] ?? ($_GET['id'] ?? '')));

    if ($fivesimId === '' || !ctype_digit($fivesimId)) {
        jsonError('ID do número inválido.');
    }

    $stmt = $pdo->prepare('SELECT * FROM sms_orders WHERE fivesim_id = ? AND user_id = ?');
    $stmt->execute([$fivesimId, $uid]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonError('Ordem não encontrada.', 404);
    }

    if ($order['status'] !== 'pending') {
        jsonError('Este número já foi finalizado ou cancelado.');
    }

    // Request cancellation at 5sim
    $ch = curl_init("https://5sim.net/v1/user/cancel/{$fivesimId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . FIVESIM_API_KEY,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // If 5sim returns non-200, double-check if it already expired/canceled at provider
    $canRefund = ($httpCode === 200);
    if (!$canRefund) {
        $chk = curl_init("https://5sim.net/v1/user/check/{$fivesimId}");
        curl_setopt_array($chk, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . FIVESIM_API_KEY,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $chkRes = curl_exec($chk);
        $chkCode = (int) curl_getinfo($chk, CURLINFO_HTTP_CODE);
        curl_close($chk);
        if ($chkRes !== false && $chkCode === 200) {
            $chkData = json_decode($chkRes, true);
            $remoteStatus = strtoupper((string) ($chkData['status'] ?? ''));
            if (in_array($remoteStatus, ['CANCELED', 'TIMEOUT', 'BANNED'], true)) {
                $canRefund = true;
            }
        }
    }

    if (!$canRefund) {
        jsonError('Não foi possível cancelar o número no provedor (o SMS pode já estar chegando).');
    }

    try {
        refundPendingOrderAtomically($pdo, $fivesimId, $uid, (int) $order['cost_cents']);
    } catch (Throwable $e) {
        jsonError('Falha ao processar o estorno de créditos.', 500);
    }

    jsonResponse([
        'success' => true,
        'status'  => 'canceled',
    ]);
}
