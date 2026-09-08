<?php
/**
 * SAPG — Global Header / Sidebar
 * Sertakan setelah requireLogin() di setiap halaman admin.
 *
 * Variabel yang bisa diset sebelum require:
 *   $pageTitle    string  Judul halaman (default: 'Dashboard')
 *   $breadcrumb   array   [['label'=>'…','url'=>'…'], …] (opsional)
 *   $activePage   string  'dashboard'|'kategori'|'produk'|'chatbot'
 *   $extraCss     array   Path CSS tambahan
 *   $extraJs      array   Path JS tambahan
 */

declare(strict_types=1);

$pageTitle  = $pageTitle  ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';
$breadcrumb = $breadcrumb ?? [];

$username     = e($_SESSION['username'] ?? 'Admin');
$roleLabel    = ucfirst($_SESSION['role'] ?? 'admin');
$avatarLetter = strtoupper(substr($username, 0, 1));

// Base path helper
$B = APP_BASE; // shorthand, e.g. '/Gudang Chat Bot'
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($pageTitle) ?> — SAPG</title>
  <meta name="description" content="Sistem Analisis Produk Gudang — Panel Admin">

  <!-- Preconnect Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- CSS -->
  <link rel="stylesheet" href="<?= $B ?>/assets/css/style.css">
  <?php if (isset($extraCss)): ?>
    <?php foreach ((array) $extraCss as $css): ?>
    <link rel="stylesheet" href="<?= $B . e($css) ?>">
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- CSRF Token for JS/AJAX -->
  <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
  <!-- APP_BASE for JS -->
  <meta name="app-base" content="<?= $B ?>">

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%236366f1'/><text y='22' x='6' font-size='18' fill='white' font-family='Inter,sans-serif' font-weight='800'>S</text></svg>">
</head>
<body>

<!-- ═══ App Layout ═══════════════════════════════════════════════════ -->
<div class="app-layout">

  <!-- ─── Sidebar ─────────────────────────────────────────────── -->
  <aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu utama">

    <!-- Brand -->
    <div class="sidebar__brand">
      <a href="<?= $B ?>/dashboard.php" style="display:flex;align-items:center;gap:12px;text-decoration:none;">
        <div class="sidebar__logo" aria-hidden="true">S</div>
        <div class="sidebar__brand-text">
          <div class="sidebar__brand-name">SAPG</div>
          <div class="sidebar__brand-sub">Sistem Analisis Produk Gudang</div>
        </div>
      </a>
    </div>

    <!-- Nav Links -->
    <nav class="sidebar__nav">

      <div class="sidebar__section-label">Menu</div>

      <a href="<?= $B ?>/dashboard.php"
         class="sidebar__link <?= $activePage === 'dashboard' ? 'active' : '' ?>"
         id="nav-dashboard"
         aria-current="<?= $activePage === 'dashboard' ? 'page' : 'false' ?>">
        <span class="sidebar__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
          </svg>
        </span>
        <span class="sidebar__link-text">Dashboard</span>
      </a>

      <div class="sidebar__section-label">Manajemen</div>

      <a href="<?= $B ?>/modules/kategori/index.php"
         class="sidebar__link <?= $activePage === 'kategori' ? 'active' : '' ?>"
         id="nav-kategori">
        <span class="sidebar__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
          </svg>
        </span>
        <span class="sidebar__link-text">Kategori</span>
      </a>

      <a href="<?= $B ?>/modules/produk/index.php"
         class="sidebar__link <?= $activePage === 'produk' ? 'active' : '' ?>"
         id="nav-produk">
        <span class="sidebar__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          </svg>
        </span>
        <span class="sidebar__link-text">Produk</span>
      </a>

      <div class="sidebar__section-label">AI</div>

      <a href="<?= $B ?>/modules/chatbot/index.php"
         class="sidebar__link <?= $activePage === 'chatbot' ? 'active' : '' ?>"
         id="nav-chatbot">
        <span class="sidebar__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            <circle cx="9" cy="10" r="1" fill="currentColor"/><circle cx="12" cy="10" r="1" fill="currentColor"/><circle cx="15" cy="10" r="1" fill="currentColor"/>
          </svg>
        </span>
        <span class="sidebar__link-text">Chatbot AI</span>
      </a>

    </nav>

    <!-- Sidebar Footer / Logout -->
    <div class="sidebar__footer">
      <a href="<?= $B ?>/logout.php"
         id="nav-logout"
         class="sidebar__link"
         onclick="return confirm('Yakin ingin logout?')"
         style="color: var(--clr-danger);">
        <span class="sidebar__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
        </span>
        <span class="sidebar__link-text">Logout</span>
      </a>
    </div>

  </aside>
  <!-- ─────────────────────────────────────────────────────────── -->

  <!-- ─── Main Content ─────────────────────────────────────────── -->
  <div class="main-content" id="main-content">

    <!-- Top Bar -->
    <header class="topbar" role="banner">
      <button class="topbar__toggle" id="sidebar-toggle" aria-label="Toggle sidebar" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>

      <!-- Breadcrumb -->
      <nav class="topbar__breadcrumb" aria-label="Breadcrumb">
        <a href="<?= $B ?>/dashboard.php">SAPG</a>
        <?php foreach ($breadcrumb as $crumb): ?>
          <span class="topbar__breadcrumb-sep" aria-hidden="true">›</span>
          <?php if (isset($crumb['url'])): ?>
            <?php
              $cUrl = $crumb['url'];
              if (!str_starts_with($cUrl, 'http://') && !str_starts_with($cUrl, 'https://') && $B !== '' && !str_starts_with($cUrl, $B)) {
                  $cUrl = $B . '/' . ltrim($cUrl, '/');
              }
            ?>
            <a href="<?= e($cUrl) ?>"><?= e($crumb['label']) ?></a>
          <?php else: ?>
            <span class="topbar__breadcrumb-current"><?= e($crumb['label']) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (empty($breadcrumb)): ?>
          <span class="topbar__breadcrumb-sep" aria-hidden="true">›</span>
          <span class="topbar__breadcrumb-current"><?= e($pageTitle) ?></span>
        <?php endif; ?>
      </nav>

      <!-- User Info -->
      <div class="topbar__user" aria-label="Info pengguna">
        <div class="topbar__avatar" aria-hidden="true"><?= $avatarLetter ?></div>
        <span class="topbar__username"><?= $username ?></span>
        <span class="topbar__role"><?= e($roleLabel) ?></span>
      </div>
    </header>
    <!-- ─────────────────────────────────────────────────────────── -->

    <!-- Page Content starts here -->
    <main class="page-wrapper" id="main" role="main">

      <?php
      // Tampilkan flash message jika ada
      $flash = getFlash();
      if ($flash):
      ?>
      <div class="alert alert--<?= e($flash['type']) ?>" role="alert" id="flash-msg">
        <?= e($flash['message']) ?>
        <button class="alert__close" onclick="this.parentElement.remove()" aria-label="Tutup">&times;</button>
      </div>
      <?php endif; ?>
