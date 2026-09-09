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

// ─── Helper upload gambar ────────────────────────────────────────────────────
function processImageUpload(array $file): array
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['filename' => null, 'error' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['filename' => null, 'error' => 'Upload gagal (kode: ' . $file['error'] . ').'];
    }

    $maxSize  = 2 * 1024 * 1024;
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    if ($file['size'] > $maxSize) {
        return ['filename' => null, 'error' => 'Ukuran gambar maksimal 2 MB.'];
    }

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

function deleteProductImage(?string $filename): void
{
    if (!$filename) return;
    $path = dirname(__DIR__, 2) . '/assets/public/upload/' . $filename;
    if (file_exists($path)) {
        @unlink($path);
    }
}

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
        $hapusGambar = !empty($_POST['hapus_gambar']);

        if (empty($namaProduk))        $errors[] = 'Nama produk wajib diisi.';
        if (strlen($namaProduk) > 150) $errors[] = 'Nama produk maksimal 150 karakter.';
        if (!$idKategori)              $errors[] = 'Pilih kategori yang valid.';
        if ($stok === null)            $errors[] = 'Stok harus >= 0.';
        if ($harga === null)           $errors[] = 'Harga harus >= 0.';

        // Proses upload gambar baru
        $gambarFilename = $produk['gambar']; // default: gambar lama
        $uploadDone = false;

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = processImageUpload($_FILES['gambar']);
            if ($upload['error']) {
                $errors[] = $upload['error'];
            } else {
                $gambarFilename = $upload['filename'];
                $uploadDone = true;
            }
        }

        if (empty($errors)) {
            // Hapus gambar lama jika ada gambar baru atau user ceklis hapus gambar
            if ($hapusGambar && !$uploadDone) {
                deleteProductImage($produk['gambar']);
                $gambarFilename = null;
            } elseif ($uploadDone && $produk['gambar']) {
                deleteProductImage($produk['gambar']);
            }

            $db->prepare(
                "UPDATE produk SET nama_produk = ?, id_kategori = ?, stok = ?, harga = ?, gambar = ? WHERE id_produk = ?"
            )->execute([$namaProduk, $idKategori, $stok, $harga, $gambarFilename, $id]);

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
    'gambar'      => $produk['gambar'],
];

$uploadDir = APP_BASE . '/assets/public/upload/';

$pageTitle  = 'Edit Produk';
$activePage = 'produk';
$breadcrumb = [
    ['label' => 'Produk', 'url' => APP_BASE . '/modules/produk/index.php'],
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

<div class="card" style="max-width: 640px;">

  <?php foreach ($errors as $err): ?>
    <div class="alert alert--error" role="alert"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="POST" action="<?= APP_BASE ?>/modules/produk/edit.php?id=<?= $id ?>"
        enctype="multipart/form-data" novalidate>
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

    <!-- ─── Gambar Produk ─── -->
    <div class="form-group">
      <label class="form-label">Gambar Produk</label>

      <?php if ($f['gambar']): ?>
      <!-- Tampilkan gambar saat ini -->
      <div style="margin-bottom:var(--sp-3);">
        <p style="font-size:var(--fs-xs);color:var(--clr-text-muted);margin-bottom:var(--sp-2);">Gambar saat ini:</p>
        <img id="img-current"
             src="<?= APP_BASE . '/assets/public/upload/' . e($f['gambar']) ?>"
             alt="Gambar produk"
             style="max-height:180px;border-radius:8px;object-fit:contain;border:1px solid rgba(255,255,255,.1);">
      </div>
      <label style="display:flex;align-items:center;gap:var(--sp-2);margin-bottom:var(--sp-3);cursor:pointer;font-size:var(--fs-sm);">
        <input type="checkbox" name="hapus_gambar" id="hapus_gambar" value="1"
               onchange="toggleHapusGambar(this)">
        Hapus gambar ini
      </label>
      <?php endif; ?>

      <div class="img-upload-wrap" id="img-upload-wrap">
        <label for="gambar" class="img-upload-label" id="img-upload-label">
          <div class="img-upload-placeholder" id="img-placeholder">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                 style="opacity:.4;margin-bottom:8px;">
              <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
            <span><?= $f['gambar'] ? 'Ganti gambar (opsional)' : 'Klik untuk pilih gambar (opsional)' ?></span>
            <span style="font-size:var(--fs-xs);opacity:.5;">JPEG, PNG, WebP, GIF · Maks 2 MB</span>
          </div>
          <img id="img-preview" src="" alt="Preview gambar baru" style="display:none;max-height:180px;border-radius:8px;object-fit:contain;">
        </label>
        <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp,image/gif"
               style="display:none;" onchange="previewImg(this)">
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

<style>
.img-upload-wrap { border: 2px dashed rgba(255,255,255,.15); border-radius: 12px; overflow: hidden; transition: border-color .2s; }
.img-upload-wrap:hover { border-color: var(--clr-primary); }
.img-upload-label { display: flex; align-items: center; justify-content: center; min-height: 140px; cursor: pointer; padding: var(--sp-4); }
.img-upload-placeholder { display: flex; flex-direction: column; align-items: center; gap: 4px; color: var(--clr-text-muted); }
</style>

<script>
function previewImg(input) {
  const preview     = document.getElementById('img-preview');
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
function toggleHapusGambar(cb) {
  const current = document.getElementById('img-current');
  if (current) current.style.opacity = cb.checked ? '0.3' : '1';
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
