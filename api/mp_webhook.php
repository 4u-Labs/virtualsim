<?php
declare(strict_types=1);

/**
 * POST /api/mp_webhook.php
 * Mercado Pago IPN / Webhook handler with atomic idempotency & optional x-signature verification.
 */

require_once __DIR__ . '/database.php';

$rawBody   = file_get_contents('php://input') ?: '';
$body      = json_decode($rawBody, true) ?? [];
$topic     = (string) ($body['type'] ?? ($_GET['topic'] ?? ''));
$paymentId = (string) ($body['data']['id'] ?? ($_GET['data_id'] ?? ($_GET['id'] ?? '')));

// Optional HMAC signature verification when MP_WEBHOOK_SECRET is configured
if (MP_WEBHOOK_SECRET !== '' && $paymentId !== '') {
    $xSig = (string) ($_SERVER['HTTP_X_SIGNATURE'] ?? '');
    $xReqId = (string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? '');
    $ts = '';
    $v1 = '';
    foreach (explode(',', $xSig) as $part) {
        $kv = explode('=', trim($part), 2);
        if (count($kv) === 2) {
            if ($kv[0] === 'ts') $ts = $kv[1];
            if ($kv[0] === 'v1') $v1 = $kv[1];
        }
    }
    $manifest = "id:{$paymentId};request-id:{$xReqId};ts:{$ts};";
    $expected = hash_hmac('sha256', $manifest, MP_WEBHOOK_SECRET);
    if ($v1 === '' || !hash_equals($expected, $v1)) {
        http_response_code(401);
        echo 'Invalid signature';
        exit;
    }
}

if ($topic !== 'payment' || $paymentId === '' || !ctype_digit($paymentId)) {
    http_response_code(200);
    echo 'OK';
    exit;
}

// Fetch authoritative payment state directly from Mercado Pago
$ch = curl_init("https://api.mercadopago.com/v1/payments/{$paymentId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
    ],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    http_response_code(200);
    exit;
}

$payment = json_decode($response, true);
$status  = (string) ($payment['status'] ?? 'pending');

if ($status !== 'approved') {
    $pdo = Database::get();
    $pdo->prepare('UPDATE transactions SET status = ? WHERE mp_payment_id = ? AND status != "approved"')
        ->execute([$status, $paymentId]);
    http_response_code(200);
    echo 'OK';
    exit;
}

$userId       = (int) ($payment['external_reference'] ?? 0);
$creditsToAdd = (int) ($payment['metadata']['credits'] ?? 0);
$label        = (string) ($payment['metadata']['package_label'] ?? 'Recarga PIX');
$amountBRL    = (float) ($payment['transaction_amount'] ?? 0);

// Sanity-check amount matches requested credit value (1 credit = 1 cent BRL)
if ($userId <= 0 || $creditsToAdd <= 0 || abs($amountBRL - ($creditsToAdd / 100)) > 0.05) {
    http_response_code(200);
    exit;
}

$pdo = Database::get();

// Atomic BEGIN IMMEDIATE transaction prevents duplicate credits on concurrent webhooks
$pdo->exec('BEGIN IMMEDIATE');
try {
    $checkStmt = $pdo->prepare('SELECT status FROM transactions WHERE mp_payment_id = ?');
    $checkStmt->execute([$paymentId]);
    $existing = $checkStmt->fetch();

    if ($existing && $existing['status'] === 'approved') {
        $pdo->exec('COMMIT');
        http_response_code(200);
        echo 'OK';
        exit;
    }

    // Verify user exists before crediting
    $userStmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $userStmt->execute([$userId]);
    if (!$userStmt->fetch()) {
        $pdo->exec('ROLLBACK');
        http_response_code(200);
        exit;
    }

    if ($existing) {
        $pdo->prepare('UPDATE transactions SET status = "approved", credits_added = ?, amount_brl = ? WHERE mp_payment_id = ?')
            ->execute([$creditsToAdd, $amountBRL, $paymentId]);
    } else {
        $pdo->prepare('
            INSERT INTO transactions (user_id, mp_payment_id, package_label, amount_brl, credits_added, status)
            VALUES (?, ?, ?, ?, ?, "approved")
        ')->execute([$userId, $paymentId, $label, $amountBRL, $creditsToAdd]);
    }

    $pdo->prepare('UPDATE users SET credits = credits + ?, updated_at = datetime("now") WHERE id = ?')
        ->execute([$creditsToAdd, $userId]);

    $pdo->exec('COMMIT');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->exec('ROLLBACK');
    }
    http_response_code(500);
    exit;
}

http_response_code(200);
echo 'OK';
