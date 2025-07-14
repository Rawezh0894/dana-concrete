<?php require_once '../config/permissions.php'; ?>
<link href="../assets/css/variables.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link href="../assets/css/nav.css" rel="stylesheet">
<link href="../assets/css/sidebar.css" rel="stylesheet">

<nav class="navbar navbar-expand-lg sticky-top" style="background: var(--seafoam-green); border-radius: 2rem; box-shadow: 0 2px 16px 0 var(--spearmint, #94c973); margin: 1rem;">
  <div class="container-fluid d-flex align-items-center justify-content-between">
    <!-- Left: Menu icon -->
    <div class="d-flex align-items-center gap-3">
      <img src="../assets/images/menu.svg" alt="Menu" style="height: 36px; width: 36px;" />
    </div>
    <!-- Center: User image and username with dropdown -->
    <div class="flex-grow-1 d-flex justify-content-end align-items-center gap-2 position-relative" id="userDropdownContainer">
      <span style="color: #fff; font-weight: bold; font-size: 1rem; cursor: pointer;" id="userDropdownToggle">
            <?php echo $_SESSION['username'] ?? 'User'; ?>
          </span>
      <img src="../assets/images/user.png" alt="User" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #fff; border: 2px solid var(--kelly-green); cursor: pointer;" id="userDropdownImg" />
      <div id="userDropdownMenu" style="display: none; position: absolute; top: 110%; left: 0; right: auto; background: #fff; border: 1px solid #ddd; border-radius: 1rem; min-width: 140px; box-shadow: 0 2px 8px #003b7320; z-index: 2000;">
        <a href="../core/logout.php" style="display: block; padding: 0.75rem 1.25rem; color: #003b73; text-decoration: none; font-weight: bold; border-radius: 1rem; transition: background 0.2s;">
          <i class="bi bi-box-arrow-right me-1"></i>چوونەدەرەوە
        </a>
      </div>
    </div>
    <!-- Right: Menu button (for sidebar toggle) -->
    <!-- <button class="btn btn-light d-lg-none" id="sidebarToggle" type="button" style="border-radius: 1rem; box-shadow: 0 2px 8px 0 var(--kelly-green);">
      <i class="bi bi-grid-3x3-gap-fill" style="font-size: 1.5rem; color: var(--seafoam-green);"></i>
    </button> -->
  </div>
</nav>
<script src="../assets/js/nav/nav.js"></script>
<script src="../assets/js/nav/sidebar.js"></script>
<script>
// User dropdown logic
(function() {
  const toggle = document.getElementById('userDropdownToggle');
  const img = document.getElementById('userDropdownImg');
  const menu = document.getElementById('userDropdownMenu');
  const container = document.getElementById('userDropdownContainer');
  function showMenu() { menu.style.display = 'block'; }
  function hideMenu() { menu.style.display = 'none'; }
  function toggleMenu(e) {
    e.stopPropagation();
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
  }
  if (toggle && img && menu) {
    toggle.addEventListener('click', toggleMenu);
    img.addEventListener('click', toggleMenu);
    document.addEventListener('click', function(e) {
      if (!container.contains(e.target)) hideMenu();
    });
  }
})();
</script>