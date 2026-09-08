<?php
try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS sapg_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo 'Database sapg_db siap.' . PHP_EOL;

    // Import schema
    $sql = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    $pdo->exec('USE sapg_db');
    $pdo->exec($sql);
    echo 'Schema berhasil diimport.' . PHP_EOL;

} catch(PDOException $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
