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
if (!hasPermission('add_customer')) {
    header('Location: ../index.php');
    exit;
}
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">کڕیارەکان</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCustomerModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی کڕیار</button>
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
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-primary">نوێکردنەوە</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/customer/add_customer.js"></script>
<script src="../assets/js/customer/select_customer.js"></script>
<script src="../assets/js/customer/update_customer.js"></script>
<script src="../assets/js/customer/delete_customer.js"></script>
<script src="../assets/js/customer/customer.js"></script>
</body>
</html>
