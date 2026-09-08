<?php
/**
 * SAPG — Database Connection (PDO Singleton)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

class Database
{
    private static ?PDO $instance = null;

    /**
     * Kembalikan instance PDO (singleton).
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_PORT,
                DB_NAME
            );

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Log error tanpa expose detail ke output
                error_log('[DB ERROR] ' . $e->getMessage());
                http_response_code(500);
                die(json_encode([
                    'success' => false,
                    'message' => 'Koneksi database gagal. Silakan coba lagi.',
                ]));
            }
        }

        return self::$instance;
    }

    // Prevent instantiation & cloning
    private function __construct() {}
    private function __clone() {}
}

/**
 * Helper shortcut untuk mendapatkan koneksi DB.
 */
function getDB(): PDO
{
    return Database::getInstance();
}
