<?php
/**
 * SAPG – Tambah Produk (dengan upload gambar)
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

// Konfigurasi Upload
$uploadDir          = dirname(__DIR__, 2) . '/assets/public/upload/';
$uploadMaxSize      = 2 * 1024 * 1024;
$uploadAllowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$uploadAllowedExt   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

function handleImageUpload(array $file, array &$errors, string $uploadDir, int $maxSize, array $allowedMimes, array $allowedExt): ?string
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Gagal mengunggah gambar (kode error: ' . $file['error'] . ').';
        return null;
    }
    if ($file['size'] > $maxSize) {
        $errors[] = 'Ukuran gambar maksimal 2 MB.';
        return null;
    }
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, $allowedMimes, true)) {
        $errors[] = 'Tipe file tidak didukung. Gunakan JPG, PNG, WebP, atau GIF.';
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        $errors[] = 'Ekstensi file tidak diizinkan.';
        return null;
    }
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $newName = 'produk_' . uniqid('', true) . '.' . $ext;
    $dest    = $uploadDir . $newName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $errors[] = 'Gagal menyimpan file gambar ke server.';
        return null;
    }
    return $newName;
}

// Process Form
$gambarBaru = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $errors[] = 'Token keamanan tidak valid.';
    } else {
        $namaProduk  = trim($_POST['nama_produk']  ?? '');
        $idKategori  = sanitizeInt($_POST['id_kategori'] ?? null, 1);
        $stok        = sanitizeInt($_POST['stok']         ?? '0', 0);
        $harga       = sanitizeDecimal($_POST['harga']     ?? '0');

        if (empty($namaProduk))           $errors[] = 'Nama produk wajib diisi.';
        if (strlen($namaProduk) > 150)    $errors[] = 'Nama produk maksimal 150 karakter.';
        if (!$idKategori)                 $errors[] = 'Pilih kategori yang valid.';
        if ($stok === null)               $errors[] = 'Stok harus berupa angka >= 0.';
        if ($harga === null)              $errors[] = 'Harga harus berupa angka >= 0.';

        if ($idKategori && empty($errors)) {
            $cek = $db->prepare("SELECT id_kategori FROM kategori WHERE id_kategori = ?");
            $cek->execute([$idKategori]);
            if (!$cek->fetch()) $errors[] = 'Kategori tidak ditemukan.';
        }

        // Handle upload gambar (proses bahkan saat ada error agar validasi file langsung terlihat)
        if (isset($_FILES['gambar'])) {
            $gambarBaru = handleImageUpload($_FILES['gambar'], $errors, $uploadDir, $uploadMaxSize, $uploadAllowedMimes, $uploadAllowedExt);
        }

        if (empty($errors)) {
            $db->prepare(
                "INSERT INTO produk (nama_produk, id_kategori, stok, harga, gambar) VALUES (?, ?, ?, ?, ?)"
            )->execute([$namaProduk, $idKategori, $stok, $harga, $gambarBaru]);

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
  <a href="<?= APP_BASE ?>/modules/produk/index.php" class="btn btn--ghost">&#8592; Kembali</a>
</div>

<div class="card" style="max-width: 640px;">

  <?php foreach ($errors as $err): ?>
    <div class="alert alert--error" role="alert"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="POST" action="<?= APP_BASE ?>/modules/produk/create.php" enctype="multipart/form-data" novalidate>
    <?= csrfInput() ?>

    <div class="form-group">
      <label for="nama_produk" class="form-label">
        Nama Produk <span class="required" aria-hidden="true">*</span>
      </label>
      <input type="text" id="nama_produk" name="nama_produk" class="form-control"
             value="<?= e($_POST['nama_produk'] ?? '') ?>"
             placeholder="Masukkan nama produk..." maxlength="150" required autofocus>
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
          Belum ada kategori. <a href="<?= APP_BASE ?>/modules/kategori/create.php">Tambah kategori dulu</a>.
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

    <!-- Upload Gambar -->
    <div class="form-group">
      <label for="gambar" class="form-label">Gambar Produk</label>
      <div class="upload-area" id="upload-area">
        <input type="file" id="gambar" name="gambar" class="upload-input"
               accept="image/jpeg,image/png,image/webp,image/gif">
        <div class="upload-placeholder" id="upload-placeholder">
          <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="1.5"
               stroke-linecap="round" stroke-linejoin="round" style="opacity:.45;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
          </svg>
          <p style="margin:0;font-size:.85rem;color:var(--clr-text-2);">Klik atau drag gambar ke sini</p>
          <p style="margin:4px 0 0;font-size:.75rem;color:var(--clr-text-3);">JPG, PNG, WebP, GIF &middot; Maks 2 MB</p>
        </div>
        <img id="upload-preview" src="" alt="Preview gambar produk"
             style="display:none;max-height:200px;max-width:100%;border-radius:8px;object-fit:contain;margin:auto;display:block;">
        <button type="button" id="upload-remove" style="display:none;margin-top:8px;" class="btn btn--sm btn--ghost">
          &#10005; Hapus Gambar
        </button>
      </div>
      <div class="form-hint">Opsional. Gambar ditampilkan di daftar produk dan chatbot.</div>
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
.upload-area {
  border: 2px dashed var(--clr-border-2);
  border-radius: 12px;
  padding: 28px 20px;
  text-align: center;
  cursor: pointer;
  transition: border-color .2s, background .2s;
  position: relative;
  background: var(--clr-surface-2);
  min-height: 120px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.upload-area:hover, .upload-area.dragover {
  border-color: var(--clr-primary);
  background: rgba(99,102,241,.06);
}
.upload-input {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}
#upload-remove { position: relative; z-index: 2; }
</style>

<script>
(function () {
  const input       = document.getElementById('gambar');
  const preview     = document.getElementById('upload-preview');
  const placeholder = document.getElementById('upload-placeholder');
  const removeBtn   = document.getElementById('upload-remove');
  const area        = document.getElementById('upload-area');

  function showPreview(file) {
    if (!file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      preview.style.display = 'block';
      placeholder.style.display = 'none';
      removeBtn.style.display = 'inline-flex';
    };
    reader.readAsDataURL(file);
  }

  function clearPreview() {
    preview.style.display = 'none';
    placeholder.style.display = 'block';
    removeBtn.style.display = 'none';
    input.value = '';
  }

  input?.addEventListener('change', () => showPreview(input.files[0]));
  removeBtn?.addEventListener('click', e => { e.stopPropagation(); clearPreview(); });

  area?.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('dragover'); });
  area?.addEventListener('dragleave', () => area.classList.remove('dragover'));
  area?.addEventListener('drop', e => {
    e.preventDefault();
    area.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
      try {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
      } catch(err) {}
      showPreview(file);
    }
  });
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
