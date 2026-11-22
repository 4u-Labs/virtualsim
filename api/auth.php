<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Authentication API (VirtualSim)
 * POST /api/auth.php?action=register  -> Create user
 * POST /api/auth.php?action=login     -> Authenticate with email/password
 * POST /api/auth.php?action=google    -> Authenticate with Google Identity Services
 * POST /api/auth.php?action=logout    -> Invalidate token server-side
 * GET  /api/auth.php                  -> Sync / Get user info
 */

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? 'sync';

if ($method === 'POST') {
    if ($action === 'register') {
        handleRegister();
    } elseif ($action === 'login') {
        handleLogin();
    } elseif ($action === 'google') {
        handleGoogleLogin();
    } elseif ($action === 'logout') {
        handleLogout();
    } else {
        jsonError('Invalid action.', 400);
    }
} elseif ($method === 'GET') {
    handleSync();
} else {
    jsonError('Method not allowed.', 405);
}

function formatUserPayload(array $user): array
{
    return [
        'id'           => (int) $user['id'],
        'email'        => (string) $user['email'],
        'credits'      => (int) $user['credits'],
        'display_name' => explode('@', (string) $user['email'])[0],
        'avatar_url'   => $user['avatar_url'] ?? null,
    ];
}

function handleRegister(): never
{
    $pdo = Database::get();
    checkRateLimit($pdo, 'register', 8, 900);

    $body = json_decode(file_get_contents('php://input') ?: '', true) ?? [];
    $email = mb_strtolower(trim((string) ($body['email'] ?? '')), 'UTF-8');
    $password = (string) ($body['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Por favor, informe um e-mail válido.');
    }
    if (strlen($password) < 6) {
        jsonError('A senha deve ter pelo menos 6 caracteres.');
    }

    $exists = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = ?');
    $exists->execute([$email]);
    if ($exists->fetch()) {
        recordRateLimitFailure($pdo, 'register');
        jsonError('Este e-mail já está cadastrado.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));

    try {
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, token, credits) VALUES (?, ?, ?, 0)');
        $stmt->execute([$email, $hash, $token]);
        $id = (int) $pdo->lastInsertId();

        jsonResponse([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'           => $id,
                'email'        => $email,
                'credits'      => 0,
                'display_name' => explode('@', $email)[0],
                'avatar_url'   => null,
            ],
        ], 201);
    } catch (Exception $e) {
        jsonError('Este e-mail já está cadastrado.');
    }
}

function handleLogin(): never
{
    $pdo = Database::get();
    checkRateLimit($pdo, 'login', 10, 900);

    $body = json_decode(file_get_contents('php://input') ?: '', true) ?? [];
    $email = mb_strtolower(trim((string) ($body['email'] ?? '')), 'UTF-8');
    $password = (string) ($body['password'] ?? '');

    if ($email === '' || $password === '') {
        jsonError('Preencha e-mail e senha.');
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(email) = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        recordRateLimitFailure($pdo, 'login');
        jsonError('E-mail ou senha incorretos.', 401);
    }

    $token = bin2hex(random_bytes(32));
    $pdo->prepare('UPDATE users SET token = ?, updated_at = datetime("now") WHERE id = ?')
        ->execute([$token, $user['id']]);

    jsonResponse([
        'success' => true,
        'token'   => $token,
        'user'    => formatUserPayload($user),
    ]);
}

function googleHttpJson(string $url, array $headers = [], ?string $postBody = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($postBody !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
    }
    $res = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false || $status !== 200) {
        jsonError('Não foi possível validar sua conta Google. Tente novamente.', 401);
    }
    $data = json_decode($res, true);
    if (!is_array($data)) {
        jsonError('Resposta inválida da autenticação Google.', 502);
    }
    return $data;
}

/**
 * Verifies Google OAuth access_token or ID token (JWT) against Google servers.
 */
