<?php 

$current_page = basename($_SERVER['PHP_SELF']);
// Materials dropdown
$materials_pages = ['add_material.php', 'list_materials.php'];
$is_materials_active = in_array($current_page, $materials_pages);
// Accounts dropdown
$accounts_pages = ['add_company.php', 'list_company.php'];
$is_accounts_active = in_array($current_page, $accounts_pages);
// Vouchers dropdown
$vouchers_pages = ['add_purchase.php', 'add_sale.php', 'raw_material_sales.php', 'cash_sales.php', 'credit_sales.php', 'recycle_bin_purchases.php', 'recycle_bin_sales.php'];
$is_vouchers_active = in_array($current_page, $vouchers_pages);
// System Management dropdown
$system_pages = ['users.php', 'notifications.php', 'settings.php'];
$is_system_active = in_array($current_page, $system_pages);
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
    <!-- Main Dashboard Dropdown -->
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#mainMenu" aria-expanded="false">
        <i class="bi bi-speedometer2 me-2"></i> سەرەکی
      </button>
      <ul class="collapse sidebar-submenu" id="mainMenu">
        <?php if (hasPermission('view_dashboard')): ?>
          <li><a href="../pages/dashboard.php" class="sidebar-link<?php if($current_page == 'dashboard.php') echo ' active'; ?>"><i class="bi bi-speedometer2 me-2"></i> داشبۆرد</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_reports')): ?>
          <li><a href="../pages/reports.php" class="sidebar-link<?php if($current_page == 'reports.php') echo ' active'; ?>"><i class="bi bi-graph-up me-2"></i> ڕاپۆرت</a></li>
        <?php endif; ?>
        <!-- <?php if (hasPermission('view_cash_box')): ?>
          <li><a href="../pages/cash_box.php" class="sidebar-link<?php if($current_page == 'cash_box.php') echo ' active'; ?>"><i class="bi bi-cash-stack me-2"></i> قاسەکە</a></li>
        <?php endif; ?> -->
        <?php if (hasPermission('view_user_wallets')): ?>
          <li><a href="../pages/user_wallets.php" class="sidebar-link<?php if($current_page == 'user_wallets.php') echo ' active'; ?>"><i class="bi bi-wallet2 me-2"></i> هەژماری تایبەت</a></li>
        <?php endif; ?>
      </ul>
    </li>
    <!-- All other dropdowns -->
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#concreteMenu" aria-expanded="false">
        <i class="bi bi-building me-2"></i> کۆنکرێت
      </button>
      <ul class="collapse sidebar-submenu" id="concreteMenu">
        <?php if (hasPermission('view_concrete_receipts')): ?>
          <li><a href="../pages/concrete_receipts.php" class="sidebar-link<?php if($current_page == 'concrete_receipts.php') echo ' active'; ?>"><i class="bi bi-file-earmark-text me-2"></i> پسووڵەی ناردن</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_summery_concrete_receipts')): ?>
          <li><a href="../pages/summery_concrete_receipts.php" class="sidebar-link<?php if($current_page == 'summery_concrete_receipts.php') echo ' active'; ?>"><i class="bi bi-graph-up me-2"></i>پوختەی پسووڵە</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_notes')): ?>
          <li><a href="../pages/notes.php" class="sidebar-link<?php if($current_page == 'notes.php') echo ' active'; ?>"><i class="bi bi-sticky me-2"></i> تێبینیەکان</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_concrete_formulas')): ?>
          <li><a href="../pages/concrete_formulas.php" class="sidebar-link<?php if($current_page == 'concrete_formulas.php') echo ' active'; ?>"><i class="bi bi-calculator me-2"></i> فۆرمولاکان</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_service_receipts')): ?>
          <li><a href="../pages/service_receipts.php" class="sidebar-link<?php if($current_page == 'service_receipts.php') echo ' active'; ?>"><i class="bi bi-gear-wide-connected me-2"></i> پسووڵەی خزمەتگوزاری</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_service_customers')): ?>
          <li><a href="../pages/service_customers.php" class="sidebar-link<?php if($current_page == 'service_customers.php') echo ' active'; ?>"><i class="bi bi-people-fill me-2"></i> کڕیارانی خزمەتگوزاری</a></li>
        <?php endif; ?>
      </ul>
    </li>
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#materialsAccountsMenu" aria-expanded="false">
        <i class="bi bi-box-seam me-2"></i> مەواد و هەژمارەکان
      </button>
      <ul class="collapse sidebar-submenu" id="materialsAccountsMenu">
        <!-- <?php if (hasPermission('view_materials')): ?>
          <li><a href="../pages/stock_adjustments.php" class="sidebar-link<?php if($current_page == 'stock_adjustments.php') echo ' active'; ?>"><i class="bi bi-gear me-2"></i> گۆڕانکاری مەواد</a></li>
        <?php endif; ?> -->
        <?php if (hasPermission('view_bins_silos')): ?>
          <li><a href="../pages/bins_silos.php" class="sidebar-link<?php if($current_page == 'bins_silos.php') echo ' active'; ?>"><i class="bi bi-box me-2"></i> بین/سایلۆکان</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_purchase')): ?>
          <li><a href="../pages/monthly_material_stock.php" class="sidebar-link<?php if($current_page == 'monthly_material_stock.php') echo ' active'; ?>"><i class="bi bi-clock-history me-2"></i> مێژووی بڕی مەوادەکان</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_company')): ?>
          <li><a href="../pages/add_company.php" class="sidebar-link<?php if($current_page == 'add_company.php') echo ' active'; ?>"><i class="bi bi-building me-2"></i> کۆمپانیا</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_car')): ?>
          <li><a href="../pages/add_car.php" class="sidebar-link<?php if($current_page == 'add_car.php') echo ' active'; ?>"><i class="bi bi-truck me-2"></i> سەیارەکان</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_customer')): ?>
          <li><a href="../pages/add_customers.php" class="sidebar-link<?php if($current_page == 'add_customers.php') echo ' active'; ?>"><i class="bi bi-person-badge me-2"></i> کڕیار</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_employee')): ?>
          <li><a href="../pages/add_employee.php" class="sidebar-link<?php if($current_page == 'add_employee.php') echo ' active'; ?>"><i class="bi bi-person-workspace me-2"></i> کارمەند</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_recipient')): ?>
          <li><a href="../pages/recipients.php" class="sidebar-link<?php if($current_page == 'recipients.php') echo ' active'; ?>"><i class="bi bi-people me-2"></i> وەرگرەکان</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_person_other_expenses')): ?>
          <li><a href="../pages/person_other_expenses.php" class="sidebar-link<?php if($current_page == 'person_other_expenses.php') echo ' active'; ?>"><i class="bi bi-person-lines-fill me-2"></i> خەرجی تر</a></li>
        <?php endif; ?>
      </ul>
    </li>
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#vouchersMenu" aria-expanded="false">
        <i class="bi bi-receipt me-2"></i> مامەڵەکان
      </button>
      <ul class="collapse sidebar-submenu" id="vouchersMenu">
        <?php if (hasPermission('view_purchase')): ?>
          <li><a href="../pages/add_purchase.php" class="sidebar-link<?php if($current_page == 'add_purchase.php') echo ' active'; ?>"><i class="bi bi-cart-plus me-2"></i> کڕین</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_sale')): ?>
          <li><a href="../pages/add_sale.php" class="sidebar-link<?php if($current_page == 'add_sale.php') echo ' active'; ?>"><i class="bi bi-cart-check me-2"></i> فرۆشتن</a></li>
          <li><a href="../pages/cash_sales.php" class="sidebar-link<?php if($current_page == 'cash_sales.php') echo ' active'; ?>"><i class="bi bi-cash-coin me-2"></i> فرۆشتنی نەقد</a></li>
          <li><a href="../pages/credit_sales.php" class="sidebar-link<?php if($current_page == 'credit_sales.php') echo ' active'; ?>"><i class="bi bi-credit-card-2-front me-2"></i> فرۆشتنی قەرز</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_raw_material_sales')): ?>
          <li><a href="../pages/raw_material_sales.php" class="sidebar-link<?php if($current_page == 'raw_material_sales.php') echo ' active'; ?>"><i class="bi bi-box-seam me-2"></i> فرۆشتنی مەوادی خام</a></li>
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
          <li><a href="../pages/employee_expenses.php" class="sidebar-link<?php if($current_page == 'employee_expenses.php') echo ' active'; ?>"><i class="bi bi-person-workspace me-2"></i> بەڕێوەبردنی خەرجی کارمەند</a></li>
          <li><a href="../pages/employee_payments.php" class="sidebar-link<?php if($current_page == 'employee_payments.php') echo ' active'; ?>"><i class="bi bi-clock-history me-2"></i> پارەدانە کۆنەکان</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_other_expenses')): ?>
          <li><a href="../pages/other_expenses.php" class="sidebar-link<?php if($current_page == 'other_expenses.php') echo ' active'; ?>"><i class="bi bi-receipt-cutoff me-2"></i>خەرجی سەیارەکان</a></li>
          <!-- <li><a href="../pages/cars_expenses.php" class="sidebar-link<?php if($current_page == 'cars_expenses.php') echo ' active'; ?>"><i class="bi bi-truck-front me-2"></i> خەرجی سەیارەکان</a></li> -->
        <?php endif; ?>
       
        <?php if (hasPermission('view_income_from_cars')): ?>
          <li><a href="../pages/income_from_cars.php" class="sidebar-link<?php if($current_page == 'income_from_cars.php') echo ' active'; ?>"><i class="bi bi-graph-up-arrow me-2"></i>داهاتی سەیارەکان</a></li>
        <?php endif; ?>
        <li><a href="../pages/other_income.php" class="sidebar-link<?php if($current_page == 'other_income.php') echo ' active'; ?>"><i class="bi bi-wallet2 me-2"></i>داهاتی تر</a></li>
        <li><a href="../pages/asset_depreciation.php" class="sidebar-link<?php if($current_page == 'asset_depreciation.php') echo ' active'; ?>"><i class="bi bi-graph-down-arrow me-2"></i>داخورانی ئامێرەکان</a></li>
      </ul>
    </li>
    <!-- Financial System Dropdown -->
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#financialMenu" aria-expanded="false">
        <i class="bi bi-bank me-2"></i> سیستەمی دارایی
      </button>
      <ul class="collapse sidebar-submenu" id="financialMenu">
        <li><a href="../pages/user_wallets.php" class="sidebar-link<?php if($current_page == 'user_wallets.php') echo ' active'; ?>"><i class="bi bi-wallet2 me-2"></i> قاسەی بەکارهێنەر</a></li>
        <li><a href="../pages/transaction_categories.php" class="sidebar-link<?php if($current_page == 'transaction_categories.php') echo ' active'; ?>"><i class="bi bi-tags me-2"></i> پۆلێنکردنی مامەڵەکان</a></li>
        <li><a href="../pages/wallet_report.php" class="sidebar-link<?php if($current_page == 'wallet_report.php') echo ' active'; ?>"><i class="bi bi-file-earmark-spreadsheet me-2"></i> کشف حساب (ڕاپۆرت)</a></li>
      </ul>
    </li>
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#factoryTrucksMenu" aria-expanded="false">
        <i class="bi bi-truck me-2"></i> تڕێلەکانی کارگە
      </button>
      <ul class="collapse sidebar-submenu" id="factoryTrucksMenu">
        <li><a href="../pages/factory_trucks.php" class="sidebar-link<?php if($current_page == 'factory_trucks.php') echo ' active'; ?>"><i class="bi bi-gear-fill me-2"></i> بەڕێوەبردنی تڕێلە</a></li>
        <li><a href="../pages/truck_expenses.php" class="sidebar-link<?php if($current_page == 'truck_expenses.php') echo ' active'; ?>"><i class="bi bi-receipt-cutoff me-2"></i> خەرجی تڕێلە</a></li>
        <li><a href="../pages/truck_report.php" class="sidebar-link<?php if($current_page == 'truck_report.php') echo ' active'; ?>"><i class="bi bi-graph-up-arrow me-2"></i> ڕاپۆرتی قازانج</a></li>
      </ul>
    </li>
    <!-- New Spare Parts Inventory -->
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#newInventoryMenu" aria-expanded="false">
        <i class="bi bi-gear-wide-connected me-2"></i> کۆگای یەدەگ
      </button>
      <ul class="collapse sidebar-submenu shadow-sm" id="newInventoryMenu">
        <li><a href="../pages/inventory_management.php" class="sidebar-link<?php if($current_page == 'inventory_management.php') echo ' active'; ?>"><i class="bi bi-box-seam me-2"></i> بەڕێوەبردنی پارچە</a></li>
      </ul>
    </li>
    <!-- New Koga (Materials) Dropdown -->
    <!-- <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#materialsMenu" aria-expanded="false">
        <i class="bi bi-boxes me-2"></i> کۆگا
      </button>
      <ul class="collapse sidebar-submenu" id="materialsMenu">
        <?php if (hasPermission('add_materials')): ?>
          <li><a href="../pages/add_material.php" class="sidebar-link<?php if($current_page == 'add_material.php') echo ' active'; ?>"><i class="bi bi-plus-square me-2"></i> زیادکردنی کاڵا</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_materials')): ?>
          <li><a href="../pages/purchase_materila.php" class="sidebar-link<?php if($current_page == 'purchase_materila.php') echo ' active'; ?>"><i class="bi bi-list-ul me-2"></i> کڕینی کاڵا</a></li>
          <li><a href="../pages/material_sales.php" class="sidebar-link<?php if($current_page == 'material_sales.php') echo ' active'; ?>"><i class="bi bi-clock-history me-2"></i> مێژووی فرۆشتنی کاڵا</a></li>
        <?php endif; ?>
      </ul>
    </li> -->
    <!-- System Management Dropdown -->
    <li class="sidebar-group">
      <button class="sidebar-group-toggle d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#systemMenu" aria-expanded="false">
        <i class="bi bi-gear me-2"></i> بەڕێوەبردنی سیستەم
      </button>
      <ul class="collapse sidebar-submenu" id="systemMenu">
        <?php if (hasPermission('view_users')): ?>
          <li><a href="../pages/users.php" class="sidebar-link<?php if($current_page == 'users.php') echo ' active'; ?>"><i class="bi bi-people me-2"></i> بەکارهێنەران</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_notifications')): ?>
          <li><a href="../pages/notifications.php" class="sidebar-link<?php if($current_page == 'notifications.php') echo ' active'; ?>"><i class="bi bi-bell me-2"></i> ئاگادارکردنەوەکان</a></li>
        <?php endif; ?>
        <?php if (hasPermission('view_settings')): ?>
          <li><a href="../pages/settings.php" class="sidebar-link<?php if($current_page == 'settings.php') echo ' active'; ?>"><i class="bi bi-sliders me-2"></i> ڕێکخستنەکان</a></li>
        <?php endif; ?>
        <!-- <?php if (hasPermission('view_users')): ?>
          <li><a href="../pages/database_backup.php" class="sidebar-link<?php if($current_page == 'database_backup.php') echo ' active'; ?>"><i class="bi bi-database me-2"></i> باک ئەپی داتابەیس</a></li>
        <?php endif; ?> -->
      </ul>
    </li>
  </ul>
</aside>
