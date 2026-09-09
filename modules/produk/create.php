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

// ─── Helper upload gambar ────────────────────────────────────────────────────
function processImageUpload(array $file): array
{
    // ['filename' => string|null, 'error' => string|null]
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['filename' => null, 'error' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['filename' => null, 'error' => 'Upload gagal (kode: ' . $file['error'] . ').'];
    }

    $maxSize  = 2 * 1024 * 1024; // 2 MB
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    if ($file['size'] > $maxSize) {
        return ['filename' => null, 'error' => 'Ukuran gambar maksimal 2 MB.'];
    }

    // Verifikasi mime type dari isi file (bukan ekstensi)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowed, true)) {
        return ['filename' => null, 'error' => 'Format gambar tidak didukung. Gunakan JPEG, PNG, WebP, atau GIF.'];
    }

    $ext      = $extMap[$mimeType];
    $filename = 'produk_' . bin2hex(random_bytes(12)) . '.' . $ext;
    $destDir  = dirname(__DIR__, 2) . '/assets/public/upload/';
    $destPath = $destDir . $filename;

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['filename' => null, 'error' => 'Gagal menyimpan gambar ke server.'];
    }

    return ['filename' => $filename, 'error' => null];
}

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

        // Proses upload gambar
        $gambarFilename = null;
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = processImageUpload($_FILES['gambar']);
            if ($upload['error']) {
                $errors[] = $upload['error'];
            } else {
                $gambarFilename = $upload['filename'];
            }
        }

        // Cek FK kategori
        if ($idKategori && empty($errors)) {
            $cek = $db->prepare("SELECT id_kategori FROM kategori WHERE id_kategori = ?");
            $cek->execute([$idKategori]);
            if (!$cek->fetch()) $errors[] = 'Kategori tidak ditemukan.';
        }

        if (empty($errors)) {
            $db->prepare(
                "INSERT INTO produk (nama_produk, id_kategori, stok, harga, gambar) VALUES (?, ?, ?, ?, ?)"
            )->execute([$namaProduk, $idKategori, $stok, $harga, $gambarFilename]);

            flashMessage('success', "Produk \"" . e($namaProduk) . "\" berhasil ditambahkan.");
            header('Location: ' . APP_BASE . '/modules/produk/index.php');
            exit;
        }
    }
}

$pageTitle  = 'Tambah Produk';
$activePage = 'produk';
$breadcrumb = [
    ['label' => 'Produk', 'url' => APP_BASE . '/modules/produk/index.php'],
    ['label' => 'Tambah'],
];

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header__left">
    <h1>Tambah Produk</h1>
    <p>Daftarkan produk baru ke inventaris gudang.</p>
  </div>
  <a href="<?= APP_BASE ?>/modules/produk/index.php" class="btn btn--ghost">← Kembali</a>
</div>

<div class="card" style="max-width: 640px;">

  <?php foreach ($errors as $err): ?>
    <div class="alert alert--error" role="alert"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="POST" action="<?= APP_BASE ?>/modules/produk/create.php"
        enctype="multipart/form-data" novalidate>
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
          ⚠ Belum ada kategori. <a href="<?= APP_BASE ?>/modules/kategori/create.php">Tambah kategori dulu</a>.
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

    <!-- ─── Upload Gambar ─── -->
    <div class="form-group">
      <label for="gambar" class="form-label">Gambar Produk</label>
      <div class="img-upload-wrap" id="img-upload-wrap">
        <label for="gambar" class="img-upload-label" id="img-upload-label">
          <div class="img-upload-placeholder" id="img-placeholder">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                 style="opacity:.4;margin-bottom:8px;">
              <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
            <span>Klik untuk pilih gambar</span>
            <span style="font-size:var(--fs-xs);opacity:.5;">JPEG, PNG, WebP, GIF · Maks 2 MB</span>
          </div>
          <img id="img-preview" src="" alt="Preview gambar" style="display:none;max-height:220px;border-radius:8px;object-fit:contain;">
        </label>
        <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp,image/gif"
               style="display:none;" onchange="previewImg(this)">
      </div>
      <div class="form-hint">Opsional. Gambar akan ditampilkan di daftar produk.</div>
    </div>

    <div style="display: flex; gap: var(--sp-3); margin-top: var(--sp-6);">
      <button type="submit" id="btn-simpan-produk" class="btn btn--primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Simpan Produk
      </button>
      <a href="<?= APP_BASE ?>/modules/produk/index.php" class="btn btn--secondary">Batal</a>
    </div>
  </form>
</div>

<style>
.img-upload-wrap { border: 2px dashed rgba(255,255,255,.15); border-radius: 12px; overflow: hidden; transition: border-color .2s; }
.img-upload-wrap:hover { border-color: var(--clr-primary); }
.img-upload-label { display: flex; align-items: center; justify-content: center; min-height: 180px; cursor: pointer; padding: var(--sp-5); }
.img-upload-placeholder { display: flex; flex-direction: column; align-items: center; gap: 4px; color: var(--clr-text-muted); }
</style>

<script>
function previewImg(input) {
  const preview = document.getElementById('img-preview');
  const placeholder = document.getElementById('img-placeholder');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
      preview.style.display = 'block';
      placeholder.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
