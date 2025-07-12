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
        <!-- 1. Dashboard -->
        <li class="nav-item">
          <?php if (hasPermission('view_dashboard')): ?>
            <a class="nav-link <?php if(in_array($current_page, $dashboard_pages)) echo 'active'; ?>" style="color: var(--seafoam-green); font-weight: bold;" href="../pages/dashboard.php">
              <i class="bi bi-speedometer2 me-1"></i>داشبۆرد
            </a>
          <?php else: ?>
            <a class="nav-link disabled" style="color: var(--seafoam-green); font-weight: bold; opacity:0.5;" href="#" tabindex="-1" aria-disabled="true">
              <i class="bi bi-lock-fill me-1"></i>داشبۆرد
            </a>
          <?php endif; ?>
        </li>
        <!-- 2. Reports -->
        <li class="nav-item">
          <?php if (hasPermission('view_reports')): ?>
            <a class="nav-link <?php if($current_page == 'reports.php') echo 'active'; ?>" style="color: var(--seafoam-green); font-weight: bold;" href="../pages/reports.php">
              <i class="bi bi-graph-up me-1"></i>ڕاپۆرت
            </a>
          <?php else: ?>
            <a class="nav-link disabled" style="color: var(--seafoam-green); font-weight: bold; opacity:0.5;" href="#" tabindex="-1" aria-disabled="true">
              <i class="bi bi-lock-fill me-1"></i>ڕاپۆرت
            </a>
          <?php endif; ?>
        </li>
        <!-- 3. Users -->
        <li class="nav-item">
          <?php if (hasPermission('view_users')): ?>
            <a class="nav-link <?php if(in_array($current_page, $users_pages)) echo 'active'; ?>" style="color: var(--seafoam-green); font-weight: bold;" href="../pages/users.php">
              <i class="bi bi-people me-1"></i>بەکارهێنەران
            </a>
          <?php else: ?>
            <a class="nav-link disabled" style="color: var(--seafoam-green); font-weight: bold; opacity:0.5;" href="#" tabindex="-1" aria-disabled="true">
              <i class="bi bi-lock-fill me-1"></i>بەکارهێنەران
            </a>
          <?php endif; ?>
        </li>
        <!-- 4. Materials -->
        <li class="nav-item dropdown <?php if($is_materials_active) echo 'active'; ?>">
          <?php if (hasPermission('view_materials')): ?>
            <a class="nav-link dropdown-toggle <?php if($is_materials_active) echo 'active'; ?>" href="#" id="materialsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--seafoam-green); font-weight: bold;">
              <i class="bi bi-box-seam me-1"></i>مەواد
            </a>
            <ul class="dropdown-menu" aria-labelledby="materialsDropdown">
              <li><a class="dropdown-item <?php if($current_page == 'stock_adjustments.php') echo 'active'; ?>" href="../pages/stock_adjustments.php">
                <i class="bi bi-gear me-1"></i>گۆڕانکاری مەواد
              </a></li>
            </ul>
          <?php else: ?>
            <a class="nav-link disabled" style="color: var(--seafoam-green); font-weight: bold; opacity:0.5;" href="#" tabindex="-1" aria-disabled="true">
              <i class="bi bi-lock-fill me-1"></i>مەواد
            </a>
          <?php endif; ?>
        </li>
        <!-- 5. Accounts -->
        <li class="nav-item dropdown <?php if($is_accounts_active) echo 'active'; ?>">
          <?php if (hasPermission('view_accounts')): ?>
            <a class="nav-link dropdown-toggle <?php if($is_accounts_active) echo 'active'; ?>" href="#" id="accountsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--seafoam-green); font-weight: bold;">
              <i class="bi bi-bank me-1"></i>هەژمارەکان
            </a>
            <ul class="dropdown-menu" aria-labelledby="accountsDropdown">
              <li><a class="dropdown-item <?php if($current_page == 'add_company.php') echo 'active'; ?>" href="../pages/add_company.php">
                <i class="bi bi-building me-1"></i>کۆمپانیا
              </a></li>
              <li><a class="dropdown-item <?php if($current_page == 'add_car.php') echo 'active'; ?>" href="../pages/add_car.php">
                <i class="bi bi-truck me-1"></i>سەیارەکان
              </a></li>
              <li><?php if (hasPermission('view_person_other_expenses')): ?>
                <a class="dropdown-item <?php if($current_page == 'person_other_expenses.php') echo 'active'; ?>" href="../pages/person_other_expenses.php">
                  <i class="bi bi-person-lines-fill me-1"></i>کەسانی خەرجی تر
                </a>
              <?php else: ?>
                <a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true">
                  <i class="bi bi-lock-fill me-1"></i>کەسانی خەرجی تر
                </a>
              <?php endif; ?></li>
              <li><a class="dropdown-item <?php if($current_page == 'list_company.php') echo 'active'; ?>" href="../pages/add_customers.php">
                <i class="bi bi-person-badge me-1"></i>کڕیار
              </a></li>
              <li><a class="dropdown-item <?php if($current_page == 'add_employee.php') echo 'active'; ?>" href="../pages/add_employee.php">
                <i class="bi bi-person-workspace me-1"></i>کارمەند
              </a></li>
            </ul>
          <?php else: ?>
            <a class="nav-link disabled" style="color: var(--seafoam-green); font-weight: bold; opacity:0.5;" href="#" tabindex="-1" aria-disabled="true">
              <i class="bi bi-lock-fill me-1"></i>هەژمارەکان
            </a>
          <?php endif; ?>
        </li>
        <!-- 6. Vouchers -->
        <li class="nav-item dropdown <?php if($is_vouchers_active) echo 'active'; ?>">
          <?php if (hasPermission('view_vouchers')): ?>
            <a class="nav-link dropdown-toggle <?php if($is_vouchers_active) echo 'active'; ?>" href="#" id="vouchersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--seafoam-green); font-weight: bold;">
              <i class="bi bi-receipt me-1"></i>پسوڵەکان
            </a>
            <ul class="dropdown-menu" aria-labelledby="vouchersDropdown">
              <li><a class="dropdown-item <?php if($current_page == 'add_purchase.php') echo 'active'; ?>" href="../pages/add_purchase.php">
                <i class="bi bi-cart-plus me-1"></i>کڕین
              </a></li>
              <li><a class="dropdown-item <?php if($current_page == 'add_sale.php') echo 'active'; ?>" href="../pages/add_sale.php">
                <i class="bi bi-cart-check me-1"></i>فرۆشتن
              </a></li>
            </ul>
          <?php else: ?>
            <a class="nav-link disabled" style="color: var(--seafoam-green); font-weight: bold; opacity:0.5;" href="#" tabindex="-1" aria-disabled="true">
              <i class="bi bi-lock-fill me-1"></i>پسوڵەکان
            </a>
          <?php endif; ?>
        </li>
        <!-- 6.5. Cash Box -->
        <li class="nav-item">
          <?php if (hasPermission('view_cash_box')): ?>
            <a class="nav-link <?php if($current_page == 'cash_box.php') echo 'active'; ?>" style="color: var(--seafoam-green); font-weight: bold;" href="../pages/cash_box.php">
              <i class="bi bi-cash-stack me-1"></i>قاسەکە
            </a>
          <?php else: ?>
            <a class="nav-link disabled" style="color: var(--seafoam-green); font-weight: bold; opacity:0.5;" href="#" tabindex="-1" aria-disabled="true">
              <i class="bi bi-lock-fill me-1"></i>قاسەکە
            </a>
          <?php endif; ?>
        </li>
        <!-- 7. Concrete Receipts -->
        <li class="nav-item">
          <?php if (hasPermission('view_concrete_receipts')): ?>
            <a class="nav-link <?php if($current_page == 'concrete_receipts.php') echo 'active'; ?>" style="color: var(--seafoam-green); font-weight: bold;" href="../pages/concrete_receipts.php">
              <i class="bi bi-file-earmark-text me-1"></i>پسوڵەی کۆنکرێت
            </a>
          <?php else: ?>
            <a class="nav-link disabled" style="color: var(--seafoam-green); font-weight: bold; opacity:0.5;" href="#" tabindex="-1" aria-disabled="true">
              <i class="bi bi-lock-fill me-1"></i>پسوڵەی کۆنکرێت
            </a>
          <?php endif; ?>
        </li>
        <!-- 8. Concrete Formulas -->
        <li class="nav-item">
          <?php if (hasPermission('view_concrete_formulas')): ?>
            <a class="nav-link <?php if($current_page == 'concrete_formulas.php') echo 'active'; ?>" style="color: var(--seafoam-green); font-weight: bold;" href="../pages/concrete_formulas.php">
              <i class="bi bi-calculator me-1"></i>فۆرمولای کۆنکرێت
            </a>
          <?php else: ?>
            <a class="nav-link disabled" style="color: var(--seafoam-green); font-weight: bold; opacity:0.5;" href="#" tabindex="-1" aria-disabled="true">
              <i class="bi bi-lock-fill me-1"></i>فۆرمولای کۆنکرێت
            </a>
          <?php endif; ?>
        </li>
        <!-- 9. Expenses Dropdown -->
        <li class="nav-item dropdown">
          <?php if (hasPermission('view_employee_payment') || hasPermission('view_other_expenses')): ?>
          <a class="nav-link dropdown-toggle" href="#" id="expensesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--seafoam-green); font-weight: bold;">
              <i class="bi bi-credit-card me-1"></i>خەرجیەکان
          </a>
          <ul class="dropdown-menu" aria-labelledby="expensesDropdown">
            <li>
              <?php if (hasPermission('view_employee_payment')): ?>
                <a class="dropdown-item" href="../pages/employee_payments.php">
                  <i class="bi bi-cash-coin me-1"></i>پارەدان بە کارمەند
                </a>
              <?php else: ?>
                <a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true">
                  <i class="bi bi-lock-fill me-1"></i>پارەدان بە کارمەند
                </a>
              <?php endif; ?>
            </li>
            <li>
              <?php if (hasPermission('view_other_expenses')): ?>
                <a class="dropdown-item" href="../pages/other_expenses.php">
                  <i class="bi bi-receipt-cutoff me-1"></i>خەرجی تر
                </a>
              <?php else: ?>
                <a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true">
                  <i class="bi bi-lock-fill me-1"></i>خەرجی تر
                </a>
              <?php endif; ?>
            </li>
          </ul>
          <?php else: ?>
            <a class="nav-link disabled" style="color: var(--seafoam-green); font-weight: bold; opacity:0.5;" href="#" tabindex="-1" aria-disabled="true">
              <i class="bi bi-lock-fill me-1"></i>خەرجیەکان
            </a>
          <?php endif; ?>
        </li>
        <!-- 10. Recycle Bin Dropdown -->
        <li class="nav-item dropdown">
          <?php if (hasPermission('delete_purchase') || hasPermission('delete_sale')): ?>
            <a class="nav-link dropdown-toggle" href="#" id="recycleBinDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--seafoam-green); font-weight: bold;">
              <i class="bi bi-trash me-1"></i>مامەڵە سڕدراوەکان
            </a>
            <ul class="dropdown-menu" aria-labelledby="recycleBinDropdown">
              <?php if (hasPermission('delete_purchase')): ?>
                <li><a class="dropdown-item" href="../pages/recycle_bin_purchases.php">
                  <i class="bi bi-cart-x me-1"></i>کڕین سڕدراوەکان
                </a></li>
              <?php endif; ?>
              <?php if (hasPermission('delete_sale')): ?>
                <li><a class="dropdown-item" href="../pages/recycle_bin_sales.php">
                  <i class="bi bi-cart-dash me-1"></i>فرۆشتن سڕدراوەکان
                </a></li>
              <?php endif; ?>
            </ul>
          <?php endif; ?>
        </li>
      </ul>
            <div class="dropdown">
        <a class="navbar-brand d-flex align-items-center dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
          <span style="color: #fff; font-weight: bold; font-size: 1rem;"><?php echo $_SESSION['username'] ?? 'User'; ?></span>
          <i class="bi bi-person-circle" style="font-size: 1.5rem; color: #fff; margin-right: 8px;"></i>
      </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown" style="background: white; border: 1px solid #ddd;">
          <li><a class="dropdown-item" href="../core/logout.php" style="color: #333;">
            <i class="bi bi-box-arrow-right me-1"></i>چوونەدەرەوە
          </a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<script src="../assets/js/nav/nav.js"></script>
