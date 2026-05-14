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

  // Sidebar group expand/collapse — driven entirely by the .open class / custom CSS.
  // data-bs-toggle="collapse" has been removed from the buttons so Bootstrap's
  // collapse plugin never fires here, eliminating the double-toggle flash bug.
  document.querySelectorAll('.sidebar-group-toggle').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation(); // prevent any document-level click handler from closing the menu
      const targetSelector = btn.getAttribute('data-bs-target');
      if (!targetSelector) { return; }
      const submenu = document.querySelector(targetSelector);
      if (!submenu) { return; }
      const isOpen = submenu.classList.toggle('open');
      btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
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
