/**
 * SAPG — Global JavaScript
 * Sidebar toggle, flash auto-dismiss, confirm delete
 */

(function () {
  'use strict';

  const BASE = window.APP_BASE || '';

  // ─── Sidebar Toggle ─────────────────────────────────────────────
  const sidebar      = document.getElementById('sidebar');
  const mainContent  = document.getElementById('main-content');
  const sidebarToggle = document.getElementById('sidebar-toggle');

  // Persist state in localStorage
  const SIDEBAR_KEY = 'sapg_sidebar_collapsed';

  function setSidebarState(collapsed) {
    if (!sidebar || !mainContent) return;
    if (collapsed) {
      sidebar.classList.add('collapsed');
      mainContent.classList.add('sidebar-collapsed');
      if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
    } else {
      sidebar.classList.remove('collapsed');
      mainContent.classList.remove('sidebar-collapsed');
      sidebar.classList.remove('mobile-open');
      if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'true');
    }
  }

  // Restore state
  const savedCollapsed = localStorage.getItem(SIDEBAR_KEY) === 'true';

  // On mobile, don't auto-collapse
  const isMobile = () => window.innerWidth <= 768;

  if (!isMobile()) {
    setSidebarState(savedCollapsed);
  }

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      if (isMobile()) {
        // On mobile: toggle open/close overlay
        sidebar.classList.toggle('mobile-open');
        sidebarToggle.setAttribute(
          'aria-expanded',
          sidebar.classList.contains('mobile-open') ? 'true' : 'false'
        );
      } else {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('sidebar-collapsed', isCollapsed);
        localStorage.setItem(SIDEBAR_KEY, isCollapsed ? 'true' : 'false');
        sidebarToggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
      }
    });
  }

  // Close sidebar on mobile when clicking outside
  document.addEventListener('click', (e) => {
    if (isMobile() && sidebar && sidebar.classList.contains('mobile-open')) {
      if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
        sidebar.classList.remove('mobile-open');
      }
    }
  });

  // ─── Flash Message Auto-Dismiss ─────────────────────────────────
  const flash = document.getElementById('flash-msg');
  if (flash) {
    setTimeout(() => {
      flash.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      flash.style.opacity    = '0';
      flash.style.transform  = 'translateY(-8px)';
      setTimeout(() => flash.remove(), 400);
    }, 4500);
  }

  // ─── Confirm Delete ─────────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      const msg = el.dataset.confirm || 'Yakin ingin menghapus data ini?';
      if (!confirm(msg)) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  });

  // ─── Active Nav Highlight (fallback) ────────────────────────────
  // Already handled server-side but this catches dynamic loads
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar__link').forEach((link) => {
    const href = link.getAttribute('href');
    if (href && currentPath.startsWith(href) && href !== '/') {
      link.classList.add('active');
    }
  });

  // ─── Number Input: format harga ─────────────────────────────────
  document.querySelectorAll('input[data-format="rupiah"]').forEach((input) => {
    input.addEventListener('input', () => {
      let val = input.value.replace(/\D/g, '');
      input.value = val;
    });
  });

  // ─── Auto-resize textarea ────────────────────────────────────────
  document.querySelectorAll('textarea[data-auto-resize]').forEach((ta) => {
    function resize() {
      ta.style.height = 'auto';
      ta.style.height = Math.min(ta.scrollHeight, 200) + 'px';
    }
    ta.addEventListener('input', resize);
    resize();
  });

})();
