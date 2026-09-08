<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

$db = getDB();
echo '=== Database Verification ===' . PHP_EOL;

$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo 'Tables: ' . implode(', ', $tables) . PHP_EOL . PHP_EOL;

$totalUser   = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalKat    = $db->query('SELECT COUNT(*) FROM kategori')->fetchColumn();
$totalProduk = $db->query('SELECT COUNT(*) FROM produk')->fetchColumn();
$stokHabis   = $db->query('SELECT COUNT(*) FROM produk WHERE stok = 0')->fetchColumn();
$nilaiStok   = $db->query('SELECT SUM(stok * harga) FROM produk')->fetchColumn();

echo 'Users      : ' . $totalUser . PHP_EOL;
echo 'Kategori   : ' . $totalKat . PHP_EOL;
echo 'Produk     : ' . $totalProduk . PHP_EOL;
echo 'Stok habis : ' . $stokHabis . PHP_EOL;
echo 'Nilai stok : Rp ' . number_format((float)$nilaiStok, 0, ',', '.') . PHP_EOL;

$admin = $db->query("SELECT username, role FROM users WHERE username = 'admin'")->fetch();
echo PHP_EOL . 'Admin: ' . $admin['username'] . ' (role: ' . $admin['role'] . ')' . PHP_EOL;

$hash = $db->query("SELECT password FROM users WHERE username = 'admin'")->fetchColumn();
echo 'Password verify: ' . (password_verify('admin', $hash) ? 'OK' : 'FAIL') . PHP_EOL;
echo PHP_EOL . '=== SEMUA OK ===' . PHP_EOL;
