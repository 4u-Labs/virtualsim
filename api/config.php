<?php
declare(strict_types=1);

/**
 * VirtualSim — Configuration
 */

// Paths
define('BASE_DIR', dirname(__DIR__));
define('DB_PATH', BASE_DIR . '/database/virtualsim.db');

// Mercado Pago (Ported from KeepAI)
define('MP_PUBLIC_KEY', 'APP_USR-cbe6db67-0f0a-4149-82f9-abfc0a57f55f');
define('MP_ACCESS_TOKEN', 'APP_USR-4404962015981699-111621-7eb905e9749a5abcac15f4e322da4b03-124159657');
define('MP_WEBHOOK_SECRET', '');

// 5sim.net API Key
define('FIVESIM_API_KEY', 'eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE4MTQ2Mjc0OTUsImlhdCI6MTc4MzA5MTQ5NSwicmF5IjoiNGExNzBjZTI2MGI3MmU1ZjUxOGQ1NGQ2NTE0ZThkNDkiLCJzdWIiOjQyODY5Mjd9.uI9BMbTQc8K7DctxuJ3GFklLWXt8NCz2j-cJgkaTHL3Ebuh4lADAf0isYdwWxiWlhnJ8G7ESnt1I7A5FOfyfnpvMgi5kmz1X26NREZGlA4h5mabqgdOhiEvPcoEYFNoiASD0Ij70vY_cFmxBeBVJNues9i95hUQ08ZAqIJ7WMZGK-n_5xxcfgiSs4qxhY-WIxxQCEx4gdGOo32cwKHb8HIO2PvQhgynBIfQ1mi7nIxAtwsmAesEpZ2Gzaj0TJNJlOPR16dROpX5kvLH3yX0m3XsoZkQ91CXVweWdjXpzN3QQ7THa-niO2Y6DK-A0q37PaT9PeEDtEFrUvTWOwDef4A'); // Replace with your actual 5sim.net token

// App URL & General settings
define('APP_NAME', 'VirtualSim');
define('APP_URL', 'https://4u.ia.br/app/virtualsim'); // Hosted subfolder

// Pricing package configuration (deposits in BRL cents)
define('CREDIT_PACKAGES', [
    ['credits' => 1000, 'price_cents' => 1000, 'label' => 'Recarga R$ 10,00'],
    ['credits' => 2000, 'price_cents' => 2000, 'label' => 'Recarga R$ 20,00'],
    ['credits' => 5000, 'price_cents' => 5000, 'label' => 'Recarga R$ 50,00'],
]);

// CORS Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
