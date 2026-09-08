<?php
/**
 * SAPG — Authentication & Security Guards
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

// ─── Session Guard ─────────────────────────────────────────────────────────────

/**
 * Wajibkan login. Redirect ke login jika session tidak aktif.
 */
function requireLogin(): void
{
    if (empty($_SESSION['admin_id'])) {
        // Simpan URL yang diminta agar bisa redirect setelah login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? APP_BASE . '/dashboard.php';
        header('Location: ' . APP_BASE . '/login.php');
        exit;
    }
}

/**
 * Cek apakah admin sudah login.
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

// ─── CSRF Protection ───────────────────────────────────────────────────────────

/**
 * Generate dan simpan CSRF token ke session.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validasi CSRF token dari request. Throw atau return false jika tidak valid.
 */
function validateCsrfToken(string $token): bool
{
    if (
        empty($_SESSION[CSRF_TOKEN_NAME]) ||
        !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)
    ) {
        return false;
    }
    // Rotasi token setelah validasi berhasil
    unset($_SESSION[CSRF_TOKEN_NAME]);
    return true;
}

/**
 * Render hidden input CSRF untuk form HTML.
 */
function csrfInput(): string
{
    $token = generateCsrfToken();
    return sprintf('<input type="hidden" name="%s" value="%s">', CSRF_TOKEN_NAME, $token);
}

// ─── Brute Force Protection ────────────────────────────────────────────────────

/**
 * Cek apakah IP saat ini sedang dalam lockout.
 */
function isLockedOut(): bool
{
    $ip        = getClientIp();
    $attempts  = $_SESSION['login_attempts'][$ip] ?? 0;
    $lockTime  = $_SESSION['login_lockout'][$ip]  ?? 0;

    if ($attempts >= MAX_LOGIN_ATTEMPTS && $lockTime > 0) {
        if ((time() - $lockTime) < LOCKOUT_DURATION) {
            return true;
        }
        // Lockout expired — reset
        unset($_SESSION['login_attempts'][$ip], $_SESSION['login_lockout'][$ip]);
    }
    return false;
}

/**
 * Sisa waktu lockout dalam detik.
 */
function lockoutRemainingSeconds(): int
{
    $ip       = getClientIp();
    $lockTime = $_SESSION['login_lockout'][$ip] ?? 0;
    $remaining = LOCKOUT_DURATION - (time() - $lockTime);
    return max(0, (int) $remaining);
}

/**
 * Catat percobaan login gagal.
 */
function recordFailedAttempt(): void
{
    $ip = getClientIp();
    $_SESSION['login_attempts'][$ip] = ($_SESSION['login_attempts'][$ip] ?? 0) + 1;

    if ($_SESSION['login_attempts'][$ip] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['login_lockout'][$ip] = time();
    }
}

/**
 * Reset percobaan login (setelah berhasil login).
 */
function resetLoginAttempts(): void
{
    $ip = getClientIp();
    unset($_SESSION['login_attempts'][$ip], $_SESSION['login_lockout'][$ip]);
}

/**
 * Dapatkan IP client (dengan deteksi proxy sederhana).
 */
function getClientIp(): string
{
    $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            return trim(explode(',', $_SERVER[$header])[0]);
        }
    }
    return '0.0.0.0';
}
