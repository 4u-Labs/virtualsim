<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * POST /api/mp_create.php
 * Creates a Mercado Pago PIX payment.
 * Body: { "package_index": 0|1|2|3 }
 */

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonError('Method not allowed.', 405);
}

if (MP_ACCESS_TOKEN === '') {
    jsonError('Gateway de pagamento não configurado.', 503);
}

$user  = verifyAuthToken();
$uid   = (int) $user['id'];
$email = (string) ($user['email'] ?? '');

$body         = json_decode(file_get_contents('php://input') ?: '', true) ?? [];
$packageIndex = (int) ($body['package_index'] ?? 1);
$packages     = CREDIT_PACKAGES;

if (!isset($packages[$packageIndex])) {
    jsonError('Pacote de recarga inválido.');
}

$package   = $packages[$packageIndex];
$amountBRL = round($package['price_cents'] / 100, 2);
$credits   = (int) $package['credits'];
$label     = (string) $package['label'];

$mpPayload = [
    'transaction_amount' => $amountBRL,
    'description'        => "VirtualSim — {$label}",
    'payment_method_id'  => 'pix',
    'external_reference' => (string) $uid,
    'notification_url'   => APP_URL . '/api/mp_webhook.php',
    'payer' => [
        'email'      => $email ?: 'comprador@4u.ia.br',
        'first_name' => 'Usuário',
        'last_name'  => 'VirtualSim',
    ],
    'metadata' => [
        'credits'       => $credits,
        'user_id'       => $uid,
        'package_label' => $label,
    ],
];

$idempotencyKey = 'virtualsim-' . $uid . '-' . bin2hex(random_bytes(8));

$ch = curl_init('https://api.mercadopago.com/v1/payments');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($mpPayload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'X-Idempotency-Key: ' . $idempotencyKey,
    ],
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode !== 201) {
    $err = is_string($response) ? json_decode($response, true) : null;
    $msg = $err['message'] ?? ($curlErr !== '' ? 'Falha de conexão com Mercado Pago.' : 'Tente novamente.');
    jsonError('Erro ao gerar PIX: ' . $msg, 502);
}

$mpData    = json_decode($response, true);
$paymentId = (string) ($mpData['id'] ?? '');
$pixQrCode = (string) ($mpData['point_of_interaction']['transaction_data']['qr_code'] ?? '');
$pixQrB64  = (string) ($mpData['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '');
$expiresAt = (string) ($mpData['date_of_expiration'] ?? '');

if ($paymentId === '' || $pixQrCode === '') {
    jsonError('Resposta inválida ao gerar PIX. Tente novamente.', 502);
}

$pdo = Database::get();
$pdo->prepare('
    INSERT OR IGNORE INTO transactions (user_id, mp_payment_id, package_label, amount_brl, credits_added, status)
    VALUES (?, ?, ?, ?, ?, ?)
')->execute([$uid, $paymentId, $label, $amountBRL, $credits, 'pending']);

jsonResponse([
    'success'        => true,
    'payment_id'     => $paymentId,
    'qr_code'        => $pixQrCode,
    'qr_code_base64' => $pixQrB64,
    'amount_brl'     => $amountBRL,
    'credits'        => $credits,
    'label'          => $label,
    'expires_at'     => $expiresAt,
]);
