<?php
declare(strict_types=1);

/**
 * POST /api/mp_webhook.php
 * Mercado Pago IPN handler.
 * Verifies payment status and credits user's balance automatically.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$topic     = $body['type'] ?? ($_GET['topic'] ?? '');
$paymentId = $body['data']['id'] ?? ($_GET['id'] ?? null);

if ($topic !== 'payment' || !$paymentId) {
    http_response_code(200);
    echo 'OK';
    exit;
}

// Fetch payment from Mercado Pago to verify status
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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(200);
    exit;
}

$payment = json_decode($response, true);

if (($payment['status'] ?? '') !== 'approved') {
    $pdo = Database::get();
    $pdo->prepare('UPDATE transactions SET status = ? WHERE mp_payment_id = ?')
        ->execute([$payment['status'] ?? 'pending', (string) $paymentId]);
    http_response_code(200);
    echo 'OK';
    exit;
}

// Payment approved — process credits
$userId       = $payment['external_reference'] ?? null;
$creditsToAdd = (int) ($payment['metadata']['credits'] ?? 0);
$label        = $payment['metadata']['package_label'] ?? 'Recarga PIX';
$amountBRL    = (float) ($payment['transaction_amount'] ?? 0);

if (!$userId || $creditsToAdd <= 0) {
    http_response_code(200);
    exit;
}

$pdo = Database::get();

// Idempotency check
$checkStmt = $pdo->prepare('SELECT status FROM transactions WHERE mp_payment_id = ?');
$checkStmt->execute([(string) $paymentId]);
$existing = $checkStmt->fetch();

if ($existing && $existing['status'] === 'approved') {
    http_response_code(200);
    echo 'OK';
    exit;
}

// Add credits (BRL cents balance) to user
$pdo->prepare('UPDATE users SET credits = credits + ?, updated_at = datetime("now") WHERE id = ?')
    ->execute([$creditsToAdd, $userId]);

// Update or insert transaction record
if ($existing) {
    $pdo->prepare('UPDATE transactions SET status = "approved", credits_added = ?, amount_brl = ? WHERE mp_payment_id = ?')
        ->execute([$creditsToAdd, $amountBRL, (string) $paymentId]);
} else {
    $pdo->prepare('
        INSERT INTO transactions (user_id, mp_payment_id, package_label, amount_brl, credits_added, status)
        VALUES (?, ?, ?, ?, ?, "approved")
    ')->execute([$userId, (string) $paymentId, $label, $amountBRL, $creditsToAdd]);
}

http_response_code(200);
echo 'OK';
