<?php
/**
 * SAPG — Hapus Kategori
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
    flashMessage('error', 'ID kategori tidak valid.');
    header('Location: ' . APP_BASE . '/modules/kategori/index.php');
    exit;
}

// Ambil data kategori
$stmt = $db->prepare("SELECT nama_kategori FROM kategori WHERE id_kategori = ?");
$stmt->execute([$id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    flashMessage('error', 'Kategori tidak ditemukan.');
    header('Location: ' . APP_BASE . '/modules/kategori/index.php');
    exit;
}

// Cek apakah masih dipakai produk
$cekProduk = $db->prepare("SELECT COUNT(*) FROM produk WHERE id_kategori = ?");
$cekProduk->execute([$id]);
$jumlahProduk = (int) $cekProduk->fetchColumn();

if ($jumlahProduk > 0) {
    flashMessage(
        'error',
        "Kategori \"" . e($kategori['nama_kategori']) . "\" tidak bisa dihapus karena masih digunakan oleh {$jumlahProduk} produk. Pindahkan atau hapus produk-produk tersebut terlebih dahulu."
    );
    header('Location: ' . APP_BASE . '/modules/kategori/index.php');
    exit;
}

// Hapus
$db->prepare("DELETE FROM kategori WHERE id_kategori = ?")->execute([$id]);
flashMessage('success', "Kategori \"" . e($kategori['nama_kategori']) . "\" berhasil dihapus.");
header('Location: ' . APP_BASE . '/modules/kategori/index.php');
exit;
