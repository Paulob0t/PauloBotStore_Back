/**
 * VendingBox Admin — sidebar toggle & layout
 * Replaces Skydash minimize (sidebar-icon-only) with proper drawer/collapse.
 */
(function () {
  'use strict';

  var MOBILE_BP = 992;
  var STORAGE_KEY = 'vb-sidebar-collapsed';

  function isMobile() {
    return window.innerWidth < MOBILE_BP;
  }

  function openMobileSidebar() {
    document.body.classList.add('vb-sidebar-open');
    document.body.classList.remove('sidebar-icon-only');
  }

  function closeMobileSidebar() {
    document.body.classList.remove('vb-sidebar-open');
    document.querySelector('.sidebar-offcanvas')?.classList.remove('active');
  }

  function toggleDesktopCollapse() {
    var collapsed = document.body.classList.toggle('vb-sidebar-collapsed');
    try {
      localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
    } catch (e) { /* ignore */ }
    document.body.classList.remove('sidebar-icon-only');
  }

  function handleToggleClick(e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    if (isMobile()) {
      if (document.body.classList.contains('vb-sidebar-open')) {
        closeMobileSidebar();
      } else {
        openMobileSidebar();
      }
    } else {
      toggleDesktopCollapse();
    }
  }

  function restoreDesktopState() {
    if (isMobile()) return;
    try {
      if (localStorage.getItem(STORAGE_KEY) === '1') {
        document.body.classList.add('vb-sidebar-collapsed');
      }
    } catch (e) { /* ignore */ }
  }

  function bindSidebar() {
    document.body.classList.remove('sidebar-icon-only');

    // Capture phase — runs before jQuery template.js minimize handler
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('#vbSidebarToggle, #vbSidebarToggleMobile, [data-toggle="minimize"], [data-toggle="offcanvas"]');
      if (!btn) return;
      handleToggleClick(e);
    }, true);

    var overlay = document.getElementById('vbSidebarOverlay');
    if (overlay) {
      overlay.addEventListener('click', closeMobileSidebar);
    }

    var closeBtn = document.getElementById('vbSidebarClose');
    if (closeBtn) {
      closeBtn.addEventListener('click', closeMobileSidebar);
    }

    document.querySelectorAll('#sidebar .nav-link').forEach(function (link) {
      link.addEventListener('click', function () {
        if (isMobile()) closeMobileSidebar();
      });
    });

    window.addEventListener('resize', function () {
      if (!isMobile()) {
        closeMobileSidebar();
      } else {
        document.body.classList.remove('vb-sidebar-collapsed');
      }
    });

    restoreDesktopState();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindSidebar);
  } else {
    bindSidebar();
  }
})();
