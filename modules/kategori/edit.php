<?php
/**
 * SAPG — Edit Kategori
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
$stmt = $db->prepare("SELECT * FROM kategori WHERE id_kategori = ?");
$stmt->execute([$id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    flashMessage('error', 'Kategori tidak ditemukan.');
    header('Location: ' . APP_BASE . '/modules/kategori/index.php');
    exit;
}

$errors = [];

// ─── Process Form ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $errors[] = 'Token keamanan tidak valid.';
    } else {
        $namaKategori = trim($_POST['nama_kategori'] ?? '');

        if (empty($namaKategori)) {
            $errors[] = 'Nama kategori wajib diisi.';
        } elseif (strlen($namaKategori) > 100) {
            $errors[] = 'Nama kategori maksimal 100 karakter.';
        } else {
            // Cek duplikat (kecuali diri sendiri)
            $cek = $db->prepare(
                "SELECT id_kategori FROM kategori WHERE LOWER(nama_kategori) = LOWER(?) AND id_kategori != ?"
            );
            $cek->execute([$namaKategori, $id]);
            if ($cek->fetch()) {
                $errors[] = "Kategori \"" . e($namaKategori) . "\" sudah ada.";
            }
        }

        if (empty($errors)) {
            $db->prepare("UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?")
               ->execute([$namaKategori, $id]);
            flashMessage('success', "Kategori berhasil diperbarui.");
            header('Location: ' . APP_BASE . '/modules/kategori/index.php');
            exit;
        }
    }
}

$pageTitle  = 'Edit Kategori';
$activePage = 'kategori';
$breadcrumb = [
    ['label' => 'Kategori', 'url' => '/modules/kategori/index.php'],
    ['label' => 'Edit'],
];

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header__left">
    <h1>Edit Kategori</h1>
    <p>Perbarui nama kategori yang sudah ada.</p>
  </div>
  <a href="/modules/kategori/index.php" class="btn btn--ghost">← Kembali</a>
</div>

<div class="card" style="max-width: 520px;">

  <?php foreach ($errors as $err): ?>
    <div class="alert alert--error" role="alert"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="POST" action="<?= APP_BASE ?>/modules/kategori/edit.php?id=<?= $id ?>" novalidate>
    <?= csrfInput() ?>

    <div class="form-group">
      <label for="nama_kategori" class="form-label">
        Nama Kategori <span class="required" aria-hidden="true">*</span>
      </label>
      <input
        type="text"
        id="nama_kategori"
        name="nama_kategori"
        class="form-control"
        value="<?= e($_POST['nama_kategori'] ?? $kategori['nama_kategori']) ?>"
        placeholder="Nama kategori"
        maxlength="100"
        required
        autofocus
      >
    </div>

    <div style="display: flex; gap: var(--sp-3); margin-top: var(--sp-6);">
      <button type="submit" id="btn-update-kategori" class="btn btn--primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Simpan Perubahan
      </button>
      <a href="/modules/kategori/index.php" class="btn btn--secondary">Batal</a>
    </div>
  </form>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
