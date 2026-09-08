<?php
/**
 * SAPG — 403 Forbidden
 * Standalone error page with inline CSS to prevent broken styles when APP_BASE is wrong
 */
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title>403 — Akses Ditolak | SAPG</title>
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
    .btn {
      display: inline-flex; align-items: center; justify-content: center;
      padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600;
      text-decoration: none; transition: all 0.2s; cursor: pointer;
      background: var(--clr-primary); color: #fff; border: none;
    }
    .btn:hover { background: #4f46e5; transform: translateY(-1px); }
  </style>
</head>
<body>
  <div class="err-box" role="main">
    <h1 class="err-code" aria-hidden="true">403</h1>
    <h2 class="err-title">Akses Ditolak</h2>
    <p class="err-desc">Anda tidak memiliki izin untuk mengakses direktori atau halaman ini pada server.</p>
    <a href="javascript:history.back()" class="btn">← Kembali</a>
  </div>
</body>
</html>
