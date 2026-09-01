<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Authentication API (VirtualSim)
 * POST /api/auth.php?action=register  → Create user
 * POST /api/auth.php?action=login     → Authenticate
 * GET  /api/auth.php                  → Sync / Get user info
 */

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'sync';

if ($method === 'POST') {
    if ($action === 'register') {
        handleRegister();
    } elseif ($action === 'login') {
        handleLogin();
    } else {
        jsonError('Invalid action.', 400);
    }
} elseif ($method === 'GET') {
    handleSync();
} else {
    jsonError('Method not allowed.', 405);
}

function handleRegister(): never {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $email    = trim($body['email'] ?? '');
    $password = trim($body['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Por favor, informe um e-mail válido.');
    }
    if (strlen($password) < 6) {
        jsonError('Senha deve ter pelo menos 6 caracteres.');
    }

    $pdo = Database::get();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));

    try {
        // Create user with 0 starting credits (R$ 0,00 balance)
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, token, credits) VALUES (?, ?, ?, 0)');
        $stmt->execute([$email, $hash, $token]);
        $id = $pdo->lastInsertId();
        
        jsonResponse([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'           => (int)$id,
                'email'        => $email,
                'credits'      => 0,
                'display_name' => explode('@', $email)[0]
            ]
        ], 201);
    } catch (Exception $e) {
        jsonError('Este e-mail já está cadastrado.');
    }
}

function handleLogin(): never {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $email    = trim($body['email'] ?? '');
    $password = trim($body['password'] ?? '');

    if (!$email || !$password) {
        jsonError('Preencha e-mail e senha.');
    }

    $pdo = Database::get();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonError('E-mail ou senha incorretos.', 401);
    }

    $token = bin2hex(random_bytes(32));
    $pdo->prepare('UPDATE users SET token = ? WHERE id = ?')->execute([$token, $user['id']]);

    jsonResponse([
        'success' => true,
        'token'   => $token,
        'user'    => [
            'id'           => (int)$user['id'],
            'email'        => $user['email'],
            'credits'      => (int)$user['credits'],
            'display_name' => explode('@', $user['email'])[0]
        ]
    ]);
}

function handleSync(): never {
    $user = verifyAuthToken();
    jsonResponse([
        'success' => true,
        'user'    => [
            'id'           => (int)$user['id'],
            'email'        => $user['email'],
            'credits'      => (int)$user['credits'],
            'display_name' => explode('@', $user['email'])[0]
        ]
    ]);
}
