<?php
/**
 * SAPG - Chatbot AI UI
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

requireLogin();

// Handle "Chat Baru"
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['chat_history']);
    header('Location: ' . APP_BASE . '/modules/chatbot/index.php');
    exit;
}

$chatHistory = $_SESSION['chat_history'] ?? [];

$pageTitle  = 'Chatbot AI';
$activePage = 'chatbot';
$breadcrumb = [['label' => 'Chatbot AI']];
$extraCss   = ['/assets/css/chat.css'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($pageTitle) ?> - SAPG</title>
  <meta name="description" content="Chatbot AI untuk analisis data gudang">
  <meta name="csrf-token" content="<?= generateCsrfToken() ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/chat.css">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%236366f1'/><text y='22' x='6' font-size='18' fill='white' font-family='Inter,sans-serif' font-weight='800'>S</text></svg>">
</head>
<body>

<div class="app-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu utama">
    <div class="sidebar__brand">
      <div class="sidebar__logo" aria-hidden="true">S</div>
      <div class="sidebar__brand-text">
        <div class="sidebar__brand-name">SAPG</div>
        <div class="sidebar__brand-sub">Sistem Analisis Produk Gudang</div>
      </div>
    </div>
    <nav class="sidebar__nav">
      <div class="sidebar__section-label">Menu</div>
      <a href="<?= APP_BASE ?>/dashboard.php" class="sidebar__link" id="nav-dashboard">
        <span class="sidebar__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg></span>
        <span class="sidebar__link-text">Dashboard</span>
      </a>
      <div class="sidebar__section-label">Manajemen</div>
      <a href="<?= APP_BASE ?>/modules/kategori/index.php" class="sidebar__link" id="nav-kategori">
        <span class="sidebar__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></span>
        <span class="sidebar__link-text">Kategori</span>
      </a>
      <a href="<?= APP_BASE ?>/modules/produk/index.php" class="sidebar__link" id="nav-produk">
        <span class="sidebar__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></span>
        <span class="sidebar__link-text">Produk</span>
      </a>
      <div class="sidebar__section-label">AI</div>
      <a href="<?= APP_BASE ?>/modules/chatbot/index.php" class="sidebar__link active" id="nav-chatbot" aria-current="page">
        <span class="sidebar__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><circle cx="9" cy="10" r="1" fill="currentColor"/><circle cx="12" cy="10" r="1" fill="currentColor"/><circle cx="15" cy="10" r="1" fill="currentColor"/></svg></span>
        <span class="sidebar__link-text">Chatbot AI</span>
      </a>
    </nav>
    <div class="sidebar__footer">
      <a href="<?= APP_BASE ?>/logout.php" id="nav-logout" class="sidebar__link" onclick="return confirm('Yakin logout?')" style="color:var(--clr-danger);">
        <span class="sidebar__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
        <span class="sidebar__link-text">Logout</span>
      </a>
    </div>
  </aside>

  <div class="main-content" id="main-content">

    <!-- Topbar -->
    <header class="topbar" role="banner">
      <button class="topbar__toggle" id="sidebar-toggle" aria-label="Toggle sidebar" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <nav class="topbar__breadcrumb" aria-label="Breadcrumb">
        <a href="<?= APP_BASE ?>/dashboard.php">SAPG</a>
        <span class="topbar__breadcrumb-sep" aria-hidden="true">&rsaquo;</span>
        <span class="topbar__breadcrumb-current">Chatbot AI</span>
      </nav>
      <div style="display:flex;gap:var(--sp-3);align-items:center;">
        <a href="<?= APP_BASE ?>/modules/chatbot/index.php?reset=1" class="btn btn--ghost btn--sm" title="Mulai percakapan baru">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/></svg>
          Chat Baru
        </a>
        <div class="topbar__user">
          <div class="topbar__avatar" aria-hidden="true"><?= strtoupper(substr(e($_SESSION['username'] ?? 'U'), 0, 1)) ?></div>
          <span class="topbar__username"><?= e($_SESSION['username'] ?? 'User') ?></span>
        </div>
      </div>
    </header>

    <!-- Chat Layout -->
    <div class="page-wrapper" style="display:flex;flex-direction:column;height:calc(100vh - 64px);padding: var(--sp-5) var(--sp-8) var(--sp-8);">

      <div class="chat-layout" style="flex:1;display:flex;flex-direction:column;">

        <!-- Chat Header -->
        <div class="chat-header">
          <div class="chat-header__avatar" aria-hidden="true">AI</div>
          <div class="chat-header__info">
            <div class="chat-header__name">SAPG AI Assistant</div>
            <div class="chat-header__status">
              <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--clr-success);"></span>
              Siap menjawab pertanyaan analitis
            </div>
          </div>
        </div>

        <!-- Chat Messages Area -->
        <div class="chat-messages" id="chat-messages" role="log" aria-live="polite">

          <?php if (empty($chatHistory)): ?>
          <!-- Welcome Screen -->
          <div class="chat-welcome" id="chat-welcome">
            <div class="chat-welcome__icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><circle cx="9" cy="10" r="1" fill="currentColor"/><circle cx="12" cy="10" r="1" fill="currentColor"/><circle cx="15" cy="10" r="1" fill="currentColor"/></svg>
            </div>
            <h2 class="chat-welcome__title">SAPG AI Assistant</h2>
            <p class="chat-welcome__desc">
              Tanyakan apa saja seputar data stok, harga, dan pergerakan produk gudang Anda. Klik rekomendasi di bawah untuk mulai langsung.
            </p>
            <div class="chat-suggestions">
              <button type="button" class="chat-suggestion-btn" data-q="Produk termurah apa yang ada di gudang?">
                Produk termurah apa?
              </button>
              <button type="button" class="chat-suggestion-btn" data-q="Produk apa saja yang stoknya habis atau menipis?">
                Produk yang stoknya habis?
              </button>
              <button type="button" class="chat-suggestion-btn" data-q="Kategori apa yang memiliki jumlah produk paling sedikit?">
                Kategori dengan produk paling sedikit?
              </button>
              <button type="button" class="chat-suggestion-btn" data-q="Berapa total nilai estimasi stok gudang keseluruhan?">
                Berapa total nilai stok gudang?
              </button>
              <button type="button" class="chat-suggestion-btn" data-q="Tampilkan 5 produk dengan stok terbanyak di gudang.">
                5 produk stok terbanyak
              </button>
              <button type="button" class="chat-suggestion-btn" data-q="Berapa jumlah total produk dan total kategori yang terdaftar?">
                Total produk dan kategori
              </button>
            </div>
          </div>

          <?php else: ?>
          <!-- Existing Chat History -->
            <?php foreach ($chatHistory as $msg): ?>
              <?php if ($msg['role'] === 'user'): ?>
              <div class="chat-msg chat-msg--user">
                <div class="chat-msg__avatar" aria-hidden="true">
                  <?= strtoupper(substr(e($_SESSION['username'] ?? 'U'), 0, 1)) ?>
                </div>
                <div>
                  <div class="chat-msg__bubble"><?= nl2br(e($msg['text'])) ?></div>
                  <div class="chat-msg__time" style="text-align:right;"><?= isset($msg['time']) ? e($msg['time']) : '' ?></div>
                </div>
              </div>
              <?php elseif ($msg['role'] === 'model'): ?>
              <div class="chat-msg chat-msg--ai">
                <div class="chat-msg__avatar" aria-hidden="true">AI</div>
                <div>
                  <div class="chat-msg__bubble"><?= nl2br(e($msg['text'])) ?></div>
                  <div class="chat-msg__time"><?= isset($msg['time']) ? e($msg['time']) : '' ?></div>
                </div>
              </div>
              <?php endif; ?>
            <?php endforeach; ?>

            <!-- Render follow-up suggestions for existing history -->
            <div class="chat-msg chat-msg--ai chat-post-suggestions-wrap" style="margin-top:4px;">
              <div class="chat-msg__avatar" aria-hidden="true">AI</div>
              <div style="max-width: 100%;">
                <div class="chat-post-label">Pertanyaan lanjutan yang bisa Anda pilih:</div>
                <div class="chat-post-suggestions">
                  <button type="button" class="chat-suggestion-chip" data-q="Cek produk dengan stok menipis">Cek produk dengan stok menipis</button>
                  <button type="button" class="chat-suggestion-chip" data-q="Produk termahal dan termurah">Produk termahal dan termurah</button>
                  <button type="button" class="chat-suggestion-chip" data-q="Ringkasan stok per kategori">Ringkasan stok per kategori</button>
                  <button type="button" class="chat-suggestion-chip" data-q="Total nilai estimasi aset gudang">Total nilai estimasi aset gudang</button>
                  <button type="button" class="chat-suggestion-chip" data-q="Tampilkan 5 produk dengan stok terbanyak">Tampilkan 5 produk dengan stok terbanyak</button>
                </div>
              </div>
            </div>
          <?php endif; ?>

        </div>
        <!-- /.chat-messages -->

        <!-- Input Area -->
        <div class="chat-input-area">
          <form class="chat-input-form" id="chat-form" novalidate>
            <div class="chat-input-wrap">
              <textarea
                id="chat-input"
                name="message"
                placeholder="Tanya sesuatu tentang data gudang... (Enter kirim, Shift+Enter baris baru)"
                rows="1"
                maxlength="2000"
                autocomplete="off"
                aria-label="Pesan untuk AI"
              ></textarea>
            </div>
            <button type="submit" id="chat-send-btn" class="chat-send-btn" aria-label="Kirim pesan" disabled>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
          </form>
          <p class="chat-input-hint">
            Jawaban AI didasarkan pada data real dari database gudang Anda.
          </p>
        </div>

      </div><!-- /.chat-layout -->

    </div><!-- /.page-wrapper -->
  </div><!-- /.main-content -->
</div><!-- /.app-layout -->

<script src="<?= APP_BASE ?>/assets/js/main.js"></script>
<script>
// Chat UI Logic
(function () {
  'use strict';

  const chatForm     = document.getElementById('chat-form');
  const chatInput    = document.getElementById('chat-input');
  const sendBtn      = document.getElementById('chat-send-btn');
  const messagesArea = document.getElementById('chat-messages');
  const welcomeDiv   = document.getElementById('chat-welcome');
  const csrfMeta     = document.querySelector('meta[name="csrf-token"]');

  // Input auto-resize & send-button toggle
  chatInput?.addEventListener('input', () => {
    sendBtn.disabled = !chatInput.value.trim();
    chatInput.style.height = 'auto';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 160) + 'px';
  });

  // Enter to send, Shift+Enter for new line
  chatInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (!sendBtn.disabled) chatForm.dispatchEvent(new Event('submit'));
    }
  });

  // Helper function to send question directly
  function sendQuestion(question) {
    if (!question) return;
    chatInput.value = question;
    sendBtn.disabled = false;
    chatForm.dispatchEvent(new Event('submit'));
  }

  // Bind click handler for welcome suggestion buttons
  document.querySelectorAll('.chat-suggestion-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      sendQuestion(btn.dataset.q || btn.textContent.trim());
    });
  });

  // Bind click handler for any initial suggestion chips in HTML
  document.querySelectorAll('.chat-suggestion-chip').forEach(btn => {
    btn.addEventListener('click', () => {
      sendQuestion(btn.dataset.q || btn.textContent.trim());
    });
  });

  // Submit Handler
  chatForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = chatInput.value.trim();
    if (!message) return;

    // Remove any previous follow-up suggestions
    document.querySelectorAll('.chat-post-suggestions-wrap').forEach(el => el.remove());

    // Hide welcome screen
    if (welcomeDiv) welcomeDiv.style.display = 'none';

    // Add user message bubble
    appendBubble('user', message);

    // Reset input
    chatInput.value = '';
    chatInput.style.height = 'auto';
    sendBtn.disabled = true;

    // Show typing indicator
    const typingId = showTyping();
    scrollToBottom();

    // Get CSRF token
    let token = csrfMeta?.content || '';
    if (!token) {
      try {
        const r = await fetch('<?= APP_BASE ?>/modules/produk/get_token.php');
        const d = await r.json();
        token = d.token || '';
      } catch(err) {}
    }

    try {
      const fd = new FormData();
      fd.append('message', message);
      fd.append('csrf_token', token);

      const res = await fetch('<?= APP_BASE ?>/modules/chatbot/ask.php', {
        method: 'POST',
        body: fd,
      });

      const data = await res.json();
      removeTyping(typingId);

      if (data.success) {
        // AI answer on success
        appendBubble('ai', data.answer);
        if (data.csrf_token && csrfMeta) csrfMeta.content = data.csrf_token;

        // Render product cards (side-to-side layout) if any products with images were mentioned
        if (data.produk_gambar && data.produk_gambar.length > 0) {
          appendProductImages(data.produk_gambar);
        }

        // Always show follow-up suggestions after AI responds successfully!
        showPostResponseSuggestions();
      } else {
        appendBubble('ai', 'Gagal: ' + (data.message || 'Terjadi kesalahan. Silakan coba lagi.'), true);
        showPostResponseSuggestions();
      }
    } catch (err) {
      removeTyping(typingId);
      appendBubble('ai', 'Gagal: Tidak dapat menghubungi server. Periksa koneksi Anda.', true);
      showPostResponseSuggestions();
    }

    scrollToBottom();
    sendBtn.disabled = !chatInput.value.trim();
  });

  // Render text bubble
  function appendBubble(role, text, isError = false) {
    const isUser = role === 'user';
    const div = document.createElement('div');
    div.className = `chat-msg chat-msg--${isUser ? 'user' : 'ai'}`;

    const avatarHtml = isUser
      ? '<div class="chat-msg__avatar" aria-hidden="true"><?= strtoupper(substr(e($_SESSION['username'] ?? 'U'), 0, 1)) ?></div>'
      : '<div class="chat-msg__avatar" aria-hidden="true">AI</div>';

    let formattedText = escapeHtml(text);
    if (!isUser) {
      formattedText = formattedText
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`(.*?)`/g, '<code style="background:rgba(255,255,255,0.1);padding:1px 5px;border-radius:3px;font-family:monospace;">$1</code>')
        .replace(/\n/g, '<br>');
    } else {
      formattedText = formattedText.replace(/\n/g, '<br>');
    }

    div.innerHTML = `
      ${avatarHtml}
      <div>
        <div class="chat-msg__bubble${isError ? ' border-danger' : ''}">${formattedText}</div>
        <div class="chat-msg__time" style="${isUser ? 'text-align:right;' : ''}">${now()}</div>
      </div>
    `;

    messagesArea.appendChild(div);
    scrollToBottom();
  }

  // Render product cards (SIDE-TO-SIDE layout: thumbnail on left, description on right)
  function appendProductImages(products) {
    if (!products || !products.length) return;

    const wrap = document.createElement('div');
    wrap.className = 'chat-msg chat-msg--ai';

    const avatar = document.createElement('div');
    avatar.className = 'chat-msg__avatar';
    avatar.setAttribute('aria-hidden', 'true');
    avatar.textContent = 'AI';

    const content = document.createElement('div');
    content.style.maxWidth = '100%';

    const strip = document.createElement('div');
    strip.className = 'chat-products-strip';

    products.forEach(prod => {
      if (!prod.gambar_url) return; // Only show products with an image

      const card = document.createElement('div');
      card.className = 'chat-product-card';

      card.innerHTML = `
        <img src="${escapeHtml(prod.gambar_url)}" alt="${escapeHtml(prod.nama)}" class="chat-product-thumb" onerror="this.closest('.chat-product-card').remove()">
        <div class="chat-product-content">
          <div class="chat-product-name" title="${escapeHtml(prod.nama)}">${escapeHtml(prod.nama)}</div>
          <div class="chat-product-meta">
            <span class="chat-product-badge">${escapeHtml(prod.kategori || 'Umum')}</span>
            <span class="chat-product-stok">Stok: <strong>${prod.stok ?? 0}</strong></span>
          </div>
          <div class="chat-product-price">${escapeHtml(prod.harga_fmt || '')}</div>
        </div>
      `;
      strip.appendChild(card);
    });

    if (strip.children.length === 0) return;

    content.appendChild(strip);
    wrap.appendChild(avatar);
    wrap.appendChild(content);

    messagesArea.appendChild(wrap);
    scrollToBottom();
  }

  // Follow-up suggestion choices after AI response (BOTH success and error)
  function showPostResponseSuggestions() {
    document.querySelectorAll('.chat-post-suggestions-wrap').forEach(el => el.remove());

    const wrap = document.createElement('div');
    wrap.className = 'chat-msg chat-msg--ai chat-post-suggestions-wrap';

    const avatar = document.createElement('div');
    avatar.className = 'chat-msg__avatar';
    avatar.setAttribute('aria-hidden', 'true');
    avatar.textContent = 'AI';

    const content = document.createElement('div');
    content.style.maxWidth = '100%';

    const label = document.createElement('div');
    label.className = 'chat-post-label';
    label.textContent = 'Pertanyaan lanjutan yang bisa Anda pilih:';

    const strip = document.createElement('div');
    strip.className = 'chat-post-suggestions';

    const suggestions = [
      'Cek produk dengan stok menipis',
      'Produk termahal dan termurah',
      'Ringkasan stok per kategori',
      'Total nilai estimasi aset gudang',
      'Tampilkan 5 produk dengan stok terbanyak'
    ];

    suggestions.forEach(text => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'chat-suggestion-chip';
      chip.textContent = text;
      chip.dataset.q = text;
      chip.addEventListener('click', () => {
        sendQuestion(text);
      });
      strip.appendChild(chip);
    });

    content.appendChild(label);
    content.appendChild(strip);
    wrap.appendChild(avatar);
    wrap.appendChild(content);

    messagesArea.appendChild(wrap);
    scrollToBottom();
  }

  // Typing indicator
  function showTyping() {
    const id = 'typing-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'typing-indicator';
    div.innerHTML = `
      <div class="chat-msg__avatar" aria-hidden="true">AI</div>
      <div class="typing-bubble" aria-label="AI sedang menganalisis">
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
      </div>
    `;
    messagesArea.appendChild(div);
    scrollToBottom();
    return id;
  }

  function removeTyping(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
  }

  function scrollToBottom() {
    messagesArea.scrollTop = messagesArea.scrollHeight;
  }

  function now() {
    return new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  }

  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(text)));
    return div.innerHTML;
  }

  // Initial scroll
  scrollToBottom();

})();
</script>

</body>
</html>