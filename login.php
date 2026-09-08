<?php
/**
 * SAPG — Login Page
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header('Location: ' . APP_BASE . 'GudangChatBot/dashboard.php');
    exit;
}

$errors  = [];
$oldUser = '';

// ─── Process Login Form ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $errors[] = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    }

    // Cek lockout
    elseif (isLockedOut()) {
        $remaining = lockoutRemainingSeconds();
        $menit     = ceil($remaining / 60);
        $errors[]  = "Terlalu banyak percobaan login. Coba lagi dalam {$menit} menit.";
    }

    else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password']        ?? '';
        $oldUser  = $username;

        if (empty($username) || empty($password)) {
            $errors[] = 'Username dan password wajib diisi.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare(
                "SELECT id_user, username, password, role FROM users WHERE username = ? AND is_active = 1 LIMIT 1"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login berhasil
                session_regenerate_id(true);
                resetLoginAttempts();

                $_SESSION['admin_id']  = $user['id_user'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['login_at']  = time();

                // Redirect ke halaman sebelumnya atau dashboard
                $redirect = $_SESSION['redirect_after_login'] ?? APP_BASE . '/dashboard.php';
                
                // Tambahan: proteksi jika session lama menyimpan fallback salah '/dashboard.php'
                if ($redirect === '/dashboard.php') {
                    $redirect = APP_BASE . '/dashboard.php';
                }

                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            } else {
                recordFailedAttempt();
                $attemptsLeft = MAX_LOGIN_ATTEMPTS - ($_SESSION['login_attempts'][getClientIp()] ?? 0);
                $attemptsLeft = max(0, $attemptsLeft);

                $errors[] = 'Username atau password salah.' .
                    ($attemptsLeft > 0 && $attemptsLeft <= 2
                        ? " Sisa percobaan: {$attemptsLeft}."
                        : '');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Login SAPG — Sistem Analisis Produk Gudang</title>
  <meta name="description" content="Masuk ke panel admin SAPG">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/style.css">

  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%236366f1'/><text y='22' x='6' font-size='18' fill='white' font-family='Inter,sans-serif' font-weight='800'>S</text></svg>">

  <style>
    /* Login-specific overrides */
    body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }

    .login-bg {
      position: fixed; inset: 0; z-index: 0; overflow: hidden;
    }
    .login-bg__orb {
      position: absolute; border-radius: 50%; filter: blur(120px); opacity: 0.12;
    }
    .login-bg__orb:nth-child(1) {
      width: 500px; height: 500px;
      background: var(--clr-primary);
      top: -150px; right: -150px;
    }
    .login-bg__orb:nth-child(2) {
      width: 350px; height: 350px;
      background: var(--clr-accent);
      bottom: -80px; left: -80px;
    }

    .login-wrap {
      position: relative; z-index: 1;
      width: 100%; max-width: 400px;
      padding: var(--sp-4);
    }

    .login-card {
      background: rgba(20,22,37,0.85);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: var(--r-xl);
      padding: var(--sp-8);
      box-shadow: 0 32px 80px rgba(0,0,0,0.5);
    }

    .login-logo {
      display: flex; flex-direction: column; align-items: center;
      margin-bottom: var(--sp-8);
    }

    .login-logo__icon {
      width: 56px; height: 56px;
      background: linear-gradient(135deg, var(--clr-primary), var(--clr-accent));
      border-radius: 16px;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px; font-weight: 900; color: #fff;
      box-shadow: 0 8px 32px rgba(99,102,241,0.4);
      margin-bottom: var(--sp-4);
    }

    .login-logo__title {
      font-size: var(--fs-xl); font-weight: 800;
      letter-spacing: -0.5px;
    }

    .login-logo__sub {
      font-size: var(--fs-sm); color: var(--clr-text-2);
      margin-top: 4px;
    }

    .login-divider {
      text-align: center;
      font-size: var(--fs-xs); color: var(--clr-text-3);
      margin: var(--sp-6) 0 var(--sp-4);
      position: relative;
    }
    .login-divider::before {
      content: '';
      position: absolute; top: 50%; left: 0; right: 0;
      height: 1px; background: var(--clr-border);
    }
    .login-divider span {
      position: relative; background: var(--clr-surface);
      padding: 0 var(--sp-3);
    }

    .lockout-info {
      font-size: var(--fs-xs); color: var(--clr-text-3);
      text-align: center; margin-top: var(--sp-4);
    }
  </style>
