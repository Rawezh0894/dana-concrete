<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Materials dropdown
$materials_pages = ['add_material.php', 'list_materials.php'];
$is_materials_active = in_array($current_page, $materials_pages);
// Accounts dropdown
$accounts_pages = ['add_company.php', 'list_company.php'];
$is_accounts_active = in_array($current_page, $accounts_pages);
// Vouchers dropdown
$vouchers_pages = ['add_purchase.php', 'add_sale.php'];
$is_vouchers_active = in_array($current_page, $vouchers_pages);
// Single nav-links
$dashboard_pages = ['dashboard.php'];
$users_pages = ['users.php'];
$logout_pages = ['logout.php']; // for consistency
?>
<?php require_once '../config/permissions.php'; ?>
<link href="../assets/css/variables.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link href="../assets/css/nav.css" rel="stylesheet">

<nav class="navbar navbar-expand-lg sticky-top" style="background: var(--seafoam-green);">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <!-- Group 1: Dashboard & Reports -->
        <li class="nav-item">
          <?php if (hasPermission('view_dashboard')): ?>
            <a class="nav-link <?php if(in_array($current_page, $dashboard_pages)) echo 'active'; ?>" href="../pages/dashboard.php">
              <i class="bi bi-speedometer2 me-1"></i> داشبۆرد
            </a>
          <?php endif; ?>
        </li>
        <li class="nav-item">
          <?php if (hasPermission('view_reports')): ?>
            <a class="nav-link <?php if($current_page == 'reports.php') echo 'active'; ?>" href="../pages/reports.php">
              <i class="bi bi-graph-up me-1"></i> ڕاپۆرت
            </a>
          <?php endif; ?>
        </li>
        <!-- Group 2: Materials & Accounts (Dropdown) -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="materialsAccountsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-box-seam me-1"></i> مەواد و هەژمارەکان
          </a>
          <ul class="dropdown-menu" aria-labelledby="materialsAccountsDropdown">
            <?php if (hasPermission('view_materials')): ?>
              <li><a class="dropdown-item" href="../pages/stock_adjustments.php"><i class="bi bi-gear me-1"></i> گۆڕانکاری مەواد</a></li>
            <?php endif; ?>
            <?php if (hasPermission('view_accounts')): ?>
              <li><a class="dropdown-item" href="../pages/add_company.php"><i class="bi bi-building me-1"></i> کۆمپانیا</a></li>
              <li><a class="dropdown-item" href="../pages/add_car.php"><i class="bi bi-truck me-1"></i> سەیارەکان</a></li>
              <li><a class="dropdown-item" href="../pages/add_customers.php"><i class="bi bi-person-badge me-1"></i> کڕیار</a></li>
              <li><a class="dropdown-item" href="../pages/add_employee.php"><i class="bi bi-person-workspace me-1"></i> کارمەند</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <!-- Group 3: Vouchers (Dropdown) -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="vouchersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-receipt me-1"></i> مامەڵەکان
          </a>
          <ul class="dropdown-menu" aria-labelledby="vouchersDropdown">
            <?php if (hasPermission('view_vouchers')): ?>
              <li><a class="dropdown-item" href="../pages/add_purchase.php"><i class="bi bi-cart-plus me-1"></i> کڕین</a></li>
              <li><a class="dropdown-item" href="../pages/add_sale.php"><i class="bi bi-cart-check me-1"></i> فرۆشتن</a></li>
            <?php endif; ?>
            <?php if (hasPermission('delete_purchase')): ?>
              <li><a class="dropdown-item" href="../pages/recycle_bin_purchases.php"><i class="bi bi-cart-x me-1"></i> کڕین سڕدراوەکان</a></li>
            <?php endif; ?>
            <?php if (hasPermission('delete_sale')): ?>
              <li><a class="dropdown-item" href="../pages/recycle_bin_sales.php"><i class="bi bi-cart-dash me-1"></i> فرۆشتن سڕدراوەکان</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <!-- Group 4: Cash Box, Concrete Receipts, Concrete Formulas -->
        <li class="nav-item">
          <?php if (hasPermission('view_cash_box')): ?>
            <a class="nav-link <?php if($current_page == 'cash_box.php') echo 'active'; ?>" href="../pages/cash_box.php">
              <i class="bi bi-cash-stack me-1"></i> قاسەکە
            </a>
          <?php endif; ?>
        </li>
        <li class="nav-item">
          <?php if (hasPermission('view_concrete_receipts')): ?>
            <a class="nav-link <?php if($current_page == 'concrete_receipts.php') echo 'active'; ?>" href="../pages/concrete_receipts.php">
              <i class="bi bi-file-earmark-text me-1"></i> پسوڵەی کۆنکرێت
            </a>
          <?php endif; ?>
        </li>
        <li class="nav-item">
          <?php if (hasPermission('view_concrete_formulas')): ?>
            <a class="nav-link <?php if($current_page == 'concrete_formulas.php') echo 'active'; ?>" href="../pages/concrete_formulas.php">
              <i class="bi bi-calculator me-1"></i> فۆرمولای کۆنکرێت
            </a>
          <?php endif; ?>
        </li>
        <!-- Group 5: Expenses (Dropdown) -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="expensesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-credit-card me-1"></i> خەرجیەکان
          </a>
          <ul class="dropdown-menu" aria-labelledby="expensesDropdown">
            <?php if (hasPermission('view_employee_payment')): ?>
              <li><a class="dropdown-item" href="../pages/employee_payments.php"><i class="bi bi-cash-coin me-1"></i> پارەدان بە کارمەند</a></li>
            <?php endif; ?>
            <?php if (hasPermission('view_other_expenses')): ?>
              <li><a class="dropdown-item" href="../pages/other_expenses.php"><i class="bi bi-receipt-cutoff me-1"></i> خەرجی تر</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <!-- Group 7: Users -->
        <li class="nav-item">
          <?php if (hasPermission('view_users')): ?>
            <a class="nav-link <?php if(in_array($current_page, $users_pages)) echo 'active'; ?>" href="../pages/users.php">
              <i class="bi bi-people me-1"></i> بەکارهێنەران
            </a>
          <?php endif; ?>
        </li>
      </ul>
      <!-- User Dropdown (always at end/right) -->
      <div class="dropdown">
        <a class="navbar-brand d-flex align-items-center dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
          <span style="color: #fff; font-weight: bold; font-size: 1rem;">
            <?php echo $_SESSION['username'] ?? 'User'; ?>
          </span>
          <img src="../assets/images/user.png" alt="User" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; margin-right: 8px; background: #fff;">
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown" style="background: white; border: 1px solid #ddd;">
          <li><a class="dropdown-item" href="../core/logout.php" style="color: #333;">
            <i class="bi bi-box-arrow-right me-1"></i> چوونەدەرەوە
          </a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<script src="../assets/js/nav/nav.js"></script>
