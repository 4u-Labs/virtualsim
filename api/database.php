<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Database connection singleton + schema bootstrap + auth helpers.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $dir = dirname(DB_PATH);
            if (!is_dir($dir)) {
                mkdir($dir, 0700, true);
            }

            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA busy_timeout = 5000');
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA foreign_keys = ON');

            self::bootstrap($pdo);
            self::$instance = $pdo;
        }

        return self::$instance;
    }

    private static function bootstrap(PDO $pdo): void
    {
        // Users Table (credits stores BRL balance in cents, e.g. 2500 = R$ 25,00)
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

        // Transactions Table (Mercado Pago PIX payments)
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
                status         TEXT NOT NULL DEFAULT 'pending',
                created_at     TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at     TEXT NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Safe schema migrations for Google Sign-In columns
        $cols = array_column($pdo->query('PRAGMA table_info(users)')->fetchAll(), 'name');
        if (!in_array('google_sub', $cols, true)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN google_sub TEXT');
            $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_users_google_sub ON users(google_sub) WHERE google_sub IS NOT NULL');
        }
        if (!in_array('avatar_url', $cols, true)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN avatar_url TEXT');
        }

        // Rate-limiting table for login/register brute-force protection
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS auth_rate_limits (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                ip          TEXT NOT NULL,
                action      TEXT NOT NULL,
                created_at  INTEGER NOT NULL
            )
        ");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_auth_rate_ip_time ON auth_rate_limits(ip, action, created_at)");
    }
}

function jsonResponse(mixed $data, int $status = 200): never
{
    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message, int $status = 400): never
{
    jsonResponse(['error' => $message], $status);
}

function getClientIp(): string
{
    $cf = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
    if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
        return $cf;
    }
    $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($xff !== '') {
        $first = trim(explode(',', $xff)[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function checkRateLimit(PDO $pdo, string $action, int $maxAttempts = 10, int $windowSeconds = 900): void
{
    $ip = getClientIp();
    $cutoff = time() - $windowSeconds;
    $pdo->prepare('DELETE FROM auth_rate_limits WHERE created_at < ?')->execute([$cutoff]);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM auth_rate_limits WHERE ip = ? AND action = ? AND created_at >= ?');
    $stmt->execute([$ip, $action, $cutoff]);
    if ((int) $stmt->fetchColumn() >= $maxAttempts) {
        jsonError('Muitas tentativas em sequência. Aguarde alguns minutos e tente novamente.', 429);
    }
}

function recordRateLimitFailure(PDO $pdo, string $action): void
{
    $pdo->prepare('INSERT INTO auth_rate_limits (ip, action, created_at) VALUES (?, ?, ?)')
        ->execute([getClientIp(), $action, time()]);
}

/**
 * Verify Bearer auth token strictly from Authorization header (never query string).
 */
function verifyAuthToken(): array
{
    $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
    $auth = $headers['Authorization']
        ?? $headers['authorization']
        ?? $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    $token = '';
    if (preg_match('/^Bearer\s+(\S+)$/i', trim($auth), $m)) {
        $token = $m[1];
    }

    if ($token === '') {
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
