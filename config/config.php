<?php
/**
 * SAPG — Konfigurasi Utama Aplikasi
 * Load environment variables dari .env dan define konstanta global.
 */

declare(strict_types=1);

// ─── Load .env ────────────────────────────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// ─── Helper: ambil env dengan default ─────────────────────────────────────────
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// ─── Timezone & Error Reporting ───────────────────────────────────────────────
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

ini_set('display_errors', env('APP_ENV', 'production') === 'development' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/logs/error.log');
error_reporting(E_ALL);

// ─── Konstanta Database ────────────────────────────────────────────────────────
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'sapg_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// ─── Konstanta Gemini AI ──────────────────────────────────────────────────────
define('GEMINI_API_KEY', env('GEMINI_API_KEY', ''));
define('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent');

// ─── Konstanta Aplikasi ───────────────────────────────────────────────────────
define('APP_NAME',    'SAPG — Sistem Analisis Produk Gudang');
define('APP_VERSION', '1.0.0');

// ─── Base Path ────────────────────────────────────────────────────────────────
// Atur secara hardcode di file .env menggunakan variabel APP_BASE
define('APP_BASE', env('APP_BASE', '/GudangChatBot'));
define('ASSET_BASE', APP_BASE . '/assets');
define('APP_URL',     env('APP_URL', 'http://localhost'));

// ─── Konstanta Keamanan ───────────────────────────────────────────────────────
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION',   900); // 15 menit dalam detik
define('CSRF_TOKEN_NAME',    'csrf_token');
define('SESSION_LIFETIME',   3600); // 1 jam

// ─── Konstanta Chatbot ────────────────────────────────────────────────────────
define('CHAT_MAX_HISTORY_TURNS', 10); // maksimum turn yang dikirim ke Gemini
define('CHAT_MAX_ROWS_FULL',     500); // threshold baris produk sebelum pakai agregasi

// ─── Session Configuration ────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
