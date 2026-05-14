/**
 * Sidebar: mobile drawer + accordion-style section dropdowns.
 * Dropdowns use .sidebar-submenu + .open (see sidebar.css). Do not use
 * data-bs-toggle="collapse" on .sidebar-group-toggle — it conflicts with this script.
 */
(function () {
  'use strict';

  function initSidebar() {
    var sidebar = document.getElementById('sidebar');
    var menuIcon = document.querySelector('img[alt="Menu"]');
    var overlay = document.querySelector('.sidebar-overlay');

    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'sidebar-overlay';
      overlay.style.display = 'none';
      document.body.appendChild(overlay);
    }

    function openSidebar() {
      if (!sidebar) {
        return;
      }
      sidebar.classList.add('open');
      overlay.style.display = 'block';
    }

    function closeSidebar() {
      if (!sidebar) {
        return;
      }
      sidebar.classList.remove('open');
      overlay.style.display = 'none';
    }

    if (sidebar) {
      sidebar.classList.remove('open');
    }

    if (menuIcon && sidebar) {
      menuIcon.style.cursor = 'pointer';
      menuIcon.addEventListener('click', function (e) {
        e.preventDefault();
        if (sidebar.classList.contains('open')) {
          closeSidebar();
        } else {
          openSidebar();
        }
      });
    }

    overlay.addEventListener('click', closeSidebar);

    // Accordion: one delegated listener on #sidebar (works for icon clicks inside buttons).
    if (sidebar) {
      sidebar.addEventListener('click', function (e) {
        var btn = e.target && typeof e.target.closest === 'function'
          ? e.target.closest('.sidebar-group-toggle')
          : null;
        if (!btn || !sidebar.contains(btn)) {
          return;
        }
        e.preventDefault();
        var group = btn.closest('.sidebar-group');
        var submenu = group ? group.querySelector('.sidebar-submenu') : null;
        if (!submenu) {
          return;
        }
        var opening = !submenu.classList.contains('open');
        if (opening) {
          submenu.classList.add('open');
          btn.setAttribute('aria-expanded', 'true');
        } else {
          submenu.classList.remove('open');
          btn.setAttribute('aria-expanded', 'false');
        }
      });
    }

    // Close section dropdowns when clicking outside the sidebar (main content, etc.).
    document.addEventListener('click', function (e) {
      if (!sidebar || !e.target) {
        return;
      }
      if (sidebar.contains(e.target)) {
        return;
      }
      document.querySelectorAll('#sidebar .sidebar-submenu.open').forEach(function (ul) {
        ul.classList.remove('open');
        var g = ul.closest('.sidebar-group');
        var t = g ? g.querySelector('.sidebar-group-toggle') : null;
        if (t) {
          t.setAttribute('aria-expanded', 'false');
        }
      });
    });

    // Sync aria-expanded with PHP-rendered .open
    if (sidebar) {
      sidebar.querySelectorAll('.sidebar-group').forEach(function (group) {
        var ul = group.querySelector('.sidebar-submenu');
        var toggle = group.querySelector('.sidebar-group-toggle');
        if (ul && toggle && ul.classList.contains('open')) {
          toggle.setAttribute('aria-expanded', 'true');
        }
      });
    }

    document.querySelectorAll('.sidebar-link').forEach(function (link) {
      link.addEventListener('click', function () {
        document.querySelectorAll('.sidebar-link').forEach(function (l) {
          l.classList.remove('active');
        });
        link.classList.add('active');
        closeSidebar();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
  } else {
    initSidebar();
  }
})();
