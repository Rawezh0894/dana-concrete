<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_customer')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
if (!hasPermission('view_customer')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
// Note: add_customer permission is checked in the UI, not here
// Users with only view_customer permission can still access the page
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کڕیارەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <button class="btn btn-success me-2" id="exportCustomersExcelBtn" style="background: var(--seafoam-green); font-weight: bold;">
                <i class="fas fa-file-excel"></i> ئیکسپۆرت بۆ Excel
            </button>
            <button class="btn btn-success me-2" id="exportPaymentHistoryExcelBtn" style="background: var(--seafoam-green); font-weight: bold;">
                <i class="fas fa-file-excel"></i> ئیکسپۆرتی مێژووی دانەوەکانی قەرز
            </button>
            <button class="btn btn-success me-2" style="background: var(--seafoam-green); font-weight: bold;" onclick="window.location.href='credit_of_all_customers.php'">
                <i class="fa fa-print"></i> پرینتی قەرزی کڕیارەکان
            </button>
            <?php if (hasPermission('add_customer')): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCustomerModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی کڕیار</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4 shadow-sm border-0" style="border-radius: 15px;">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-bold">ساڵ</label>
                    <select id="filter_year" class="form-select">
                        <option value="">هەمووی</option>
                        <?php 
                        $currentYear = date('Y');
                        for($i = $currentYear; $i >= 2020; $i--) {
                            echo "<option value='$i'>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">مانگ</label>
                    <select id="filter_month" class="form-select">
                        <option value="">هەمووی</option>
                        <option value="01">01 - کانوونی دووەم</option>
                        <option value="02">02 - شوبات</option>
                        <option value="03">03 - ئازار</option>
                        <option value="04">04 - نیسان</option>
                        <option value="05">05 - ئایار</option>
                        <option value="06">06 - حوزەیران</option>
                        <option value="07">07 - تەمموز</option>
                        <option value="08">08 - ئاب</option>
                        <option value="09">09 - ئەیلوول</option>
                        <option value="10">10 - تشرینی یەکەم</option>
                        <option value="11">11 - تشرینی دووەم</option>
                        <option value="12">12 - کانوونی یەکەم</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">لە بەرواری</label>
                    <input type="date" id="filter_from_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">بۆ بەرواری</label>
                    <input type="date" id="filter_to_date" class="form-control">
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button id="apply_filters" class="btn btn-primary w-100" style="background: var(--kelly-green); border-color: var(--kelly-green);">
                            <i class="fas fa-filter"></i> فلتەر
                        </button>
                        <button id="clear_filters" class="btn btn-secondary w-100">
                            <i class="fas fa-times"></i> پاککردنەوە
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4" id="summary-cards">
        <?php if (hasPermission('view_total_customer_debt')): ?>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow  card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-dollar-sign card-icon"></i>
                    <h6 class="card-title">کۆی قەرزی کڕیارەکان</h6>
                    <div class="fs-4 fw-bold" id="total_debt">$0</div>
                    <small class="text-light">دۆلار</small>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="<?php echo hasPermission('view_total_customer_debt') ? 'col-md-4' : 'col-md-6'; ?> mb-3">
            <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-users card-icon"></i>
                    <h6 class="card-title">کۆی کڕیاران</h6>
                    <div class="fs-4 fw-bold" id="total_customers">0</div>
                    <small class="text-light">کڕیار</small>
                </div>
            </div>
        </div>
        <div class="<?php echo hasPermission('view_total_customer_debt') ? 'col-md-4' : 'col-md-6'; ?> mb-3">
            <div class="card text-center shadow  card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-user-times card-icon"></i>
                    <h6 class="card-title">کڕیارانی قەرز</h6>
                    <div class="fs-4 fw-bold" id="customers_with_debt">0</div>
                    <small class="text-light">کڕیار</small>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="customerTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>ناو</th>
                    <th>ژمارە مۆبایلی یەکەم</th>
                    <th>ژمارە مۆبایلی دووەم</th>
                    <th>بڕی قەرزی سەرەتایی (USD)</th>
                    <th>بڕی قەرزی سەرەتایی (IQD)</th>
                    <th>وەرگر</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Customers will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addCustomerForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addCustomerModalLabel">زیادکردنی کڕیار</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="customer_name" class="form-label">ناو</label>
            <input type="text" class="form-control" id="customer_name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="customer_mobile1" class="form-label">ژمارە مۆبایلی یەکەم</label>
            <input type="text" class="form-control" id="customer_mobile1" name="mobile1" required>
          </div>
          <div class="mb-3">
            <label for="customer_mobile2" class="form-label">ژمارە مۆبایلی دووەم</label>
            <input type="text" class="form-control" id="customer_mobile2" name="mobile2">
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="customer_opening_debt_usd" class="form-label">بڕی قەرزی سەرەتایی (USD)</label>
              <input type="number" class="form-control" id="customer_opening_debt_usd" name="opening_debt_usd" min="0" step="0.01">
            </div>
            <div class="col-md-6">
              <label for="customer_opening_debt_iqd" class="form-label">بڕی قەرزی سەرەتایی (IQD)</label>
              <input type="number" class="form-control" id="customer_opening_debt_iqd" name="opening_debt_iqd" min="0" step="1" disabled>
            </div>
          </div>
          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="customer_is_recipient" name="is_recipient" value="1">
              <label class="form-check-label" for="customer_is_recipient">
                ئەم کڕیارە هەم کڕیارە و هەم وەرگریشە
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success" style="background: var(--seafoam-green); font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit Customer Modal (to be filled by JS) -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editCustomerForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editCustomerModalLabel">دەستکاری کڕیار</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editCustomerId" name="id">
          <div class="mb-3">
            <label for="editCustomerName" class="form-label">ناو</label>
            <input type="text" class="form-control" id="editCustomerName" name="name" required>
          </div>
          <div class="mb-3">
            <label for="editCustomerMobile1" class="form-label">ژمارە مۆبایلی یەکەم</label>
            <input type="text" class="form-control" id="editCustomerMobile1" name="mobile1" required>
          </div>
          <div class="mb-3">
            <label for="editCustomerMobile2" class="form-label">ژمارە مۆبایلی دووەم</label>
            <input type="text" class="form-control" id="editCustomerMobile2" name="mobile2">
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="editCustomerOpeningDebtUsd" class="form-label">بڕی قەرزی سەرەتایی (USD)</label>
              <input type="number" class="form-control" id="editCustomerOpeningDebtUsd" name="opening_debt_usd" min="0" step="0.01">
            </div>
            <div class="col-md-6">
              <label for="editCustomerOpeningDebtIqd" class="form-label">بڕی قەرزی سەرەتایی (IQD)</label>
              <input type="number" class="form-control" id="editCustomerOpeningDebtIqd" name="opening_debt_iqd" min="0" step="1" disabled>
            </div>
          </div>
          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="editCustomerIsRecipient" name="is_recipient" value="1">
              <label class="form-check-label" for="editCustomerIsRecipient">
                ئەم کڕیارە هەم کڕیارە و هەم وەرگریشە
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-primary">نوێکردنەوە</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/customer/add_customer.js"></script>
<script src="../assets/js/customer/select_customer.js"></script>
<script src="../assets/js/customer/update_customer.js"></script>
<script src="../assets/js/customer/delete_customer.js"></script>
<script src="../assets/js/add_customers/summary_cards.js"></script>
<script src="../assets/js/customer/customer.js"></script>
<script src="../assets/js/customer/export_customers_excel.js"></script>
<script src="../assets/js/customer/export_payment_history_excel.js"></script>
</body>
</html>
