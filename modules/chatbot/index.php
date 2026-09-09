<?php
/**
 * SAPG — Chatbot AI UI
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
  <title><?= e($pageTitle) ?> — SAPG</title>
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

  <!-- Sidebar (reuse from header pattern) -->
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
      <a href="/logout.php" id="nav-logout" class="sidebar__link" onclick="return confirm('Yakin logout?')" style="color:var(--clr-danger);">
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
        <a href="/dashboard.php">SAPG</a>
        <span class="topbar__breadcrumb-sep" aria-hidden="true">›</span>
        <span class="topbar__breadcrumb-current">Chatbot AI</span>
      </nav>
      <div style="display:flex;gap:var(--sp-3);align-items:center;">
        <a href="/GudangChatBot/modules/chatbot/index.php?reset=1" class="btn btn--ghost btn--sm" title="Mulai percakapan baru">
          🔄 Chat Baru
        </a>
        <div class="topbar__user">
          <div class="topbar__avatar" aria-hidden="true"><?= strtoupper(substr(e($_SESSION['username']), 0, 1)) ?></div>
          <span class="topbar__username"><?= e($_SESSION['username']) ?></span>
        </div>
      </div>
    </header>

    <!-- Chat Layout -->
    <div class="page-wrapper" style="display:flex;flex-direction:column;height:calc(100vh - 64px);padding: var(--sp-5) var(--sp-8) var(--sp-8);">

      <div class="chat-layout" style="flex:1;display:flex;flex-direction:column;">

        <!-- Chat Header -->
        <div class="chat-header">
          <div class="chat-header__avatar" aria-hidden="true">🤖</div>
          <div class="chat-header__info">
            <div class="chat-header__name">SAPG AI Assistant</div>
            <div class="chat-header__status">
              <span style="width:6px;height:6px;background:var(--clr-success);border-radius:50%;display:inline-block;"></span>
              Siap menjawab pertanyaan analitis
            </div>
          </div>
          <div class="chat-header__actions">
            <a href="/GudangChatBot/modules/chatbot/index.php?reset=1" class="btn btn--ghost btn--sm" title="Chat Baru">
              ✨ Baru
            </a>
          </div>
        </div>

        <!-- Messages Area -->
        <div class="chat-messages" id="chat-messages" role="log" aria-live="polite" aria-label="Riwayat percakapan">

          <?php if (empty($chatHistory)): ?>
          <!-- Welcome State -->
          <div class="chat-welcome" id="chat-welcome">
            <div class="chat-welcome__icon" aria-hidden="true">🤖</div>
            <h2 class="chat-welcome__title">SAPG AI Assistant</h2>
            <p class="chat-welcome__desc">
              Saya dapat menjawab pertanyaan analitis tentang data produk gudang Anda secara real-time.
              Coba tanyakan sesuatu!
            </p>
            <div class="chat-suggestions" role="list" aria-label="Contoh pertanyaan">
              <button class="chat-suggestion-btn" role="listitem" data-q="Berapa harga produk termurah?">💰 Produk termurah?</button>
              <button class="chat-suggestion-btn" role="listitem" data-q="Produk apa saja yang stoknya habis?">📦 Stok habis?</button>
              <button class="chat-suggestion-btn" role="listitem" data-q="Kategori apa yang paling sedikit produknya?">🗂️ Kategori paling sedikit?</button>
              <button class="chat-suggestion-btn" role="listitem" data-q="Berapa total nilai stok gudang saat ini?">💎 Total nilai stok?</button>
              <button class="chat-suggestion-btn" role="listitem" data-q="Tampilkan daftar semua produk">📋 Daftar semua produk</button>
              <button class="chat-suggestion-btn" role="listitem" data-q="Produk mana yang stoknya paling banyak?">🏆 Stok terbanyak?</button>
            </div>
          </div>

          <?php else: ?>
          <!-- Existing Chat History -->
            <?php foreach ($chatHistory as $msg): ?>
              <?php if ($msg['role'] === 'user'): ?>
              <div class="chat-msg chat-msg--user">
                <div class="chat-msg__avatar" aria-hidden="true">
                  <?= strtoupper(substr(e($_SESSION['username']), 0, 1)) ?>
                </div>
                <div>
                  <div class="chat-msg__bubble"><?= nl2br(e($msg['text'])) ?></div>
                  <div class="chat-msg__time" style="text-align:right;"><?= isset($msg['time']) ? e($msg['time']) : '' ?></div>
                </div>
              </div>
              <?php elseif ($msg['role'] === 'model'): ?>
              <div class="chat-msg chat-msg--ai">
                <div class="chat-msg__avatar" aria-hidden="true">🤖</div>
                <div>
                  <div class="chat-msg__bubble"><?= nl2br(e($msg['text'])) ?></div>
                  <div class="chat-msg__time"><?= isset($msg['time']) ? e($msg['time']) : '' ?></div>
                </div>
              </div>
              <?php endif; ?>
            <?php endforeach; ?>
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
                placeholder="Tanya sesuatu tentang data gudang… (Enter untuk kirim, Shift+Enter untuk baris baru)"
                rows="1"
                maxlength="2000"
                autocomplete="off"
                aria-label="Pesan untuk AI"
                data-auto-resize
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
// ─── Chat UI Logic ─────────────────────────────────────────────────────
(function () {
  'use strict';

  const chatForm     = document.getElementById('chat-form');
  const chatInput    = document.getElementById('chat-input');
  const sendBtn      = document.getElementById('chat-send-btn');
  const messagesArea = document.getElementById('chat-messages');
  const welcomeDiv   = document.getElementById('chat-welcome');
  const csrfMeta     = document.querySelector('meta[name="csrf-token"]');

  // Enable/disable send button based on input
  chatInput?.addEventListener('input', () => {
    sendBtn.disabled = !chatInput.value.trim();
    // Auto-resize
    chatInput.style.height = 'auto';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 160) + 'px';
  });

  // Enter → send, Shift+Enter → newline
  chatInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (!sendBtn.disabled) chatForm.dispatchEvent(new Event('submit'));
    }
  });

  // Suggestion chips
  document.querySelectorAll('.chat-suggestion-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      chatInput.value = btn.dataset.q || btn.textContent.trim().replace(/^[^\s]+ /, '');
      sendBtn.disabled = false;
      chatInput.focus();
    });
  });

  // Submit
  chatForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = chatInput.value.trim();
    if (!message) return;

    // Hide welcome
    if (welcomeDiv) welcomeDiv.style.display = 'none';

    // Add user bubble
    appendBubble('user', message);

    // Clear input
    chatInput.value = '';
    chatInput.style.height = 'auto';
    sendBtn.disabled = true;

    // Show typing indicator
    const typingId = showTyping();
    scrollToBottom();

    // Get fresh CSRF token
    let token = csrfMeta?.content || '';
    if (!token) {
      try {
        const r = await fetch('<?= APP_BASE ?>/modules/produk/get_token.php');
        const d = await r.json();
        token = d.token || '';
      } catch(e) {}
    }

    try {
      const fd = new FormData();
      fd.append('message', message);
      fd.append('csrf_token', token);

      const res = await fetch('<?= APP_BASE ?>/modules/chatbot/ask.php', {
        method: 'POST', body: fd,
      });

      const data = await res.json();

      removeTyping(typingId);

      if (data.success) {
        appendBubble('ai', data.answer);
        appendSuggestions();
        // Refresh CSRF token
        if (data.csrf_token && csrfMeta) csrfMeta.content = data.csrf_token;
      } else {
        appendBubble('ai', '⚠️ ' + (data.message || 'Terjadi kesalahan. Silakan coba lagi.'), true);
      }

    } catch (err) {
      removeTyping(typingId);
      appendBubble('ai', '⚠️ Gagal menghubungi server. Periksa koneksi Anda.', true);
    }

    scrollToBottom();
    sendBtn.disabled = !chatInput.value.trim();
  });

  // ─── Helpers ───────────────────────────────────────────────────────
  function now() {
    return new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  }

  function appendBubble(role, text, isError = false) {
    const isUser = role === 'user';
    const div = document.createElement('div');
    div.className = `chat-msg chat-msg--${isUser ? 'user' : 'ai'}`;

    const avatarText = isUser
      ? '<?= strtoupper(substr(e($_SESSION['username']), 0, 1)) ?>'
      : '🤖';

    // Simple markdown-like formatting for AI responses
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
      <div class="chat-msg__avatar" aria-hidden="true">${avatarText}</div>
      <div>
        <div class="chat-msg__bubble${isError ? ' border-danger' : ''}">${formattedText}</div>
        <div class="chat-msg__time" style="${isUser ? 'text-align:right;' : ''}">${now()}</div>
      </div>
    `;

    messagesArea.appendChild(div);
    scrollToBottom();
  }

  function showTyping() {
    const id = 'typing-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'typing-indicator';
    div.innerHTML = `
      <div class="chat-msg__avatar" aria-hidden="true">🤖</div>
      <div class="typing-bubble" aria-label="AI sedang mengetik">
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

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
  }

  // ─── Suggestion chips setelah AI jawab ─────────────────────────
  const suggestions = [
    { label: '💰 Produk termurah?',      q: 'Berapa harga produk termurah?' },
    { label: '📦 Stok habis?',           q: 'Produk apa saja yang stoknya habis?' },
    { label: '🗂️ Distribusi kategori?',  q: 'Kategori apa yang paling sedikit produknya?' },
    { label: '💎 Total nilai stok?',      q: 'Berapa total nilai stok gudang saat ini?' },
    { label: '🏆 Stok terbanyak?',        q: 'Produk mana yang stoknya paling banyak?' },
    { label: '📋 Daftar semua produk',    q: 'Tampilkan daftar semua produk' },
  ];

  function appendSuggestions() {
    // Hapus chip sebelumnya agar tidak menumpuk
    const prev = messagesArea.querySelector('.chat-follow-up-chips');
    if (prev) prev.remove();

    const wrap = document.createElement('div');
    wrap.className = 'chat-follow-up-chips';
    wrap.style.cssText = 'display:flex;flex-wrap:wrap;gap:8px;padding:8px 0 4px 52px;animation:fadeIn .3s ease;';

    suggestions.forEach(s => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'chat-suggestion-btn';
      btn.textContent = s.label;
      btn.dataset.q = s.q;
      btn.addEventListener('click', () => {
        chatInput.value = s.q;
        sendBtn.disabled = false;
        chatInput.focus();
        // Auto-scroll juga
        chatInput.style.height = 'auto';
        chatInput.style.height = Math.min(chatInput.scrollHeight, 160) + 'px';
        // Hapus chip setelah dipilih
        wrap.remove();
      });
      wrap.appendChild(btn);
    });

    messagesArea.appendChild(wrap);
    scrollToBottom();
  }

  // Scroll to bottom on load
  scrollToBottom();

})();
</script>

</body>
</html>
