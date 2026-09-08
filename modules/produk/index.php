<?php
/**
 * SAPG — Kelola Produk (List)
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

requireLogin();

$db = getDB();

// ─── Filter & Search ───────────────────────────────────────────────
$search    = trim($_GET['q']    ?? '');
$filterKat = sanitizeInt($_GET['kat'] ?? '', 0);
$perPage   = 15;

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "p.nama_produk LIKE ?";
    $params[] = "%{$search}%";
}
if ($filterKat) {
    $where[]  = "p.id_kategori = ?";
    $params[] = $filterKat;
}

$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM produk p {$whereStr}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$pag = paginate($totalRows, $perPage);

// Fetch produk
$stmt = $db->prepare(
    "SELECT p.id_produk, p.nama_produk, p.stok, p.harga,
            p.updated_at, k.nama_kategori, k.id_kategori
     FROM produk p
     JOIN kategori k ON p.id_kategori = k.id_kategori
     {$whereStr}
     ORDER BY p.updated_at DESC
     LIMIT {$pag['limit']} OFFSET {$pag['offset']}"
);
$stmt->execute($params);
$produks = $stmt->fetchAll();

// Daftar kategori untuk filter
$allKategoris = $db->query(
    "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC"
)->fetchAll();

// ─── Page Variables ────────────────────────────────────────────────
$pageTitle  = 'Kelola Produk';
$activePage = 'produk';
$breadcrumb = [['label' => 'Produk']];
$extraJs    = ['/assets/js/produk.js'];

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header__left">
    <h1>Kelola Produk</h1>
    <p>Total <strong><?= formatAngka($totalRows) ?></strong> produk<?= $search || $filterKat ? ' (difilter)' : '' ?>.</p>
  </div>
  <a href="/modules/produk/create.php" id="btn-tambah-produk" class="btn btn--primary">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Tambah Produk
  </a>
</div>

<!-- Search & Filter -->
<form method="GET" action="<?= APP_BASE ?>/modules/produk/index.php" class="search-form" role="search">
  <div class="search-input-wrap">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="q" id="search-produk" class="form-control"
           placeholder="Cari nama produk…" value="<?= e($search) ?>" autocomplete="off">
  </div>
  <select name="kat" id="filter-kategori" class="form-control" style="width: auto; min-width: 180px;">
    <option value="">Semua Kategori</option>
    <?php foreach ($allKategoris as $kat): ?>
    <option value="<?= $kat['id_kategori'] ?>" <?= $filterKat == $kat['id_kategori'] ? 'selected' : '' ?>>
      <?= e($kat['nama_kategori']) ?>
    </option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn--secondary">Filter</button>
  <?php if ($search || $filterKat): ?>
    <a href="/modules/produk/index.php" class="btn btn--ghost">Reset</a>
  <?php endif; ?>
</form>

<!-- Table -->
<div class="card" style="padding: 0;">
  <div class="table-wrapper">
    <table class="table" aria-label="Daftar produk">
      <thead>
        <tr>
          <th style="width: 48px;">#</th>
          <th>Nama Produk</th>
          <th>Kategori</th>
          <th>Stok</th>
          <th>Harga</th>
          <th>Update</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody id="produk-tbody">
        <?php if (empty($produks)): ?>
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <div class="empty-state__icon">📦</div>
              <p class="empty-state__title">Tidak ada produk</p>
              <p class="empty-state__desc"><?= $search || $filterKat ? "Tidak ditemukan dengan filter ini." : "Tambahkan produk pertama Anda." ?></p>
            </div>
          </td>
        </tr>
        <?php else: ?>
          <?php $no = $pag['offset']; ?>
          <?php foreach ($produks as $p): $no++; ?>
          <tr id="row-produk-<?= $p['id_produk'] ?>">
            <td class="text-muted"><?= $no ?></td>
            <td class="fw-600"><?= e($p['nama_produk']) ?></td>
            <td><span class="badge badge--neutral"><?= e($p['nama_kategori']) ?></span></td>
            <td id="stok-<?= $p['id_produk'] ?>"><?= stokBadge((int) $p['stok']) ?></td>
            <td class="text-muted"><?= formatRupiah($p['harga']) ?></td>
            <td class="text-muted" style="font-size: var(--fs-xs);"><?= formatTanggal($p['updated_at'], 'd M Y') ?></td>
            <td>
              <div style="display: flex; gap: var(--sp-1); justify-content: flex-end; flex-wrap: nowrap;">
                <!-- Tambah Stok -->
                <button type="button"
                  class="btn btn--sm btn--success btn-tambah-stok"
                  id="btn-stok-<?= $p['id_produk'] ?>"
                  data-id="<?= $p['id_produk'] ?>"
                  data-nama="<?= e($p['nama_produk']) ?>"
                  data-stok="<?= (int) $p['stok'] ?>"
                  title="Tambah Stok"
                  aria-label="Tambah stok <?= e($p['nama_produk']) ?>">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  Stok
                </button>
                <!-- Edit -->
                <a href="/modules/produk/edit.php?id=<?= $p['id_produk'] ?>"
                   class="btn btn--sm btn--secondary"
                   id="btn-edit-produk-<?= $p['id_produk'] ?>">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </a>
                <!-- Hapus -->
                <a href="/modules/produk/delete.php?id=<?= $p['id_produk'] ?>"
                   class="btn btn--sm btn--danger"
                   id="btn-del-produk-<?= $p['id_produk'] ?>"
                   data-confirm="Hapus produk &quot;<?= e($p['nama_produk']) ?>&quot;? Tindakan ini tidak bisa dibatalkan.">
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

<?= renderPagination($pag['total_pages'], $pag['current_page'], '/modules/produk/index.php') ?>

<!-- ═══ Modal Tambah Stok ════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-stok" role="dialog" aria-modal="true" aria-labelledby="modal-stok-title">
  <div class="modal">
    <div class="modal__header">
      <h2 class="modal__title" id="modal-stok-title">➕ Tambah Stok</h2>
      <button type="button" class="modal__close" id="modal-stok-close" aria-label="Tutup modal">&times;</button>
    </div>
    <div class="modal__body">
      <div class="form-group">
        <label class="form-label">Produk</label>
        <input type="text" id="modal-nama-produk" class="form-control" readonly>
      </div>
      <div class="form-group">
        <label class="form-label">Stok Saat Ini</label>
        <input type="text" id="modal-stok-saat-ini" class="form-control" readonly>
      </div>
      <div class="form-group">
        <label for="modal-jumlah-tambah" class="form-label">
          Jumlah Tambahan <span class="required" aria-hidden="true">*</span>
        </label>
        <input
          type="number"
          id="modal-jumlah-tambah"
          name="jumlah_tambah"
          class="form-control"
          min="1" max="999999"
          placeholder="Masukkan jumlah…"
          autofocus
        >
        <div class="form-hint">Jumlah stok yang ingin ditambahkan (positif).</div>
      </div>
      <div id="modal-stok-error" class="alert alert--error" role="alert" style="display:none;"></div>
    </div>
    <div class="modal__footer">
      <button type="button" id="modal-stok-cancel" class="btn btn--secondary">Batal</button>
      <button type="button" id="modal-stok-submit" class="btn btn--primary">
        <span class="btn-text">Tambah Stok</span>
        <span class="btn-loading" style="display:none;"><span class="spinner"></span></span>
      </button>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