function verifyGoogleCredentials(array $body): array
{
    $clientId = GOOGLE_CLIENT_ID;
    $accessToken = (string) ($body['access_token'] ?? '');
    $idToken = (string) ($body['id_token'] ?? ($body['credential'] ?? ''));

    if ($accessToken !== '') {
        if (strlen($accessToken) > 4096) {
            jsonError('Token Google inválido.', 401);
        }
        $tokenInfo = googleHttpJson(
            'https://oauth2.googleapis.com/tokeninfo',
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query(['access_token' => $accessToken])
        );
        $aud = (string) ($tokenInfo['audience'] ?? $tokenInfo['issued_to'] ?? '');
        if ($aud !== $clientId || (int) ($tokenInfo['expires_in'] ?? 0) < 1) {
            jsonError('Token Google não pertence a este aplicativo.', 401);
        }
        $profile = googleHttpJson(
            'https://www.googleapis.com/oauth2/v3/userinfo',
            ['Authorization: Bearer ' . $accessToken]
        );
        $email = mb_strtolower(trim((string) ($profile['email'] ?? '')), 'UTF-8');
        $sub   = (string) ($profile['sub'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($profile['email_verified'] ?? false) !== true || $sub === '') {
            jsonError('Conta Google sem e-mail verificado.', 401);
        }
        if (isset($tokenInfo['user_id']) && !hash_equals((string) $tokenInfo['user_id'], $sub)) {
            jsonError('Identidade Google inconsistente.', 401);
        }
        return [
            'sub'        => $sub,
            'email'      => $email,
            'avatar_url' => is_string($profile['picture'] ?? null) ? $profile['picture'] : null,
        ];
    }

    if ($idToken !== '') {
        if (strlen($idToken) > 8192) {
            jsonError('Credencial Google inválida.', 401);
        }
        $info = googleHttpJson(
            'https://oauth2.googleapis.com/tokeninfo',
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query(['id_token' => $idToken])
        );
        $aud = (string) ($info['aud'] ?? '');
        $iss = (string) ($info['iss'] ?? '');
        $exp = (int) ($info['exp'] ?? 0);
        $email = mb_strtolower(trim((string) ($info['email'] ?? '')), 'UTF-8');
        $sub = (string) ($info['sub'] ?? '');
        $verified = ($info['email_verified'] ?? false);
        $isVerified = ($verified === true || $verified === 'true');

        if ($aud !== $clientId || !in_array($iss, ['accounts.google.com', 'https://accounts.google.com'], true) || $exp <= time()) {
            jsonError('Credencial Google inválida ou expirada.', 401);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$isVerified || $sub === '') {
            jsonError('Conta Google sem e-mail verificado.', 401);
        }
        return [
            'sub'        => $sub,
            'email'      => $email,
            'avatar_url' => is_string($info['picture'] ?? null) ? $info['picture'] : null,
        ];
    }

    jsonError('Token Google ausente.', 400);
}

function handleGoogleLogin(): never
{
    $pdo = Database::get();
    checkRateLimit($pdo, 'google_login', 15, 900);

    $body = json_decode(file_get_contents('php://input') ?: '', true) ?? [];
    $g = verifyGoogleCredentials($body);

    $token = bin2hex(random_bytes(32));

    // Match existing account by google_sub or email (preserving balance)
    $stmt = $pdo->prepare('SELECT * FROM users WHERE google_sub = ? OR LOWER(email) = ? LIMIT 1');
    $stmt->execute([$g['sub'], $g['email']]);
    $user = $stmt->fetch();

    if ($user) {
        $pdo->prepare('
            UPDATE users
            SET google_sub = ?, avatar_url = ?, token = ?, updated_at = datetime("now")
            WHERE id = ?
        ')->execute([$g['sub'], $g['avatar_url'], $token, $user['id']]);
        $user['avatar_url'] = $g['avatar_url'];

        jsonResponse([
            'success' => true,
            'is_new'  => false,
            'token'   => $token,
            'user'    => formatUserPayload($user),
        ]);
    }

    // Create new account for first-time Google user
    $randomPass = password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
    $pdo->prepare('
        INSERT INTO users (email, password_hash, credits, token, google_sub, avatar_url)
        VALUES (?, ?, 0, ?, ?, ?)
    ')->execute([$g['email'], $randomPass, $token, $g['sub'], $g['avatar_url']]);
    $id = (int) $pdo->lastInsertId();

    jsonResponse([
        'success' => true,
        'is_new'  => true,
        'token'   => $token,
        'user'    => [
            'id'           => $id,
            'email'        => $g['email'],
            'credits'      => 0,
            'display_name' => explode('@', $g['email'])[0],
            'avatar_url'   => $g['avatar_url'],
        ],
    ], 201);
}

function handleLogout(): never
{
    $user = verifyAuthToken();
    $pdo = Database::get();
    $pdo->prepare('UPDATE users SET token = NULL, updated_at = datetime("now") WHERE id = ?')
        ->execute([$user['id']]);

    jsonResponse(['success' => true]);
}

function handleSync(): never
{
    $user = verifyAuthToken();
    jsonResponse([
        'success' => true,
        'user'    => formatUserPayload($user),
    ]);
}
