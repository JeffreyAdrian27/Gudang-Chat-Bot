<?php
/**
 * SAPG — Hapus Produk
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

requireLogin();

$db = getDB();

$id = sanitizeInt($_GET['id'] ?? null, 1);
if (!$id) {
    flashMessage('error', 'ID produk tidak valid.');
    header('Location: ' . APP_BASE . '/modules/produk/index.php');
    exit;
}

$stmt = $db->prepare("SELECT nama_produk FROM produk WHERE id_produk = ?");
$stmt->execute([$id]);
$produk = $stmt->fetch();

if (!$produk) {
    flashMessage('error', 'Produk tidak ditemukan.');
    header('Location: ' . APP_BASE . '/modules/produk/index.php');
    exit;
}

// Hapus (stok_log akan terhapus CASCADE otomatis oleh FK)
$db->prepare("DELETE FROM produk WHERE id_produk = ?")->execute([$id]);
flashMessage('success', "Produk \"" . e($produk['nama_produk']) . "\" berhasil dihapus.");
header('Location: ' . APP_BASE . '/modules/produk/index.php');
exit;
