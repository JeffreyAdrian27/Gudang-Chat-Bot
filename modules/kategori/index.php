<?php
/**
 * SAPG — Kelola Kategori (List)
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

requireLogin();

$db = getDB();

// ─── Search & Pagination ───────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$perPage = 15;

$where  = '';
$params = [];
if ($search !== '') {
    $where    = "WHERE nama_kategori LIKE ?";
    $params[] = "%{$search}%";
}

$totalRows   = (int) $db->prepare("SELECT COUNT(*) FROM kategori {$where}")->execute($params) ?
               $db->prepare("SELECT COUNT(*) FROM kategori {$where}")
                  ->execute($params) || true : 0;
// Proper count query
$countStmt = $db->prepare("SELECT COUNT(*) FROM kategori {$where}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$pag  = paginate($totalRows, $perPage);

$stmt = $db->prepare(
    "SELECT k.id_kategori, k.nama_kategori, k.created_at, k.updated_at,
            COUNT(p.id_produk) AS jumlah_produk
     FROM kategori k
     LEFT JOIN produk p ON k.id_kategori = p.id_kategori
     {$where}
     GROUP BY k.id_kategori
     ORDER BY k.nama_kategori ASC
     LIMIT {$pag['limit']} OFFSET {$pag['offset']}"
);
$stmt->execute($params);
$kategoris = $stmt->fetchAll();

// ─── Page Variables ────────────────────────────────────────────────
$pageTitle  = 'Kelola Kategori';
$activePage = 'kategori';
$breadcrumb = [['label' => 'Kategori']];

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header__left">
    <h1>Kelola Kategori</h1>
    <p>Total <strong><?= formatAngka($totalRows) ?></strong> kategori terdaftar.</p>
  </div>
  <a href="<?= APP_BASE ?>/modules/kategori/create.php" id="btn-tambah-kategori" class="btn btn--primary">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Tambah Kategori
  </a>
</div>

<!-- Search -->
<form method="GET" action="<?= APP_BASE ?>/modules/kategori/index.php" class="search-form" role="search">
  <div class="search-input-wrap">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input
      type="text" name="q" id="search-kategori"
      class="form-control"
      placeholder="Cari nama kategori…"
      value="<?= e($search) ?>"
      autocomplete="off"
    >
  </div>
  <button type="submit" class="btn btn--secondary">Cari</button>
  <?php if ($search): ?>
    <a href="<?= APP_BASE ?>/modules/kategori/index.php" class="btn btn--ghost">Reset</a>
  <?php endif; ?>
</form>

<!-- Table -->
<div class="card" style="padding: 0;">
  <div class="table-wrapper">
    <table class="table" aria-label="Daftar kategori">
      <thead>
        <tr>
          <th style="width: 48px;">#</th>
          <th>Nama Kategori</th>
          <th>Jumlah Produk</th>
          <th>Dibuat</th>
          <th>Diperbarui</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($kategoris)): ?>
        <tr>
          <td colspan="6">
            <div class="empty-state">
              <div class="empty-state__icon">🗂️</div>
              <p class="empty-state__title">Tidak ada kategori</p>
              <p class="empty-state__desc"><?= $search ? "Tidak ditemukan untuk \"" . e($search) . "\"." : 'Tambahkan kategori pertama Anda.' ?></p>
            </div>
          </td>
        </tr>
        <?php else: ?>
          <?php $no = $pag['offset']; ?>
          <?php foreach ($kategoris as $kat): $no++; ?>
          <tr>
            <td class="text-muted"><?= $no ?></td>
            <td class="fw-600"><?= e($kat['nama_kategori']) ?></td>
            <td>
              <?php if ($kat['jumlah_produk'] > 0): ?>
                <span class="badge badge--primary"><?= $kat['jumlah_produk'] ?> produk</span>
              <?php else: ?>
                <span class="badge badge--neutral">Kosong</span>
              <?php endif; ?>
            </td>
            <td class="text-muted" style="font-size: var(--fs-xs);"><?= formatTanggal($kat['created_at']) ?></td>
            <td class="text-muted" style="font-size: var(--fs-xs);"><?= formatTanggal($kat['updated_at']) ?></td>
            <td>
              <div style="display: flex; gap: var(--sp-2); justify-content: flex-end;">
                <a href="<?= APP_BASE ?>/modules/kategori/edit.php?id=<?= $kat['id_kategori'] ?>"
                   class="btn btn--sm btn--secondary"
                   id="btn-edit-kat-<?= $kat['id_kategori'] ?>"
                   title="Edit">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </a>
                <a href="<?= APP_BASE ?>/modules/kategori/delete.php?id=<?= $kat['id_kategori'] ?>"
                   class="btn btn--sm btn--danger"
                   id="btn-del-kat-<?= $kat['id_kategori'] ?>"
                   data-confirm="Hapus kategori &quot;<?= e($kat['nama_kategori']) ?>&quot;?<?= $kat['jumlah_produk'] > 0 ? '\n\nPeringatan: Kategori ini masih memiliki ' . $kat['jumlah_produk'] . ' produk!' : '' ?>"
                   title="Hapus">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                  Hapus
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= renderPagination($pag['total_pages'], $pag['current_page'], '/modules/kategori/index.php') ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
