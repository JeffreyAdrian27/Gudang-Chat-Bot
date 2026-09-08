<?php
/**
 * SAPG — Tambah Produk
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

requireLogin();

$db = getDB();

// Ambil daftar kategori
$kategoris = $db->query(
    "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC"
)->fetchAll();

$errors = [];

// ─── Process Form ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $errors[] = 'Token keamanan tidak valid.';
    } else {
        $namaProduk  = trim($_POST['nama_produk']  ?? '');
        $idKategori  = sanitizeInt($_POST['id_kategori'] ?? null, 1);
        $stok        = sanitizeInt($_POST['stok']         ?? '0', 0);
        $harga       = sanitizeDecimal($_POST['harga']     ?? '0');

        // Validasi
        if (empty($namaProduk))           $errors[] = 'Nama produk wajib diisi.';
        if (strlen($namaProduk) > 150)    $errors[] = 'Nama produk maksimal 150 karakter.';
        if (!$idKategori)                 $errors[] = 'Pilih kategori yang valid.';
        if ($stok === null)               $errors[] = 'Stok harus berupa angka >= 0.';
        if ($harga === null)              $errors[] = 'Harga harus berupa angka >= 0.';

        // Cek FK kategori
        if ($idKategori && empty($errors)) {
            $cek = $db->prepare("SELECT id_kategori FROM kategori WHERE id_kategori = ?");
            $cek->execute([$idKategori]);
            if (!$cek->fetch()) $errors[] = 'Kategori tidak ditemukan.';
        }

        if (empty($errors)) {
            $db->prepare(
                "INSERT INTO produk (nama_produk, id_kategori, stok, harga) VALUES (?, ?, ?, ?)"
            )->execute([$namaProduk, $idKategori, $stok, $harga]);

            flashMessage('success', "Produk \"" . e($namaProduk) . "\" berhasil ditambahkan.");
            header('Location: ' . APP_BASE . '/modules/produk/index.php');
            exit;
        }
    }
}

$pageTitle  = 'Tambah Produk';
$activePage = 'produk';
$breadcrumb = [
    ['label' => 'Produk', 'url' => '/GudangChatBot/modules/produk/index.php'],
    ['label' => 'Tambah'],
];

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header__left">
    <h1>Tambah Produk</h1>
    <p>Daftarkan produk baru ke inventaris gudang.</p>
  </div>
  <a href="/GudangChatBot/modules/produk/index.php" class="btn btn--ghost">← Kembali</a>
</div>

<div class="card" style="max-width: 600px;">

  <?php foreach ($errors as $err): ?>
    <div class="alert alert--error" role="alert"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="POST" action="<?= APP_BASE ?>/modules/produk/create.php" novalidate>
    <?= csrfInput() ?>

    <div class="form-group">
      <label for="nama_produk" class="form-label">
        Nama Produk <span class="required" aria-hidden="true">*</span>
      </label>
      <input type="text" id="nama_produk" name="nama_produk" class="form-control"
             value="<?= e($_POST['nama_produk'] ?? '') ?>"
             placeholder="Masukkan nama produk…" maxlength="150" required autofocus>
    </div>

    <div class="form-group">
      <label for="id_kategori" class="form-label">
        Kategori <span class="required" aria-hidden="true">*</span>
      </label>
      <select id="id_kategori" name="id_kategori" class="form-control" required>
        <option value="">— Pilih Kategori —</option>
        <?php foreach ($kategoris as $kat): ?>
        <option value="<?= $kat['id_kategori'] ?>"
          <?= ($_POST['id_kategori'] ?? '') == $kat['id_kategori'] ? 'selected' : '' ?>>
          <?= e($kat['nama_kategori']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <?php if (empty($kategoris)): ?>
        <div class="form-hint" style="color: var(--clr-warning);">
          ⚠ Belum ada kategori. <a href="/modules/kategori/create.php">Tambah kategori dulu</a>.
        </div>
      <?php endif; ?>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="stok" class="form-label">
          Stok Awal <span class="required" aria-hidden="true">*</span>
        </label>
        <input type="number" id="stok" name="stok" class="form-control"
               value="<?= e($_POST['stok'] ?? '0') ?>"
               min="0" placeholder="0" required>
      </div>

      <div class="form-group">
        <label for="harga" class="form-label">
          Harga (Rp) <span class="required" aria-hidden="true">*</span>
        </label>
        <input type="number" id="harga" name="harga" class="form-control"
               value="<?= e($_POST['harga'] ?? '0') ?>"
               min="0" step="1" placeholder="0" required>
        <div class="form-hint">Dalam satuan Rupiah.</div>
      </div>
    </div>

    <div style="display: flex; gap: var(--sp-3); margin-top: var(--sp-6);">
      <button type="submit" id="btn-simpan-produk" class="btn btn--primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Simpan Produk
      </button>
      <a href="/modules/produk/index.php" class="btn btn--secondary">Batal</a>
    </div>
  </form>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
