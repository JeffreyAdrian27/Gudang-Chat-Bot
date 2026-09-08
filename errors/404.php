<?php
/**
 * SAPG — 404 Not Found
 * Standalone error page with inline CSS to prevent broken styles when APP_BASE is wrong
 */
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title>404 — Halaman Tidak Ditemukan | SAPG</title>
  <style>
    :root {
      --clr-bg: #0d0f1a;
      --clr-text: #f8fafc;
      --clr-text-2: #94a3b8;
      --clr-primary: #6366f1;
      --clr-surface: #1e2136;
      --font-family: system-ui, -apple-system, sans-serif;
    }
    body {
      margin: 0; padding: 0;
      background-color: var(--clr-bg); color: var(--clr-text); font-family: var(--font-family);
      display: flex; align-items: center; justify-content: center; min-height: 100vh;
    }
    .err-box { text-align: center; max-width: 440px; padding: 40px; }
    .err-code {
      font-size: 110px; font-weight: 900; letter-spacing: -4px;
      background: linear-gradient(135deg, #6366f1, #22d3ee);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text; line-height: 1; margin-bottom: 16px; margin-top: 0;
    }
    .err-title { font-size: 28px; font-weight: 700; margin-bottom: 16px; margin-top: 0; }
    .err-desc { color: var(--clr-text-2); margin-bottom: 40px; line-height: 1.6; }
    .btn-group { display: flex; gap: 12px; justify-content: center; }
    .btn {
      display: inline-flex; align-items: center; justify-content: center;
      padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600;
      text-decoration: none; transition: all 0.2s; cursor: pointer; border: none;
    }
    .btn--primary { background: var(--clr-primary); color: #fff; }
    .btn--primary:hover { background: #4f46e5; transform: translateY(-1px); }
    .btn--secondary { background: rgba(255,255,255,0.05); color: var(--clr-text); border: 1px solid rgba(255,255,255,0.1); }
    .btn--secondary:hover { background: rgba(255,255,255,0.1); }
  </style>
</head>
<body>
  <div class="err-box" role="main">
    <h1 class="err-code" aria-hidden="true">404</h1>
    <h2 class="err-title">Halaman Tidak Ditemukan</h2>
    <p class="err-desc">Oops! Halaman yang Anda cari tidak ada atau mungkin sudah dipindahkan.</p>
    <div class="btn-group">
      <a href="javascript:history.back()" class="btn btn--secondary">← Kembali</a>
      <!-- Gunakan JS fallback untuk beranda jika base path tidak diketahui di server level -->
      <a href="#" onclick="window.location.href = window.location.origin + window.location.pathname.substring(0, window.location.pathname.indexOf('/', 1) + 1);" class="btn btn--primary">Ke Beranda</a>
    </div>
  </div>
</body>
</html>
