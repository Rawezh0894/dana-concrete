<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!hasPermission('view_accounts')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کۆمپانیاکان</title>
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
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">کۆمپانیاکان</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCompanyModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی کۆمپانیا</button>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4" id="summary-cards">
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow" style="background: linear-gradient(135deg, #00b894, #00cec9); color: white;">
                <div class="card-body">
                    <h5 class="card-title">کۆی قەرزی ئێمە لەگەڵ کۆمپانیان</h5>
                    <span id="total_debt" style="font-size:2.5rem;font-weight:bold;">$0</span>
                    <small class="text-light">دۆلار</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow" style="background: linear-gradient(135deg, #fdcb6e, #e17055); color: white;">
                <div class="card-body">
                    <h5 class="card-title">کۆی کۆمپانیان</h5>
                    <span id="total_companies" style="font-size:2.5rem;font-weight:bold;">0</span>
                    <small class="text-light">کۆمپانیا</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow" style="background: linear-gradient(135deg, #6c5ce7, #a29bfe); color: white;">
                <div class="card-body">
                    <h5 class="card-title">کۆمپانیاکانی قەرز</h5>
                    <span id="companies_with_debt" style="font-size:2.5rem;font-weight:bold;">0</span>
                    <small class="text-light">کۆمپانیا</small>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="companyTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>ناوی کۆمپانیا</th>
                    <th>قەرزی سەرەتایی (USD)</th>
                    <th>قەرزی سەرەتایی (IQD)</th>
                    <th>جۆری مامەڵە لەگەڵ کۆمپانیا</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Companies will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Company Modal -->
<div class="modal fade" id="addCompanyModal" tabindex="-1" aria-labelledby="addCompanyModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addCompanyForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addCompanyModalLabel">زیادکردنی کۆمپانیا</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="name" class="form-label">ناوی کۆمپانیا</label>
            <input type="text" class="form-control" id="name" name="name" required value="">
          </div>
          <div class="mb-3">
            <label for="currency_type" class="form-label">جۆری مامەڵە لەگەڵ کۆمپانیا</label>
            <select class="form-select" id="currency_type" name="currency_type" required>
              <option value="" selected disabled>-- جۆری دراو هەڵبژێرە --</option>
              <option value="دینار">دینار</option>
              <option value="دۆلار">دۆلار</option>
            </select>
            <div class="invalid-feedback">تکایە جۆری دراو هەڵبژێرە</div>
          </div>
          <div class="mb-3">
            <label for="opening_debt_usd" class="form-label">قەرزی سەرەتایی (USD)</label>
            <input type="number" class="form-control" id="opening_debt_usd" name="opening_debt_usd" min="0" step="0.01" value="0">
          </div>
          <div class="mb-3">
            <label for="opening_debt_iqd" class="form-label">قەرزی سەرەتایی (IQD)</label>
            <input type="number" class="form-control" id="opening_debt_iqd" name="opening_debt_iqd" min="0" step="1" value="0">
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
<!-- Edit Company Modal (to be filled by JS) -->
<div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editCompanyForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editCompanyModalLabel">دەستکاری کۆمپانیا</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editCompanyId" name="id">
          <div class="mb-3">
            <label for="editName" class="form-label">ناوی کۆمپانیا</label>
            <input type="text" class="form-control" id="editName" name="name" required>
          </div>
          <div class="mb-3">
            <label for="editCurrencyType" class="form-label">جۆری مامەڵە</label>
            <select class="form-select" id="editCurrencyType" name="currency_type" required>
              <option value="" selected disabled>-- جۆری مامەڵە هەڵبژێرە --</option>
              <option value="دینار">دینار</option>
              <option value="دۆلار">دۆلار</option>
            </select>
            <div class="invalid-feedback">تکایە جۆری مامەڵە هەڵبژێرە</div>
          </div>
          <div class="mb-3">
            <label for="editOpeningDebtUsd" class="form-label">قەرزی سەرەتایی (USD)</label>
            <input type="number" class="form-control" id="editOpeningDebtUsd" name="opening_debt_usd" min="0" step="0.01">
          </div>
          <div class="mb-3">
            <label for="editOpeningDebtIqd" class="form-label">قەرزی سەرەتایی (IQD)</label>
            <input type="number" class="form-control" id="editOpeningDebtIqd" name="opening_debt_iqd" min="0" step="1">
          </div>
       
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background:var(--seafoam-green); color:white;">نوێکردنەوە</button>
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
<script src="../assets/js/company/summary_stats.js"></script>
<script src="../assets/js/company/add_company.js"></script>
<script src="../assets/js/company/select_company.js"></script>
<script src="../assets/js/company/update_company.js"></script>
<script src="../assets/js/company/delete_company.js"></script>
</body>
</html>
