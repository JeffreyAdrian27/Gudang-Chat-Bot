<?php
/**
 * SAPG — Logout
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Hapus riwayat chat dari session sebelum destroy
unset($_SESSION['chat_history']);

// Destroy seluruh session
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: ' . APP_BASE . '/login.php');
exit;
