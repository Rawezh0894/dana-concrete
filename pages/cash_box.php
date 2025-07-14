<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!hasPermission('view_cash_box')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
if (!isset($_SESSION['user_id']) || !hasPermission('add_cash_box')) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قاسەکە</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/reports.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">قاسەکە</h2>
        <button class="btn" data-bs-toggle="modal" data-bs-target="#addCashBoxModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردن</button>
    </div>
    <!-- Summary Cards -->
    <div class="row mb-4" id="cashBoxSummaryCards">
        <div class="col-md-6 mb-3 mx-auto">
            <div class="report-card customer-card" id="card-total-usd-all">
                <div class="card-title">کۆی پارەی قاسە (دۆلار + دینار بە دۆلار)</div>
                <div class="card-value" id="totalCashUsdAll">$0</div>
                <div class="section-label"><i class="fa fa-calculator"></i> هەموو بە دۆلار</div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
      <div class="col-md-3">
        <label>لە بەروار:</label>
        <input type="date" id="filter_from" class="form-control">
      </div>
      <div class="col-md-3">
        <label>بۆ بەروار:</label>
        <input type="date" id="filter_to" class="form-control">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-secondary" id="clearFilterBtn" type="button">پاککردنەوە</button>
      </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="cashBoxTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>بەروار</th>
                    <th>جۆری مامەڵە</th>
                    <th>بڕی پارە بە دینار</th>
                    <th>بڕی پارە بە دۆلار</th>
                    <th>جۆری دراو</th>
                    <th>تێبینی</th>
                    <th>دروستکراو لەلایەن</th>
                    <th>کات</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Cash box entries will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Cash Box Modal -->
<div class="modal fade" id="addCashBoxModal" tabindex="-1" aria-labelledby="addCashBoxModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addCashBoxForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addCashBoxModalLabel">زیادکردنی مامەڵەی قاسەکە</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="date" name="date" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="type" class="form-label">جۆری مامەڵە</label>
              <select class="form-select" id="type" name="type" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="deposit">زیادکردن</option>
                <option value="withdraw">کەمکردنەوە</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="amount_iqd" class="form-label">بڕی پارە بە دینار</label>
              <input type="number" class="form-control" id="amount_iqd" name="amount_iqd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="amount_usd" class="form-label">بڕی پارە بە دۆلار</label>
              <input type="number" class="form-control" id="amount_usd" name="amount_usd" min="0" step="0.01" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="currency" class="form-label">جۆری دراو</label>
              <select class="form-select" id="currency" name="currency" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="note" class="form-label">تێبینی</label>
              <input type="text" class="form-control" id="note" name="note">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit Cash Box Modal -->
<div class="modal fade" id="editCashBoxModal" tabindex="-1" aria-labelledby="editCashBoxModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editCashBoxForm">
        <input type="hidden" id="edit_id" name="id">
        <div class="modal-header">
          <h5 class="modal-title" id="editCashBoxModalLabel">نوێکردنەوەی مامەڵەی قاسەکە</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="edit_date" name="date" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_type" class="form-label">جۆری مامەڵە</label>
              <select class="form-select" id="edit_type" name="type" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="deposit">زیادکردن</option>
                <option value="withdraw">کەمکردنەوە</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_amount_iqd" class="form-label">بڕی پارە بە دینار</label>
              <input type="number" class="form-control" id="edit_amount_iqd" name="amount_iqd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_amount_usd" class="form-label">بڕی پارە بە دۆلار</label>
              <input type="number" class="form-control" id="edit_amount_usd" name="amount_usd" min="0" step="0.01" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_currency" class="form-label">جۆری دراو</label>
              <select class="form-select" id="edit_currency" name="currency" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_note" class="form-label">تێبینی</label>
              <input type="text" class="form-control" id="edit_note" name="note">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">نوێکردنەوە</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/cash_box/add.js"></script>
<script src="../assets/js/cash_box/select.js"></script>
<script src="../assets/js/cash_box/delete.js"></script>
<script src="../assets/js/cash_box/update.js"></script>
<script src="../assets/js/cash_box/summary.js"></script>
<script>
// JS logic for dynamic updates can be added here
</script>
</body>
</html>
