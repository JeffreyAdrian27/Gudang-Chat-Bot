/**
 * SAPG — Produk Page JS
 * Handles: Tambah Stok Modal (AJAX), confirm delete
 */

(function () {
  'use strict';

  const BASE = window.APP_BASE || '';

  // ─── Elements ────────────────────────────────────────────────────
  const overlay     = document.getElementById('modal-stok');
  const modalClose  = document.getElementById('modal-stok-close');
  const modalCancel = document.getElementById('modal-stok-cancel');
  const modalSubmit = document.getElementById('modal-stok-submit');
  const inputNama   = document.getElementById('modal-nama-produk');
  const inputStok   = document.getElementById('modal-stok-saat-ini');
  const inputJumlah = document.getElementById('modal-jumlah-tambah');
  const modalError  = document.getElementById('modal-stok-error');

  let currentProductId = null;

  if (!overlay) return; // Halaman bukan produk index

  // ─── Open Modal ──────────────────────────────────────────────────
  document.querySelectorAll('.btn-tambah-stok').forEach((btn) => {
    btn.addEventListener('click', () => {
      currentProductId = btn.dataset.id;
      const nama       = btn.dataset.nama;
      const stok       = btn.dataset.stok;

      if (inputNama)   inputNama.value   = nama;
      if (inputStok)   inputStok.value   = parseInt(stok, 10).toLocaleString('id-ID') + ' unit';
      if (inputJumlah) { inputJumlah.value = ''; inputJumlah.focus(); }
      if (modalError)  { modalError.style.display = 'none'; modalError.textContent = ''; }

      openModal();
    });
  });

  // ─── Open / Close ─────────────────────────────────────────────────
  function openModal() {
    overlay.classList.add('open');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    // Focus ke input jumlah setelah animasi selesai
    setTimeout(() => { if (inputJumlah) inputJumlah.focus(); }, 300);
  }

  function closeModal() {
    overlay.classList.remove('open');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    currentProductId = null;
    setLoading(false);
  }

  if (modalClose)  modalClose.addEventListener('click', closeModal);
  if (modalCancel) modalCancel.addEventListener('click', closeModal);

  // Close on backdrop click
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeModal();
  });

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
  });

  // Enter submits modal
  if (inputJumlah) {
    inputJumlah.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); submitTambahStok(); }
    });
  }

  // ─── Submit AJAX ──────────────────────────────────────────────────
  if (modalSubmit) {
    modalSubmit.addEventListener('click', submitTambahStok);
  }

  async function submitTambahStok() {
    // Validasi client-side
    const jumlah = parseInt(inputJumlah.value.trim(), 10);
    if (!jumlah || jumlah < 1) {
      showError('Masukkan jumlah yang valid (minimal 1).');
      inputJumlah.focus();
      return;
    }
    if (jumlah > 999999) {
      showError('Jumlah terlalu besar (maks 999.999).');
      return;
    }

    setLoading(true);
    hideError();

    // Ambil CSRF token dari hidden input di halaman (perlu satu token fresh)
    // Generate via GET ke endpoint khusus, atau gunakan token yang di-render server
    // Untuk simplisitas: kita simpan token di data attribute form tersembunyi
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const formData = new FormData();
    formData.append('id_produk',     currentProductId);
    formData.append('jumlah_tambah', jumlah);
    formData.append('csrf_token',    csrfToken);

    try {
      const res = await fetch(BASE + '/modules/produk/add_stok.php', {
        method: 'POST',
        body: formData,
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const data = await res.json();

      if (data.success) {
        // Update badge di tabel tanpa reload
        const badgeCell = document.getElementById(`stok-${currentProductId}`);
        if (badgeCell && data.stok_badge) {
          badgeCell.innerHTML = data.stok_badge;
          // Animasi highlight row
          const row = document.getElementById(`row-produk-${currentProductId}`);
          if (row) {
            row.style.transition = 'background 0.3s ease';
            row.style.background = 'rgba(16,185,129,0.08)';
            setTimeout(() => { row.style.background = ''; }, 1500);
          }
        }

        // Update data attribute tombol
        const btn = document.getElementById(`btn-stok-${currentProductId}`);
        if (btn && data.stok_baru !== undefined) {
          btn.dataset.stok = data.stok_baru;
        }

        closeModal();
        // Tampilkan notifikasi sukses di atas tabel
        showToast(`✅ Stok "${data.nama_produk}" berhasil ditambah. Stok baru: ${Number(data.stok_baru).toLocaleString('id-ID')}`, 'success');

        // Perbarui CSRF token dari header response jika ada
        const newToken = res.headers.get('X-CSRF-Token');
        if (newToken) {
          const metaCsrf = document.querySelector('meta[name="csrf-token"]');
          if (metaCsrf) metaCsrf.content = newToken;
        }

      } else {
        if (data.redirect) {
          window.location.href = (data.redirect.startsWith('http') || (BASE && data.redirect.startsWith(BASE))) ? data.redirect : (BASE + data.redirect);
          return;
        }
        showError(data.message || 'Terjadi kesalahan. Coba lagi.');
        setLoading(false);
      }

    } catch (err) {
      console.error('[STOK]', err);
      showError('Gagal menghubungi server. Periksa koneksi Anda.');
      setLoading(false);
    }
  }

  // ─── UI Helpers ───────────────────────────────────────────────────
  function setLoading(loading) {
    if (!modalSubmit) return;
    const btnText = modalSubmit.querySelector('.btn-text');
    const btnLoad = modalSubmit.querySelector('.btn-loading');
    modalSubmit.disabled = loading;
    if (btnText) btnText.style.display = loading ? 'none' : '';
    if (btnLoad) btnLoad.style.display = loading ? 'inline-flex' : 'none';
  }

  function showError(msg) {
    if (!modalError) return;
    modalError.textContent = msg;
    modalError.style.display = 'flex';
  }

  function hideError() {
    if (!modalError) return;
    modalError.style.display = 'none';
    modalError.textContent = '';
  }

  function showToast(msg, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert--${type}`;
    toast.style.cssText = 'position: fixed; bottom: 24px; right: 24px; z-index: 999; max-width: 380px; animation: slideDown 0.3s ease;';
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity 0.4s ease';
      setTimeout(() => toast.remove(), 400);
    }, 4000);
  }

  // ─── CSRF Token ───────────────────────────────────────────────────
  // Generate meta csrf token dari PHP inline di halaman
  // Fallback: ambil dari cookie atau generate ulang via endpoint
  // Di halaman index kita inject via meta tag
  function injectCsrfMeta() {
    // Meta tag di-inject oleh server jika ada, fallback: fetch token
    if (!document.querySelector('meta[name="csrf-token"]')) {
      fetch(BASE + '/modules/produk/get_token.php')
        .then(r => r.json())
        .then(d => {
          const meta = document.createElement('meta');
          meta.name = 'csrf-token';
          meta.content = d.token || '';
          document.head.appendChild(meta);
        })
        .catch(() => {});
    }
  }

  injectCsrfMeta();

})();
