<?php
/**
 * SAPG — Dashboard
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$db = getDB();

// ─── Statistik Ringkasan ───────────────────────────────────────────
$totalProduk   = (int) $db->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$totalKategori = (int) $db->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
$stokHabis     = (int) $db->query("SELECT COUNT(*) FROM produk WHERE stok = 0")->fetchColumn();
$stokRendah    = (int) $db->query("SELECT COUNT(*) FROM produk WHERE stok > 0 AND stok < 10")->fetchColumn();

$nilaiStok = (float) $db->query("SELECT COALESCE(SUM(stok * harga), 0) FROM produk")->fetchColumn();

// Harga tertinggi & terendah
$hargaRow = $db->query(
    "SELECT MIN(harga) as min_harga, MAX(harga) as max_harga FROM produk"
)->fetch();

// ─── Produk dengan stok rendah ─────────────────────────────────────
$produkRendah = $db->query(
    "SELECT p.nama_produk, p.stok, p.harga, k.nama_kategori
     FROM produk p
     JOIN kategori k ON p.id_kategori = k.id_kategori
     WHERE p.stok < 10
     ORDER BY p.stok ASC
     LIMIT 8"
)->fetchAll();

// ─── Aktivitas stok terbaru ────────────────────────────────────────
$recentActivity = $db->query(
    "SELECT sl.jumlah_tambah, sl.stok_sesudah, sl.created_at,
            p.nama_produk
     FROM stok_log sl
     JOIN produk p ON sl.id_produk = p.id_produk
     ORDER BY sl.created_at DESC
     LIMIT 8"
)->fetchAll();

// ─── Top 5 produk terlaris (stok terbanyak) ───────────────────────
$topProduk = $db->query(
    "SELECT p.nama_produk, p.stok, p.harga, k.nama_kategori
     FROM produk p
     JOIN kategori k ON p.id_kategori = k.id_kategori
     ORDER BY p.stok DESC
     LIMIT 5"
)->fetchAll();

// ─── Distribusi per kategori ───────────────────────────────────────
$distribusiKat = $db->query(
    "SELECT k.nama_kategori, COUNT(p.id_produk) as jumlah,
            COALESCE(SUM(p.stok), 0) as total_stok
     FROM kategori k
     LEFT JOIN produk p ON k.id_kategori = p.id_kategori
     GROUP BY k.id_kategori, k.nama_kategori
     ORDER BY jumlah DESC
     LIMIT 6"
)->fetchAll();

// ─── Page Variables ────────────────────────────────────────────────
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

require_once __DIR__ . '/includes/header.php';
?>

<!-- ═══ Page Header ═══════════════════════════════════════════════════ -->
<div class="page-header">
  <div class="page-header__left">
    <h1>Dashboard</h1>
    <p>Selamat datang kembali, <strong><?= e($_SESSION['username']) ?></strong> — ini ringkasan data gudang Anda hari ini.</p>
  </div>
  <div style="display:flex;gap:var(--sp-3);">
    <a href="<?= APP_BASE ?>/modules/produk/index.php" class="btn btn--secondary">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      Kelola Produk
    </a>
    <a href="<?= APP_BASE ?>/modules/chatbot/index.php" class="btn btn--primary">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Tanya AI
    </a>
  </div>
</div>

<!-- ═══ Stat Cards ════════════════════════════════════════════════════ -->
<div class="stats-grid">

  <div class="stat-card" style="--card-accent: var(--clr-primary);">
    <div class="stat-card__label">Total Produk</div>
    <div class="stat-card__value"><?= formatAngka($totalProduk) ?></div>
    <div class="stat-card__sub">Item di gudang</div>
    <div class="stat-card__icon" aria-hidden="true">📦</div>
  </div>

  <div class="stat-card" style="--card-accent: var(--clr-accent);">
    <div class="stat-card__label">Total Kategori</div>
    <div class="stat-card__value"><?= formatAngka($totalKategori) ?></div>
    <div class="stat-card__sub">Jenis kategori</div>
    <div class="stat-card__icon" aria-hidden="true">🗂️</div>
  </div>

  <div class="stat-card" style="--card-accent: var(--clr-warning);">
    <div class="stat-card__label">Stok Rendah</div>
    <div class="stat-card__value" style="color: var(--clr-warning);"><?= formatAngka($stokRendah) ?></div>
    <div class="stat-card__sub">Produk (stok &lt; 10)</div>
    <div class="stat-card__icon" aria-hidden="true">⚠️</div>
  </div>

  <div class="stat-card" style="--card-accent: var(--clr-danger);">
    <div class="stat-card__label">Stok Habis</div>
    <div class="stat-card__value" style="color: var(--clr-danger);"><?= formatAngka($stokHabis) ?></div>
    <div class="stat-card__sub">Produk kehabisan stok</div>
    <div class="stat-card__icon" aria-hidden="true">🚨</div>
  </div>

  <div class="stat-card" style="--card-accent: var(--clr-success);">
    <div class="stat-card__label">Nilai Total Stok</div>
    <div class="stat-card__value" style="font-size: var(--fs-xl);"><?= formatRupiah($nilaiStok) ?></div>
    <div class="stat-card__sub">Estimasi nilai inventaris</div>
    <div class="stat-card__icon" aria-hidden="true">💰</div>
  </div>

</div>

<!-- ═══ Content Grid ══════════════════════════════════════════════════ -->
<div class="content-grid">

  <!-- Produk Stok Rendah / Habis -->
  <div>
    <div class="card">
      <div class="card__header">
        <div>
          <h2 class="card__title">⚠️ Peringatan Stok</h2>
          <p class="card__subtitle">Produk yang perlu segera direstok</p>
        </div>
        <a href="<?= APP_BASE ?>/modules/produk/index.php" class="btn btn--ghost btn--sm">Lihat Semua</a>
      </div>

      <?php if (empty($produkRendah)): ?>
        <div class="empty-state" style="padding: var(--sp-8);">
          <div class="empty-state__icon">✅</div>
          <p class="empty-state__title">Semua stok aman</p>
          <p class="empty-state__desc">Tidak ada produk dengan stok di bawah 10.</p>
        </div>
      <?php else: ?>
        <div class="table-wrapper">
          <table class="table" aria-label="Produk stok rendah">
            <thead>
              <tr>
                <th>Produk</th>
                <th>Kategori</th>
                <th>Status Stok</th>
                <th>Harga</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($produkRendah as $p): ?>
              <tr>
                <td class="fw-600"><?= e($p['nama_produk']) ?></td>
                <td><span class="badge badge--neutral"><?= e($p['nama_kategori']) ?></span></td>
                <td><?= stokBadge((int) $p['stok']) ?></td>
                <td class="text-muted"><?= formatRupiah($p['harga']) ?></td>
                <td>
                  <a href="<?= APP_BASE ?>/modules/produk/index.php" class="btn btn--sm btn--secondary">Restok</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Distribusi Kategori -->
    <div class="card mt-6">
      <div class="card__header">
        <div>
          <h2 class="card__title">📊 Distribusi Kategori</h2>
          <p class="card__subtitle">Jumlah produk per kategori</p>
        </div>
      </div>
      <div style="display: flex; flex-direction: column; gap: var(--sp-3);">
        <?php
        $maxJumlah = max(array_column($distribusiKat, 'jumlah') ?: [1]);
        foreach ($distribusiKat as $kat):
          $pct = $maxJumlah > 0 ? round(($kat['jumlah'] / $maxJumlah) * 100) : 0;
        ?>
        <div>
          <div class="flex-between mb-4" style="margin-bottom: 6px;">
            <span style="font-size: var(--fs-sm); font-weight: 500;"><?= e($kat['nama_kategori']) ?></span>
            <span style="font-size: var(--fs-xs); color: var(--clr-text-2);">
              <?= $kat['jumlah'] ?> produk &bull; stok <?= formatAngka((int)$kat['total_stok']) ?>
            </span>
          </div>
          <div style="background: var(--clr-surface-3); border-radius: var(--r-full); height: 6px;">
            <div style="background: linear-gradient(90deg, var(--clr-primary), var(--clr-accent)); height: 6px; border-radius: var(--r-full); width: <?= $pct ?>%; transition: width 0.8s ease;"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Aktivitas Terbaru & AI Quick Access -->
  <div style="display: flex; flex-direction: column; gap: var(--sp-5);">

    <!-- Quick AI Chat -->
    <div class="card" style="background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(34,211,238,0.05)); border-color: rgba(99,102,241,0.2);">
      <div style="text-align: center; padding: var(--sp-2) 0;">
        <div style="font-size: 40px; margin-bottom: var(--sp-3);">🤖</div>
        <h2 class="card__title" style="margin-bottom: var(--sp-2);">Chatbot AI</h2>
        <p style="font-size: var(--fs-sm); color: var(--clr-text-2); margin-bottom: var(--sp-5);">
          Tanya pertanyaan analitis tentang data gudang Anda secara real-time.
        </p>
        <a href="<?= APP_BASE ?>/modules/chatbot/index.php" class="btn btn--primary" style="width: 100%; justify-content: center;">
          Mulai Percakapan →
        </a>
      </div>
    </div>

    <!-- Aktivitas Stok Terbaru -->
    <div class="card" style="flex: 1;">
      <div class="card__header">
        <div>
          <h2 class="card__title">🕐 Aktivitas Terbaru</h2>
          <p class="card__subtitle">Log penambahan stok</p>
        </div>
      </div>

      <?php if (empty($recentActivity)): ?>
        <div class="empty-state" style="padding: var(--sp-6);">
          <div class="empty-state__icon">📋</div>
          <p class="empty-state__title">Belum ada aktivitas</p>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: var(--sp-3);">
          <?php foreach ($recentActivity as $act): ?>
          <div style="display: flex; align-items: flex-start; gap: var(--sp-3); padding: var(--sp-3) 0; border-bottom: 1px solid var(--clr-border);">
            <div style="width: 34px; height: 34px; background: var(--clr-success-bg); border-radius: var(--r-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px;" aria-hidden="true">+</div>
            <div style="flex: 1; min-width: 0;">
              <p style="font-size: var(--fs-sm); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($act['nama_produk']) ?></p>
              <p style="font-size: var(--fs-xs); color: var(--clr-text-2);">
                +<?= formatAngka((int)$act['jumlah_tambah']) ?> unit → stok jadi <?= formatAngka((int)$act['stok_sesudah']) ?>
              </p>
            </div>
            <span style="font-size: 10px; color: var(--clr-text-3); white-space: nowrap; flex-shrink: 0;">
              <?= formatTanggal($act['created_at'], 'd M, H:i') ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
