<?php
/**
 * SAPG — Landing Page (Public)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header('Location: ' . APP_BASE . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- SEO -->
  <title>SAPG — Sistem Analisis Produk Gudang | Manajemen Stok Cerdas dengan AI</title>
  <meta name="description" content="SAPG adalah sistem manajemen produk gudang berbasis AI. Kelola stok, kategori, dan dapatkan analisis data real-time melalui chatbot AI Gemini yang cerdas.">
  <meta name="keywords" content="gudang, produk, chatbot AI, analisis stok, manajemen inventaris, sistem gudang, chatbot, analisis">
  <meta name="robots" content="index, follow">
  <meta name="author" content="SAPG Team">

  <!-- Open Graph -->
  <meta property="og:title" content="SAPG — Sistem Analisis Produk Gudang">
  <meta property="og:description" content="Kelola inventaris gudang Anda dengan AI. Chatbot cerdas untuk analisis stok dan produk secara real-time.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= APP_URL ?>">

  <!-- Canonical -->
  <link rel="canonical" href="<?= APP_URL ?>">

  <!-- Fonts & CSS -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/landing.css">

  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%236366f1'/><text y='22' x='6' font-size='18' fill='white' font-family='Inter,sans-serif' font-weight='800'>S</text></svg>">
</head>
<body>

<!-- Animated Background -->
<div class="bg-glow" aria-hidden="true">
  <div class="bg-glow__orb"></div>
  <div class="bg-glow__orb"></div>
</div>

<!-- ═══ Navigation ════════════════════════════════════════════════════ -->
<nav class="nav" id="main-nav" aria-label="Navigasi utama">
  <div class="nav__inner">
    <a href="<?= APP_BASE ?>/" class="nav__logo" aria-label="SAPG - Halaman utama">
      <div class="nav__logo-icon" aria-hidden="true">S</div>
      <div>
        <div class="nav__logo-text">SAPG</div>
        <div class="nav__logo-sub">Sistem Analisis Produk Gudang</div>
      </div>
    </a>

    <ul class="nav__links" role="list">
      <li><a href="#fitur">Fitur</a></li>
      <li><a href="#cara-kerja">Cara Kerja</a></li>
      <li><a href="#tentang">Tentang</a></li>
    </ul>

    <a href="<?= APP_BASE ?>/login.php" class="nav__cta" id="nav-login-btn">Masuk ke Sistem →</a>
  </div>
</nav>

<!-- ═══ Hero ══════════════════════════════════════════════════════════ -->
<section class="hero" aria-labelledby="hero-title">

  <div>
    <div class="hero__badge">
      <span class="hero__badge-dot" aria-hidden="true"></span>
      Didukung Google Gemini AI
    </div>

    <h1 id="hero-title" class="hero__title">
      Analisis Gudang Anda<br>dengan <span class="gradient-text">Kecerdasan AI</span>
    </h1>

    <p class="hero__desc">
      SAPG memadukan manajemen inventaris tradisional dengan chatbot AI berbasis data real-time.
      Tanyakan apa saja tentang stok dan produk — dapatkan jawaban instan dan akurat.
    </p>

    <div class="hero__actions">
      <a href="<?= APP_BASE ?>/login.php" class="btn-hero-primary" id="hero-cta-btn">
        🚀 Mulai Sekarang
      </a>
      <a href="#fitur" class="btn-hero-secondary">
        Lihat Fitur ↓
      </a>
    </div>

    <!-- Dashboard Preview -->
    <div class="hero__visual" aria-hidden="true">
      <div class="hero__dashboard-preview">
        <div class="hero__preview-bar">
          <div class="hero__preview-dot"></div>
          <div class="hero__preview-dot"></div>
          <div class="hero__preview-dot"></div>
          <span class="hero__preview-title">SAPG Dashboard</span>
        </div>

        <!-- Stat Mini Cards -->
        <div class="hero__stats-row">
          <div class="hero__stat-mini">
            <div class="hero__stat-value">1.2K</div>
            <div class="hero__stat-label">Total Produk</div>
          </div>
          <div class="hero__stat-mini">
            <div class="hero__stat-value">24</div>
            <div class="hero__stat-label">Kategori</div>
          </div>
          <div class="hero__stat-mini">
            <div class="hero__stat-value">8</div>
            <div class="hero__stat-label">Stok Rendah</div>
          </div>
          <div class="hero__stat-mini">
            <div class="hero__stat-value">Rp 2.4M</div>
            <div class="hero__stat-label">Nilai Stok</div>
          </div>
        </div>

        <!-- Chat Preview -->
        <div class="hero__chat-preview">
          <div class="hero__chat-msg">
            <div class="hero__chat-avatar">🤖</div>
            <div class="hero__chat-bubble">Selamat datang! Saya siap menjawab pertanyaan tentang data gudang Anda.</div>
          </div>
          <div class="hero__chat-msg user">
            <div class="hero__chat-avatar" style="background: rgba(255,255,255,0.1);">A</div>
            <div class="hero__chat-bubble">Berapa harga produk termurah saat ini?</div>
          </div>
          <div class="hero__chat-msg">
            <div class="hero__chat-avatar">🤖</div>
            <div class="hero__chat-bubble">Produk termurah adalah <strong>Pulpen Pilot G-2</strong> dengan harga <strong>Rp 35.000</strong>.</div>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ═══ Features ══════════════════════════════════════════════════════ -->
<section class="section section--center" id="fitur" aria-labelledby="fitur-title">
  <p class="section__label">Fitur Unggulan</p>
  <h2 id="fitur-title" class="section__title">Semua yang Anda Butuhkan untuk Kelola Gudang</h2>
  <p class="section__desc">
    Dari manajemen produk dasar hingga analisis AI canggih, SAPG menyediakan semua alat dalam satu platform.
  </p>

  <div class="features-grid">

    <div class="feature-card">
      <div class="feature-card__icon" style="background: rgba(99,102,241,0.15);" aria-hidden="true">🤖</div>
      <h3 class="feature-card__title">Chatbot AI Gemini</h3>
      <p class="feature-card__desc">
        Tanya pertanyaan analitis dalam bahasa natural. AI menjawab berdasarkan data real dari database gudang Anda — bukan data generik.
      </p>
    </div>

    <div class="feature-card">
      <div class="feature-card__icon" style="background: rgba(16,185,129,0.15);" aria-hidden="true">📦</div>
      <h3 class="feature-card__title">Manajemen Produk</h3>
      <p class="feature-card__desc">
        CRUD produk lengkap dengan kategori, harga, dan stok. Tambah stok via modal AJAX tanpa reload halaman.
      </p>
    </div>

    <div class="feature-card">
      <div class="feature-card__icon" style="background: rgba(245,158,11,0.15);" aria-hidden="true">⚡</div>
      <h3 class="feature-card__title">Real-time Analytics</h3>
      <p class="feature-card__desc">
        Dashboard dengan statistik langsung dari database: total produk, nilai stok, peringatan stok rendah, dan distribusi kategori.
      </p>
    </div>

    <div class="feature-card">
      <div class="feature-card__icon" style="background: rgba(34,211,238,0.15);" aria-hidden="true">🔒</div>
      <h3 class="feature-card__title">Keamanan Enterprise</h3>
      <p class="feature-card__desc">
        PDO Prepared Statement, CSRF protection, rate limiting login, session security, dan perlindungan dari SQL Injection & XSS.
      </p>
    </div>

    <div class="feature-card">
      <div class="feature-card__icon" style="background: rgba(239,68,68,0.15);" aria-hidden="true">📊</div>
      <h3 class="feature-card__title">RAG (Smart Retrieval)</h3>
      <p class="feature-card__desc">
        AI hanya mengambil data relevan berdasarkan pertanyaan — bukan seluruh database. Hemat kuota API, respons lebih cepat.
      </p>
    </div>

    <div class="feature-card">
      <div class="feature-card__icon" style="background: rgba(168,85,247,0.15);" aria-hidden="true">💬</div>
      <h3 class="feature-card__title">Riwayat Percakapan</h3>
      <p class="feature-card__desc">
        AI mengingat konteks percakapan sebelumnya. Follow-up question berjalan mulus tanpa mengulang informasi.
      </p>
    </div>

  </div>
</section>

<!-- ═══ How It Works ══════════════════════════════════════════════════ -->
<section class="section" id="cara-kerja" aria-labelledby="cara-kerja-title">
  <p class="section__label">Cara Kerja</p>
  <h2 id="cara-kerja-title" class="section__title">Mudah Digunakan,<br>Cerdas di Balik Layar</h2>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 32px; margin-top: 60px;">

    <div style="text-align: center;">
      <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(99,102,241,0.15); border: 2px solid rgba(99,102,241,0.3); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 24px;" aria-hidden="true">1</div>
      <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">Login</h3>
      <p style="font-size: 14px; color: rgba(226,232,240,0.55);">Masuk ke panel admin yang aman dengan autentikasi berbasis session.</p>
    </div>

    <div style="text-align: center;">
      <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(34,211,238,0.15); border: 2px solid rgba(34,211,238,0.3); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 24px;" aria-hidden="true">2</div>
      <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">Kelola Data</h3>
      <p style="font-size: 14px; color: rgba(226,232,240,0.55);">Tambah, edit, dan hapus kategori & produk. Update stok secara real-time.</p>
    </div>

    <div style="text-align: center;">
      <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(16,185,129,0.15); border: 2px solid rgba(16,185,129,0.3); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 24px;" aria-hidden="true">3</div>
      <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">Analisis dengan AI</h3>
      <p style="font-size: 14px; color: rgba(226,232,240,0.55);">Tanya chatbot AI tentang data gudang Anda — dapatkan jawaban akurat seketika.</p>
    </div>

  </div>
</section>

<!-- ═══ About / Tentang ═══════════════════════════════════════════════ -->
<section class="section" id="tentang" aria-labelledby="tentang-title">
  <div style="max-width: 640px;">
    <p class="section__label">Tentang SAPG</p>
    <h2 id="tentang-title" class="section__title">Sistem Analisis Produk Gudang</h2>
    <p class="section__desc" style="max-width: none;">
      SAPG dibangun untuk membantu admin gudang mengelola inventaris dengan lebih efisien.
      Menggunakan teknologi PHP Native, MySQL, dan Google Gemini AI, sistem ini memberikan
      kemampuan analisis data yang sebelumnya hanya tersedia di platform enterprise besar —
      kini bisa dijalankan di server lokal maupun cloud.
    </p>
    <div style="margin-top: 32px; display: flex; gap: 24px; flex-wrap: wrap;">
      <div>
        <p style="font-size: 28px; font-weight: 800; color: var(--clr-primary);">PHP 8.1+</p>
        <p style="font-size: 13px; color: rgba(226,232,240,0.5);">Backend</p>
      </div>
      <div>
        <p style="font-size: 28px; font-weight: 800; color: var(--clr-accent);">MySQL</p>
        <p style="font-size: 13px; color: rgba(226,232,240,0.5);">Database</p>
      </div>
      <div>
        <p style="font-size: 28px; font-weight: 800; background: linear-gradient(135deg, var(--clr-primary), var(--clr-accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Gemini AI</p>
        <p style="font-size: 13px; color: rgba(226,232,240,0.5);">Intelligence</p>
      </div>
    </div>
  </div>
</section>

<!-- ═══ CTA ═══════════════════════════════════════════════════════════ -->
<section class="cta-section">
  <div class="cta-box">
    <h2 class="cta-box__title">Siap Mengelola Gudang Anda dengan Lebih Cerdas?</h2>
    <p class="cta-box__desc">Masuk sekarang dan mulai gunakan SAPG untuk analisis inventaris berbasis AI.</p>
    <a href="<?= APP_BASE ?>/login.php" class="btn-hero-primary" style="display: inline-flex;" id="cta-login-btn">
      🚀 Masuk ke Panel Admin
    </a>
  </div>
</section>

<!-- ═══ Footer ════════════════════════════════════════════════════════ -->
<footer class="footer" role="contentinfo">
  <p>© <?= date('Y') ?> SAPG — Sistem Analisis Produk Gudang. Dibuat dengan ❤️ menggunakan PHP & Gemini AI.</p>
</footer>

<script>
// Navbar scroll effect
const nav = document.getElementById('main-nav');
window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 50);
}, { passive: true });
</script>

</body>
</html>
