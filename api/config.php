<?php
declare(strict_types=1);

/**
 * VirtualSim — Configuration
 * Secrets and database are stored outside the public webroot in production.
 */

define('BASE_DIR', dirname(__DIR__));
define('PRIVATE_DIR', '/home/fabiano/private/virtualsim');

// Load environment variables from private directory first, then local .env
(static function (): void {
    $candidates = [PRIVATE_DIR . '/.env', BASE_DIR . '/.env'];
    foreach ($candidates as $envFile) {
        if (!is_file($envFile) || !is_readable($envFile)) {
            continue;
        }
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }
            [$key, $val] = explode('=', $trimmed, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if ($key !== '' && getenv($key) === false) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
            }
        }
        break;
    }
})();

// Database path: prefer private directory outside webroot in production
define('DB_PATH', is_dir(PRIVATE_DIR) ? PRIVATE_DIR . '/virtualsim.db' : BASE_DIR . '/database/virtualsim.db');

// Credentials (loaded from .env — never hardcode secrets)
define('MP_PUBLIC_KEY', (string) (getenv('MP_PUBLIC_KEY') ?: ''));
define('MP_ACCESS_TOKEN', (string) (getenv('MP_ACCESS_TOKEN') ?: ''));
define('MP_WEBHOOK_SECRET', (string) (getenv('MP_WEBHOOK_SECRET') ?: ''));
define('FIVESIM_API_KEY', (string) (getenv('FIVESIM_API_KEY') ?: ''));
define('GOOGLE_CLIENT_ID', (string) (getenv('GOOGLE_CLIENT_ID') ?: '569266864432-pd09jbb5no9ekdhdr018fj643nopp817.apps.googleusercontent.com'));

// App URL & General settings
define('APP_NAME', 'VirtualSim');
define('APP_URL', 'https://4u.ia.br/app/virtualsim');

// Pricing packages (BRL cents) — includes R$ 25,00 ideal for WhatsApp BR (R$ 24,90)
define('CREDIT_PACKAGES', [
    ['credits' => 1000,  'price_cents' => 1000,  'label' => 'Recarga R$ 10,00'],
    ['credits' => 2500,  'price_cents' => 2500,  'label' => 'Recarga R$ 25,00'],
    ['credits' => 5000,  'price_cents' => 5000,  'label' => 'Recarga R$ 50,00'],
    ['credits' => 10000, 'price_cents' => 10000, 'label' => 'Recarga R$ 100,00'],
]);

// Security & CORS Headers (only in HTTP context)
if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = [
        'https://4u.ia.br',
        'https://www.4u.ia.br',
        'http://localhost',
        'http://127.0.0.1',
    ];
    foreach ($allowedOrigins as $allowed) {
        if ($origin === $allowed || str_starts_with($origin, $allowed . ':')) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            break;
        }
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