</head>
<body>

<!-- Animated Background -->
<div class="login-bg" aria-hidden="true">
  <div class="login-bg__orb"></div>
  <div class="login-bg__orb"></div>
</div>

<!-- Login Card -->
<div class="login-wrap">
  <div class="login-card" role="main">

    <!-- Logo -->
    <div class="login-logo">
      <div class="login-logo__icon" aria-hidden="true">S</div>
      <h1 class="login-logo__title">SAPG</h1>
      <p class="login-logo__sub">Sistem Analisis Produk Gudang</p>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
    <div class="alert alert--error" role="alert">
      <?= e($errors[0]) ?>
    </div>
    <?php endif; ?>

    <!-- Lockout Warning -->
    <?php if (isLockedOut()): ?>
    <div class="alert alert--warning" role="alert">
      <strong>Akun sementara terkunci.</strong>
      Tunggu <?= ceil(lockoutRemainingSeconds() / 60) ?> menit sebelum mencoba lagi.
    </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="<?= APP_BASE ?>/login.php" id="login-form" novalidate>
      <?= csrfInput() ?>

      <div class="form-group">
        <label for="username" class="form-label">
          Username <span class="required" aria-hidden="true">*</span>
        </label>
        <input
          type="text"
          id="username"
          name="username"
          class="form-control"
          value="<?= e($oldUser) ?>"
          placeholder="Masukkan username"
          autocomplete="username"
          required
          <?= isLockedOut() ? 'disabled' : '' ?>
        >
      </div>

      <div class="form-group">
        <label for="password" class="form-label">
          Password <span class="required" aria-hidden="true">*</span>
        </label>
        <div style="position: relative;">
          <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
            <?= isLockedOut() ? 'disabled' : '' ?>
            style="padding-right: 44px;"
          >
          <button type="button" id="toggle-pass"
            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--clr-text-3);cursor:pointer;padding:4px;"
            aria-label="Tampilkan/sembunyikan password">
            👁
          </button>
        </div>
      </div>

      <button
        type="submit"
        id="btn-login"
        class="btn btn--primary w-full mt-4"
        style="justify-content: center; height: 48px; font-size: var(--fs-md);"
        <?= isLockedOut() ? 'disabled' : '' ?>
      >
        Masuk
      </button>

    </form>

    <div class="lockout-info">
      Terkunci setelah <?= MAX_LOGIN_ATTEMPTS ?> percobaan gagal.
      Hubungi superadmin jika butuh bantuan.
    </div>

  </div><!-- /.login-card -->
</div><!-- /.login-wrap -->

<script>
  // Toggle password visibility
  const toggleBtn = document.getElementById('toggle-pass');
  const passInput = document.getElementById('password');
  if (toggleBtn && passInput) {
    toggleBtn.addEventListener('click', () => {
      const isPass = passInput.type === 'password';
      passInput.type = isPass ? 'text' : 'password';
      toggleBtn.textContent = isPass ? '🙈' : '👁';
    });
  }

  // Loading state on submit
  const loginForm = document.getElementById('login-form');
  const loginBtn  = document.getElementById('btn-login');
  if (loginForm && loginBtn) {
    loginForm.addEventListener('submit', () => {
      loginBtn.disabled = true;
      loginBtn.innerHTML = '<span class="spinner"></span> Memverifikasi…';
    });
  }

  // Auto-focus username
  const usernameEl = document.getElementById('username');
  if (usernameEl && !usernameEl.disabled) usernameEl.focus();
</script>

</body>
</html>
