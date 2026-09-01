<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Database connection singleton + schema bootstrap.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $dir = dirname(DB_PATH);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA journal_mode=WAL');
            $pdo->exec('PRAGMA foreign_keys=ON');

            self::bootstrap($pdo);
            self::$instance = $pdo;
        }

        return self::$instance;
    }

    private static function bootstrap(PDO $pdo): void
    {
        // Users Table (credits stores BRL balance in cents, e.g., 1000 = R$ 10.00)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                email          TEXT UNIQUE NOT NULL,
                password_hash  TEXT NOT NULL,
                credits        INTEGER NOT NULL DEFAULT 0,
                token          TEXT,
                created_at     TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at     TEXT NOT NULL DEFAULT (datetime('now'))
            )
        ");

        // Transactions Table (MP payments)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS transactions (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id        INTEGER NOT NULL,
                mp_payment_id  TEXT UNIQUE,
                package_label  TEXT,
                amount_brl     REAL,
                credits_added  INTEGER NOT NULL DEFAULT 0,
                status         TEXT NOT NULL DEFAULT 'pending',
                created_at     TEXT NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // SMS Orders Table (5sim orders)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sms_orders (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id        INTEGER NOT NULL,
                fivesim_id     TEXT UNIQUE NOT NULL,
                phone          TEXT NOT NULL,
                country        TEXT NOT NULL,
                product        TEXT NOT NULL,
                cost_cents     INTEGER NOT NULL DEFAULT 0,
                sms_code       TEXT,
                status         TEXT NOT NULL DEFAULT 'pending', -- pending, received, canceled
                created_at     TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at     TEXT NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
}

/**
 * Helper: send JSON response and exit.
 */
function jsonResponse(mixed $data, int $status = 200): never
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Helper: send error JSON response and exit.
 */
function jsonError(string $message, int $status = 400): never
{
    jsonResponse(['error' => $message], $status);
}

/**
 * Helper: verify custom Auth token from header or GET parameter.
 */
function verifyAuthToken(): array
{
    $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    $token   = str_replace('Bearer ', '', trim($auth));

    if (empty($token) && !empty($_GET['token'])) {
        $token = trim($_GET['token']);
    }

    if (empty($token)) {
        jsonError('Sessão não identificada. Por favor, faça login novamente no aplicativo.', 401);
    }

    $pdo = Database::get();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonError('Sessão inválida ou expirada. Por favor, faça login novamente.', 401);
    }

    return $user;
}
