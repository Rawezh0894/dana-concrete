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
$logout_pages = ['logout.php']; 

?>
<?php require_once '../config/permissions.php'; ?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header d-flex align-items-center p-3">
    <span class="sidebar-title">بەشە سەرەکییەکان</span>
  </div>
  <ul class="sidebar-menu list-unstyled">
    <!-- Single links first -->
    <?php if (hasPermission('view_dashboard')): ?>
      <li><a href="../pages/dashboard.php" class="sidebar-link<?php if($current_page == 'dashboard.php') echo ' active'; ?>"><i class="bi bi-speedometer2 me-2"></i> داشبۆرد</a></li>
    <?php endif; ?>
    <?php if (hasPermission('view_reports')): ?>
      <li><a href="../pages/reports.php" class="sidebar-link<?php if($current_page == 'reports.php') echo ' active'; ?>"><i class="bi bi-graph-up me-2"></i> ڕاپۆرت</a></li>
    <?php endif; ?>
    <?php if (hasPermission('view_cash_box')): ?>
      <li><a href="../pages/cash_box.php" class="sidebar-link<?php if($current_page == 'cash_box.php') echo ' active'; ?>"><i class="bi bi-cash-stack me-2"></i> قاسەکە</a></li>
    <?php endif; ?>
    <?php if (hasPermission('view_concrete_receipts')): ?>
      <li><a href="../pages/concrete_receipts.php" class="sidebar-link<?php if($current_page == 'concrete_receipts.php') echo ' active'; ?>"><i class="bi bi-file-earmark-text me-2"></i> پسوڵەی کۆنکرێت</a></li>
    <?php endif; ?>
    <?php if (hasPermission('view_concrete_receipts')): ?>
      <li><a href="../pages/summery_concrete_receipts.php" class="sidebar-link<?php if($current_page == 'summery_concrete_receipts.php') echo ' active'; ?>"><i class="bi bi-graph-up-arrow me-2"></i> پوختەی پسووڵەکان</a></li>
    <?php endif; ?>
    <?php if (hasPermission('view_concrete_formulas')): ?>
      <li><a href="../pages/concrete_formulas.php" class="sidebar-link<?php if($current_page == 'concrete_formulas.php') echo ' active'; ?>"><i class="bi bi-calculator me-2"></i> فۆرمولای کۆنکرێت</a></li>
    <?php endif; ?>
    <?php if (hasPermission('view_users')): ?>
      <li><a href="../pages/users.php" class="sidebar-link<?php if($current_page == 'users.php') echo ' active'; ?>"><i class="bi bi-people me-2"></i> بەکارهێنەران</a></li>
    <?php endif; ?>
    <?php if (hasPermission('view_notifications')): ?>
      <li><a href="../pages/notifications.php" class="sidebar-link<?php if($current_page == 'notifications.php') echo ' active'; ?>"><i class="bi bi-bell me-2"></i> ئاگادارکردنەوەکان</a></li>
    <?php endif; ?>
    <!-- All dropdowns at the end -->
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#materialsAccountsMenu" aria-expanded="false">
        <i class="bi bi-box-seam me-2"></i> مەواد و هەژمارەکان
      </button>
      <ul class="collapse sidebar-submenu" id="materialsAccountsMenu">
        <?php if (hasPermission('view_materials')): ?>
          <li><a href="../pages/stock_adjustments.php" class="sidebar-link<?php if($current_page == 'stock_adjustments.php') echo ' active'; ?>"><i class="bi bi-gear me-2"></i> گۆڕانکاری مەواد</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_bins_silos')): ?>
          <li><a href="../pages/bins_silos.php" class="sidebar-link<?php if($current_page == 'bins_silos.php') echo ' active'; ?>"><i class="bi bi-box me-2"></i> بین/سایلۆکان</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_accounts')): ?>
          <li><a href="../pages/add_company.php" class="sidebar-link<?php if($current_page == 'add_company.php') echo ' active'; ?>"><i class="bi bi-building me-2"></i> کۆمپانیا</a></li>
          <li><a href="../pages/add_car.php" class="sidebar-link<?php if($current_page == 'add_car.php') echo ' active'; ?>"><i class="bi bi-truck me-2"></i> سەیارەکان</a></li>
          <li><a href="../pages/add_customers.php" class="sidebar-link<?php if($current_page == 'add_customers.php') echo ' active'; ?>"><i class="bi bi-person-badge me-2"></i> کڕیار</a></li>
          <li><a href="../pages/add_employee.php" class="sidebar-link<?php if($current_page == 'add_employee.php') echo ' active'; ?>"><i class="bi bi-person-workspace me-2"></i> کارمەند</a></li>
        <?php endif; ?>
      </ul>
    </li>
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#vouchersMenu" aria-expanded="false">
        <i class="bi bi-receipt me-2"></i> مامەڵەکان
      </button>
      <ul class="collapse sidebar-submenu" id="vouchersMenu">
        <?php if (hasPermission('view_vouchers')): ?>
          <li><a href="../pages/add_purchase.php" class="sidebar-link<?php if($current_page == 'add_purchase.php') echo ' active'; ?>"><i class="bi bi-cart-plus me-2"></i> کڕین</a></li>
          <li><a href="../pages/add_sale.php" class="sidebar-link<?php if($current_page == 'add_sale.php') echo ' active'; ?>"><i class="bi bi-cart-check me-2"></i> فرۆشتن</a></li>
          <li><a href="../pages/cash_sales.php" class="sidebar-link<?php if($current_page == 'cash_sales.php') echo ' active'; ?>"><i class="bi bi-cash-coin me-2"></i> فرۆشتنی نەقد</a></li>
          <li><a href="../pages/credit_sales.php" class="sidebar-link<?php if($current_page == 'credit_sales.php') echo ' active'; ?>"><i class="bi bi-credit-card-2-front me-2"></i> فرۆشتنی قەرز</a></li>
        <?php endif; ?>
        <?php if (hasPermission('delete_purchase')): ?>
          <li><a href="../pages/recycle_bin_purchases.php" class="sidebar-link<?php if($current_page == 'recycle_bin_purchases.php') echo ' active'; ?>"><i class="bi bi-cart-x me-2"></i> کڕین سڕدراوەکان</a></li>
        <?php endif; ?>
        <?php if (hasPermission('delete_sale')): ?>
          <li><a href="../pages/recycle_bin_sales.php" class="sidebar-link<?php if($current_page == 'recycle_bin_sales.php') echo ' active'; ?>"><i class="bi bi-cart-dash me-2"></i> فرۆشتن سڕدراوەکان</a></li>
        <?php endif; ?>
      </ul>
    </li>
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#expensesMenu" aria-expanded="false">
        <i class="bi bi-credit-card me-2"></i> خەرجیەکان
      </button>
      <ul class="collapse sidebar-submenu" id="expensesMenu">
        <?php if (hasPermission('view_employee_payment')): ?>
          <li><a href="../pages/employee_payments.php" class="sidebar-link<?php if($current_page == 'employee_payments.php') echo ' active'; ?>"><i class="bi bi-cash-coin me-2"></i> پارەدان بە کارمەند</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_other_expenses')): ?>
          <li><a href="../pages/other_expenses.php" class="sidebar-link<?php if($current_page == 'other_expenses.php') echo ' active'; ?>"><i class="bi bi-receipt-cutoff me-2"></i> خەرجی تر</a></li>
          <li><a href="../pages/cars_expenses.php" class="sidebar-link<?php if($current_page == 'cars_expenses.php') echo ' active'; ?>"><i class="bi bi-truck-front me-2"></i> خەرجی سەیارەکان</a></li>
        <?php endif; ?>
      </ul>
    </li>
    <!-- New Koga (Materials) Dropdown -->
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#materialsMenu" aria-expanded="false">
        <i class="bi bi-boxes me-2"></i> کۆگا
      </button>
      <ul class="collapse sidebar-submenu" id="materialsMenu">
        <?php if (hasPermission('add_materials')): ?>
          <li><a href="../pages/add_material.php" class="sidebar-link<?php if($current_page == 'add_material.php') echo ' active'; ?>"><i class="bi bi-plus-square me-2"></i> زیادکردنی کاڵا</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_materials')): ?>
          <li><a href="../pages/list_materials.php" class="sidebar-link<?php if($current_page == 'list_materials.php') echo ' active'; ?>"><i class="bi bi-list-ul me-2"></i> کڕینی کاڵا</a></li>
        <?php endif; ?>
      </ul>
    </li>
  </ul>
</aside>
