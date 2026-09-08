<?php
/**
 * SAPG — Generate fresh CSRF token (JSON endpoint untuk JS)
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(false, 'Unauthorized.');
}

header('Content-Type: application/json');
echo json_encode(['token' => generateCsrfToken()]);
exit;
