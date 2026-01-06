<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_employee_payment')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
// Fetch employees for dropdown
$employees = $pdo->query('SELECT id, name, salary FROM employees ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پارەدان بە کارمەندەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link href="../assets/css/comon/summary_cards.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">پارەدان بە کارمەندەکان</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی پارەدان</button>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4" id="summary-cards">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-money-bill-wave card-icon"></i>
                    <h6 class="card-title">کۆی پارەدان</h6>
                    <div class="fs-4 fw-bold" id="total-payments">0 د.ع</div>
                    <small class="text-light">کۆی پارەدان بە کارمەندەکان</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-user-tie card-icon"></i>
                    <h6 class="card-title">کۆی مووچە</h6>
                    <div class="fs-4 fw-bold" id="total-salary">0 د.ع</div>
                    <small class="text-light">کۆی مووچەی کارمەندەکان</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-gift card-icon"></i>
                    <h6 class="card-title">کۆی بەخشیش</h6>
                    <div class="fs-4 fw-bold" id="total-bonus">0 د.ع</div>
                    <small class="text-light">کۆی بەخشیشی کارمەندەکان</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-purple card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-calculator card-icon"></i>
                    <h6 class="card-title">کۆی کاروانحیسابی</h6>
                    <div class="fs-4 fw-bold" id="total-karwanhisabi">0 د.ع</div>
                    <small class="text-light">کۆی کاروانحیسابی</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <label for="month-filter" class="form-label">فلتەر بە مانگ:</label>
            <select class="form-select" id="month-filter">
                <option value="">هەموو مانگەکان</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="employee-filter" class="form-label">فلتەر بە کارمەند:</label>
            <select class="form-select" id="employee-filter">
                <option value="">هەموو کارمەندەکان</option>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="hrTransactionsTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>بەروار</th>
                    <th>کارمەند</th>
                    <th>جۆری مامەڵە</th>
                    <th>بڕ (دینار)</th>
                    <th>بڕ (دۆلار)</th>
                    <th>مانگ</th>
                    <th>تێبینی</th>
                    <th>بەرهەمهێنەر</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Transactions will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit HR Transaction Modal -->
<div class="modal fade" id="hrTransactionModal" tabindex="-1" aria-labelledby="hrTransactionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="hrTransactionForm">
        <div class="modal-header">
          <h5 class="modal-title" id="hrTransactionModalLabel">مامەڵەی نوێ (HR)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="transaction_id" name="id">
          <div class="mb-3">
            <label for="employee_id" class="form-label">کارمەند</label>
            <select class="form-select select2-enable" id="employee_id" name="employee_id" required>
              <option value="">-- هەڵبژێرە --</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" data-salary="<?= $emp['salary'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="transaction_type" class="form-label">جۆری مامەڵە</label>
            <select class="form-select" id="transaction_type" name="type" required>
              <option value="مووچە (Accrual)">مووچە (Accrual)</option>
              <option value="وەصڵ کردن (Payment)">وەصڵ کردن (Payment)</option>
              <option value="پاداشت (Bonus)">پاداشت (Bonus)</option>
              <option value="ئۆڤەر تایم (Overtime)">ئۆڤەر تایم (Overtime)</option>
              <option value="سزا (Penalty)">سزا (Penalty)</option>
              <option value="پێشەکی (Advance)">پێشەکی (Advance)</option>
            </select>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="amount_iqd" class="form-label">بڕ (دینار)</label>
              <input type="number" class="form-control" id="amount_iqd" name="amount_iqd" value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="amount_usd" class="form-label">بڕ (دۆلار)</label>
              <input type="number" class="form-control" id="amount_usd" name="amount_usd" value="0" step="0.01">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="pay_month" class="form-label">مانگ</label>
              <input type="month" class="form-control" id="pay_month" name="pay_month" value="<?= date('Y-m') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="transaction_date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="transaction_date" name="date" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="note" class="form-label">تێبینی</label>
            <textarea class="form-control" id="note" name="note" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success" id="saveTransactionBtn">پاشەکەوتکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/employee_payments/add.js"></script>
<script src="../assets/js/employee_payments/select.js"></script>
<script src="../assets/js/employee_payments/update.js"></script>
<script src="../assets/js/employee_payments/delete.js"></script>
<script src="../assets/js/employee_payments/summary.js"></script>
<script>
$(function() {
    function calcTotalAdd() {
        var salary = parseFloat($('#salary').val()) || 0;
        var karwan = $('#karwanhisabi').val().replace(/,/g, '');
        var karwanVal = parseFloat(karwan) || 0;
        var bonus = parseFloat($('#bonus').val()) || 0;
        var total = salary + karwanVal + bonus;
        $('#total_add').val(total.toLocaleString('en-US') + ' د.ع');
    }
    function calcTotalEdit() {
        var salary = parseFloat($('#edit_salary').val()) || 0;
        var karwan = $('#edit_karwanhisabi').val().replace(/,/g, '');
        var karwanVal = parseFloat(karwan) || 0;
        var bonus = parseFloat($('#edit_bonus').val()) || 0;
        var total = salary + karwanVal + bonus;
        $('#total_edit').val(total.toLocaleString('en-US') + ' د.ع');
    }
    $('#salary, #karwanhisabi, #bonus').on('input change', calcTotalAdd);
    $('#edit_salary, #edit_karwanhisabi, #edit_bonus').on('input change', calcTotalEdit);
    // Auto-fill salary in Add Payment Modal
    $('#employee_id').on('change', function() {
        var salary = $(this).find('option:selected').data('salary') || '';
        $('#salary').val(salary);
        calcTotalAdd();
    });
    // Auto-fill salary in Edit Payment Modal
    $('#edit_employee_id').on('change', function() {
        var salary = $(this).find('option:selected').data('salary') || '';
        $('#edit_salary').val(salary);
        calcTotalEdit();
    });
    // Initial calculation
    calcTotalAdd();
    calcTotalEdit();
});
</script>
</body>
</html>
