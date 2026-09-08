<?php
/**
 * SAPG — Edit Produk
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

$stmt = $db->prepare("SELECT * FROM produk WHERE id_produk = ?");
$stmt->execute([$id]);
$produk = $stmt->fetch();

if (!$produk) {
    flashMessage('error', 'Produk tidak ditemukan.');
    header('Location: ' . APP_BASE . '/modules/produk/index.php');
    exit;
}

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
        $namaProduk = trim($_POST['nama_produk']  ?? '');
        $idKategori = sanitizeInt($_POST['id_kategori'] ?? null, 1);
        $stok       = sanitizeInt($_POST['stok']         ?? '0', 0);
        $harga      = sanitizeDecimal($_POST['harga']     ?? '0');

        if (empty($namaProduk))        $errors[] = 'Nama produk wajib diisi.';
        if (strlen($namaProduk) > 150) $errors[] = 'Nama produk maksimal 150 karakter.';
        if (!$idKategori)              $errors[] = 'Pilih kategori yang valid.';
        if ($stok === null)            $errors[] = 'Stok harus >= 0.';
        if ($harga === null)           $errors[] = 'Harga harus >= 0.';

        if (empty($errors)) {
            $db->prepare(
                "UPDATE produk SET nama_produk = ?, id_kategori = ?, stok = ?, harga = ? WHERE id_produk = ?"
            )->execute([$namaProduk, $idKategori, $stok, $harga, $id]);

            flashMessage('success', "Produk berhasil diperbarui.");
            header('Location: ' . APP_BASE . '/modules/produk/index.php');
            exit;
        }
    }
}

// Nilai form (POST > DB)
$f = [
    'nama_produk' => $_POST['nama_produk'] ?? $produk['nama_produk'],
    'id_kategori' => $_POST['id_kategori'] ?? $produk['id_kategori'],
    'stok'        => $_POST['stok']        ?? $produk['stok'],
    'harga'       => $_POST['harga']       ?? $produk['harga'],
];

$pageTitle  = 'Edit Produk';
$activePage = 'produk';
$breadcrumb = [
    ['label' => 'Produk', 'url' => '/modules/produk/index.php'],
    ['label' => 'Edit'],
];

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header__left">
    <h1>Edit Produk</h1>
    <p>Perbarui informasi produk yang sudah ada.</p>
  </div>
  <a href="<?= APP_BASE ?>/modules/produk/index.php" class="btn btn--ghost">← Kembali</a>
</div>

<div class="card" style="max-width: 600px;">

  <?php foreach ($errors as $err): ?>
    <div class="alert alert--error" role="alert"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="POST" action="<?= APP_BASE ?>/modules/produk/edit.php?id=<?= $id ?>" novalidate>
    <?= csrfInput() ?>

    <div class="form-group">
      <label for="nama_produk" class="form-label">
        Nama Produk <span class="required" aria-hidden="true">*</span>
      </label>
      <input type="text" id="nama_produk" name="nama_produk" class="form-control"
             value="<?= e($f['nama_produk']) ?>"
             placeholder="Nama produk" maxlength="150" required autofocus>
    </div>

    <div class="form-group">
      <label for="id_kategori" class="form-label">
        Kategori <span class="required" aria-hidden="true">*</span>
      </label>
      <select id="id_kategori" name="id_kategori" class="form-control" required>
        <option value="">— Pilih Kategori —</option>
        <?php foreach ($kategoris as $kat): ?>
        <option value="<?= $kat['id_kategori'] ?>" <?= $f['id_kategori'] == $kat['id_kategori'] ? 'selected' : '' ?>>
          <?= e($kat['nama_kategori']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="stok" class="form-label">Stok</label>
        <input type="number" id="stok" name="stok" class="form-control"
               value="<?= e($f['stok']) ?>" min="0" required>
        <div class="form-hint">Gunakan fitur "Tambah Stok" di tabel untuk penambahan via AJAX.</div>
      </div>
      <div class="form-group">
        <label for="harga" class="form-label">Harga (Rp)</label>
        <input type="number" id="harga" name="harga" class="form-control"
               value="<?= e($f['harga']) ?>" min="0" step="1" required>
      </div>
    </div>

    <div style="display: flex; gap: var(--sp-3); margin-top: var(--sp-6);">
      <button type="submit" id="btn-update-produk" class="btn btn--primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Simpan Perubahan
      </button>
      <a href="<?= APP_BASE ?>/modules/produk/index.php" class="btn btn--secondary">Batal</a>
    </div>
  </form>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
