document.addEventListener('DOMContentLoaded', function() {
  // Sidebar open/close for desktop and mobile
  const sidebar = document.getElementById('sidebar');
  const menuIcon = document.querySelector('img[alt="Menu"]');

  // Create overlay element
  let overlay = document.createElement('div');
  overlay.className = 'sidebar-overlay';
  overlay.style.display = 'none';
  document.body.appendChild(overlay);

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.style.display = 'block';
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.style.display = 'none';
  }
  if (sidebar) {
    sidebar.classList.remove('open'); // Default closed
  }
  if (menuIcon) {
    menuIcon.style.cursor = 'pointer';
    menuIcon.addEventListener('click', function() {
      if (sidebar.classList.contains('open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });
  }
  overlay.addEventListener('click', closeSidebar);

  // Sidebar group expand/collapse (custom only — do not use data-bs-toggle="collapse" on these
  // buttons or Bootstrap will fight this handler and the menu will flash closed.)
  document.querySelectorAll('.sidebar-group-toggle').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var controlsId = btn.getAttribute('aria-controls');
      var submenu = controlsId ? document.getElementById(controlsId) : null;
      if (!submenu) {
        return;
      }
      var willOpen = !submenu.classList.contains('open');
      submenu.classList.toggle('open', willOpen);
      btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
  });

  // Close all sidebar submenus when clicking outside the sidebar (main content, overlay, etc.)
  document.addEventListener('click', function (e) {
    if (!sidebar || !e.target || sidebar.contains(e.target)) {
      return;
    }
    document.querySelectorAll('#sidebar .sidebar-submenu.open').forEach(function (ul) {
      ul.classList.remove('open');
      var group = ul.closest('.sidebar-group');
      var t = group ? group.querySelector('.sidebar-group-toggle') : null;
      if (t) {
        t.setAttribute('aria-expanded', 'false');
      }
    });
  });

  // Keep aria-expanded in sync if PHP left .open on a submenu without matching button state
  document.querySelectorAll('#sidebar .sidebar-group').forEach(function (group) {
    var ul = group.querySelector('.sidebar-submenu');
    var toggle = group.querySelector('.sidebar-group-toggle');
    if (ul && toggle && ul.classList.contains('open')) {
      toggle.setAttribute('aria-expanded', 'true');
    }
  });

  // Active state for sidebar links
  document.querySelectorAll('.sidebar-link').forEach(function(link) {
    link.addEventListener('click', function() {
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      link.classList.add('active');
      closeSidebar(); // Optional: close sidebar on link click (mobile UX)
    });
  });
});
