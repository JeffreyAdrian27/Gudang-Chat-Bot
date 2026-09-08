<?php
/**
 * SAPG — AJAX Endpoint: Tambah Stok Produk
 * POST /modules/produk/add_stok.php
 * Body: { id_produk, jumlah_tambah, csrf_token }
 * Response: JSON { success, message, stok_baru, stok_badge }
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Pastikan login
if (!isLoggedIn()) {
    jsonResponse(false, 'Sesi habis. Silakan login kembali.', ['redirect' => APP_BASE . '/login.php']);
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode tidak diizinkan.');
}

// Validasi CSRF
$csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!validateCsrfToken($csrfToken)) {
    jsonResponse(false, 'Token keamanan tidak valid. Muat ulang halaman.');
}

// Ambil & validasi input
$idProduk     = sanitizeInt($_POST['id_produk']     ?? null, 1);
$jumlahTambah = sanitizeInt($_POST['jumlah_tambah'] ?? null, 1);

if (!$idProduk || !$jumlahTambah) {
    jsonResponse(false, 'Data tidak valid. Pastikan ID produk dan jumlah diisi dengan benar.');
}

if ($jumlahTambah > 999999) {
    jsonResponse(false, 'Jumlah tambahan terlalu besar (maks 999.999).');
}

$db = getDB();

// Cek produk ada & kunci untuk update
try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        "SELECT id_produk, nama_produk, stok FROM produk WHERE id_produk = ? FOR UPDATE"
    );
    $stmt->execute([$idProduk]);
    $produk = $stmt->fetch();

    if (!$produk) {
        $db->rollBack();
        jsonResponse(false, 'Produk tidak ditemukan.');
    }

    $stokLama  = (int) $produk['stok'];
    $stokBaru  = $stokLama + $jumlahTambah;

    // Update stok
    $db->prepare("UPDATE produk SET stok = ? WHERE id_produk = ?")
       ->execute([$stokBaru, $idProduk]);

    // Catat ke stok_log
    $db->prepare(
        "INSERT INTO stok_log (id_produk, jumlah_tambah, stok_sebelum, stok_sesudah) VALUES (?, ?, ?, ?)"
    )->execute([$idProduk, $jumlahTambah, $stokLama, $stokBaru]);

    $db->commit();

    // Kembalikan badge HTML baru
    $stokBadgeHtml = stokBadge($stokBaru);

    jsonResponse(true, 'Stok berhasil diperbarui.', [
        'stok_baru'   => $stokBaru,
        'stok_badge'  => $stokBadgeHtml,
        'nama_produk' => $produk['nama_produk'],
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('[STOK ERROR] ' . $e->getMessage());
    jsonResponse(false, 'Gagal memperbarui stok. Silakan coba lagi.');
}
