<?php
/**
 * SAPG — Database Seeder
 * Jalankan dari CLI: php database/seed.php
 * Akan membuat user admin default dan sample data kategori + produk.
 */

declare(strict_types=1);

// Bootstrap
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

$db = getDB();

echo "=== SAPG Database Seeder ===\n\n";

// ─── Seed Admin User ──────────────────────────────────────────

$username        = 'admin';
$plainPassword   = 'admin';
$hashedPassword  = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $db->prepare("SELECT id_user FROM users WHERE username = ?");
$stmt->execute([$username]);

if ($stmt->fetch()) {
    echo "[SKIP] User '$username' sudah ada.\n";
} else {
    $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'superadmin')")
       ->execute([$username, $hashedPassword]);
    echo "[OK] User '$username' berhasil dibuat (password: $plainPassword)\n";
}

// ─── Seed Kategori ────────────────────────────────────────────

$kategoriList = [
    'Elektronik',
    'Peralatan Rumah Tangga',
    'Makanan & Minuman',
    'Pakaian & Aksesoris',
    'Alat Tulis & Kantor',
    'Kesehatan & Kecantikan',
    'Otomotif',
    'Olahraga',
];

echo "\n[Kategori]\n";
foreach ($kategoriList as $nama) {
    $check = $db->prepare("SELECT id_kategori FROM kategori WHERE nama_kategori = ?");
    $check->execute([$nama]);
    if ($check->fetch()) {
        echo "[SKIP] Kategori '$nama' sudah ada.\n";
    } else {
        $db->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)")->execute([$nama]);
        echo "[OK] Kategori '$nama' ditambahkan.\n";
    }
}

// ─── Seed Sample Produk ───────────────────────────────────────

$stmt   = $db->query("SELECT id_kategori, nama_kategori FROM kategori");
$katMap = [];
foreach ($stmt->fetchAll() as $row) {
    $katMap[$row['nama_kategori']] = $row['id_kategori'];
}

$produkList = [
    ['Laptop Asus VivoBook 15',    'Elektronik',             45,  7_500_000],
    ['Mouse Wireless Logitech',    'Elektronik',             120, 250_000],
    ['Keyboard Mechanical RGB',    'Elektronik',             80,  450_000],
    ['Monitor LED 24 inch',        'Elektronik',             30,  2_800_000],
    ['Headset Gaming Rexus',       'Elektronik',             0,   350_000],
    ['Panci Anti Lengket 28cm',    'Peralatan Rumah Tangga', 60,  185_000],
    ['Blender Philips 2L',         'Peralatan Rumah Tangga', 25,  420_000],
    ['Rice Cooker Cosmos 1.8L',    'Peralatan Rumah Tangga', 40,  320_000],
    ['Mie Instan Goreng (karton)', 'Makanan & Minuman',      200, 120_000],
    ['Air Mineral 600ml (24 pcs)', 'Makanan & Minuman',      500, 45_000],
    ['Kopi Arabika Premium 250g',  'Makanan & Minuman',      8,   85_000],
    ['Kaos Polos Premium Unisex',  'Pakaian & Aksesoris',   150, 95_000],
    ['Celana Chino Pria',          'Pakaian & Aksesoris',   75,  185_000],
    ['Kertas HVS A4 (rim)',        'Alat Tulis & Kantor',   300, 55_000],
    ['Pulpen Pilot G-2 (pak)',     'Alat Tulis & Kantor',   0,   35_000],
    ['Vitamin C 1000mg (60 tab)',  'Kesehatan & Kecantikan', 90, 75_000],
    ['Masker N95 (50 pcs)',        'Kesehatan & Kecantikan', 5,  180_000],
    ['Oli Mesin Motor 1L',         'Otomotif',              70,  65_000],
    ['Helm SNI Full Face',         'Otomotif',              35,  450_000],
    ['Dumbbell Set 10kg',          'Olahraga',              20,  350_000],
    ['Matras Yoga 6mm',            'Olahraga',              3,   125_000],
];

echo "\n[Produk]\n";
foreach ($produkList as [$nama, $katNama, $stok, $harga]) {
    $idKategori = $katMap[$katNama] ?? null;
    if (!$idKategori) {
        echo "[ERROR] Kategori '$katNama' tidak ditemukan, skip '$nama'.\n";
        continue;
    }

    $check = $db->prepare("SELECT id_produk FROM produk WHERE nama_produk = ?");
    $check->execute([$nama]);
    if ($check->fetch()) {
        echo "[SKIP] Produk '$nama' sudah ada.\n";
    } else {
        $db->prepare("INSERT INTO produk (nama_produk, id_kategori, stok, harga) VALUES (?, ?, ?, ?)")
           ->execute([$nama, $idKategori, $stok, $harga]);
        echo "[OK] Produk '$nama' ditambahkan (stok: $stok, harga: Rp " . number_format($harga, 0, ',', '.') . ")\n";
    }
}

echo "\n=== Seeder selesai! ===\n";
echo "Login: admin / admin\n";
