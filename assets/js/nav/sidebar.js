document.addEventListener('DOMContentLoaded', function() {
  console.log('Sidebar script loaded');
  
  // Sidebar open/close for desktop and mobile
  const sidebar = document.getElementById('sidebar');
  const menuIcon = document.querySelector('img[alt="Menu"]');
  
  console.log('Sidebar element:', sidebar);
  console.log('Menu icon:', menuIcon);

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

  // Sidebar group expand/collapse - Let Bootstrap handle this
  // The sidebar uses Bootstrap's collapse functionality with data-bs-toggle="collapse"
  // Bootstrap will automatically handle the expand/collapse behavior
  
  // Debug: Check if Bootstrap is loaded
  if (typeof bootstrap !== 'undefined') {
    console.log('Bootstrap is loaded');
    console.log('Bootstrap version:', bootstrap.VERSION);
  } else {
    console.error('Bootstrap is not loaded!');
  }
  
  // Debug: Check sidebar group toggles
  const groupToggles = document.querySelectorAll('.sidebar-group-toggle');
  console.log('Found sidebar group toggles:', groupToggles.length);
  
  groupToggles.forEach((toggle, index) => {
    console.log(`Toggle ${index + 1}:`, toggle);
    console.log(`Toggle target:`, toggle.getAttribute('data-bs-target'));
  });

  // Active state for sidebar links
  document.querySelectorAll('.sidebar-link').forEach(function(link) {
    link.addEventListener('click', function() {
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      link.classList.add('active');
      closeSidebar(); // Optional: close sidebar on link click (mobile UX)
    });
  });
  
  // Ensure Bootstrap collapse is properly initialized
  setTimeout(() => {
    console.log('Initializing Bootstrap collapse components...');
    if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
      const collapseElements = document.querySelectorAll('[data-bs-toggle="collapse"]');
      collapseElements.forEach(element => {
        new bootstrap.Collapse(element, {
          toggle: false
        });
      });
      console.log('Bootstrap collapse components initialized:', collapseElements.length);
    }
  }, 100);
});
