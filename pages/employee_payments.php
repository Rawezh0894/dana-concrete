<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
require_once '../config/employee_ledger_schema.php';
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
ensureEmployeeLedgerSchema($pdo);
// Fetch employees for dropdown
$employees = $pdo->query('SELECT id, name, salary FROM employees ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حسابی کارمەند (Payroll Ledger)</title>
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
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">حسابی کارمەند (Ledger)</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPayrollModal" style="background: var(--seafoam-green); font-weight: bold;">+ تۆمارکردنی مووچە/حساب</button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDebitModal" style="font-weight: bold;">+ پارەدان/پێشەکی/سزا</button>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4" id="summary-cards">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-balance-scale card-icon"></i>
                    <h6 class="card-title">کۆی باڵانس</h6>
                    <div class="fs-4 fw-bold" id="total-balance">0 د.ع</div>
                    <small class="text-light">کۆی باڵانسی هەموو کارمەندەکان</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-user-tie card-icon"></i>
                    <h6 class="card-title">کۆی کریدەیت (مووچە/پاداشت)</h6>
                    <div class="fs-4 fw-bold" id="total-credit">0 د.ع</div>
                    <small class="text-light">لە مانگی هەڵبژێردراو</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-hand-holding-usd card-icon"></i>
                    <h6 class="card-title">کۆی پارەدان (Cash)</h6>
                    <div class="fs-4 fw-bold" id="total-paid">0 د.ع</div>
                    <small class="text-light">پارەی دراو (payment/advance)</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-purple card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-gavel card-icon"></i>
                    <h6 class="card-title">کۆی سزا</h6>
                    <div class="fs-4 fw-bold" id="total-penalty">0 د.ع</div>
                    <small class="text-light">سزاکانی مانگ</small>
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
        <table class="table table-bordered table-hover align-middle text-center" id="employeePaymentsTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>کارمەند</th>
                    <th>جۆر</th>
                    <th>ئۆپەڕەیشن</th>
                    <th>بڕ</th>
                    <th>مانگ</th>
                    <th>بەروار</th>
                    <th>تێبینی</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Payments will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Payroll (Credit) Modal -->
<div class="modal fade" id="addPayrollModal" tabindex="-1" aria-labelledby="addPayrollModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addPaymentForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addPayrollModalLabel">تۆمارکردنی مووچە/حساب (Credit)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" data-salary="<?= $emp['salary'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="salary" class="form-label">مووچە (د.ع)</label>
            <input type="text" class="form-control" id="salary" name="salary" required>
          </div>
          <div class="mb-3">
            <label for="karwanhisabi" class="form-label">کاروانحیسابی</label>
            <input type="text" class="form-control" id="karwanhisabi" name="karwanhisabi" required>
          </div>
          <div class="mb-3">
            <label for="bonus" class="form-label">بەخشیش (د.ع)</label>
            <input type="number" class="form-control" id="bonus" name="bonus" min="0" step="0.01" value="0">
          </div>
          <div class="mb-3">
            <label for="penalty" class="form-label">سزا (د.ع)</label>
            <input type="number" class="form-control" id="penalty" name="penalty" min="0" step="0.01" value="0">
          </div>
          <div class="mb-3">
            <label for="total_add" class="form-label">کۆی کریدەیت (مووچە + کاروانحیسابی + بەخشیش)</label>
            <input type="text" class="form-control" id="total_add" readonly>
          </div>
          <div class="mb-3">
            <label for="pay_month" class="form-label">مانگ</label>
            <input type="month" class="form-control" id="pay_month" name="pay_month" required>
          </div>
          <div class="mb-3">
            <label for="transaction_date" class="form-label">بەروار</label>
            <input type="date" class="form-control" id="transaction_date" name="transaction_date" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="mb-3">
            <label for="note" class="form-label">تێبینی</label>
            <input type="text" class="form-control" id="note" name="note" placeholder="تێبینی...">
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

<!-- Add Debit Modal (Payment / Advance / Penalty) -->
<div class="modal fade" id="addDebitModal" tabindex="-1" aria-labelledby="addDebitModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addDebitForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addDebitModalLabel">پارەدان/پێشەکی/سزا (Debit)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="debit_employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="debit_employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="debit_type" class="form-label">جۆر</label>
            <select class="form-select" id="debit_type" name="type" required>
              <option value="payment">پارەدان</option>
              <option value="advance">پێشەکی</option>
              <option value="penalty">سزا</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="debit_amount" class="form-label">بڕ (د.ع)</label>
            <input type="number" class="form-control" id="debit_amount" name="amount" min="0" step="0.01" required>
          </div>
          <div class="mb-3">
            <label for="debit_date" class="form-label">بەروار</label>
            <input type="date" class="form-control" id="debit_date" name="date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label for="debit_note" class="form-label">تێبینی</label>
            <input type="text" class="form-control" id="debit_note" name="note" placeholder="تێبینی...">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-primary" style="font-weight:bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit Transaction Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editPaymentForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editPaymentModalLabel">دەستکاری مامەڵە</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_payment_id" name="id">
          <div class="mb-3">
            <label for="edit_employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="edit_employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" data-salary="<?= $emp['salary'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_type" class="form-label">جۆر</label>
            <input type="text" class="form-control" id="edit_type" name="type" required>
          </div>
          <div class="mb-3">
            <label for="edit_operation" class="form-label">ئۆپەڕەیشن</label>
            <select class="form-select" id="edit_operation" name="operation" required>
              <option value="credit">credit</option>
              <option value="debit">debit</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_amount" class="form-label">بڕ (د.ع)</label>
            <input type="number" class="form-control" id="edit_amount" name="amount" min="0" step="0.01" required>
          </div>
          <div class="mb-3">
            <label for="edit_pay_month" class="form-label">مانگ</label>
            <input type="month" class="form-control" id="edit_pay_month" name="pay_month">
          </div>
          <div class="mb-3">
            <label for="edit_transaction_date" class="form-label">بەروار</label>
            <input type="datetime-local" class="form-control" id="edit_transaction_date" name="transaction_date" required>
          </div>
          <div class="mb-3">
            <label for="edit_description" class="form-label">تێبینی</label>
            <input type="text" class="form-control" id="edit_description" name="description">
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
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/employee_payments/add.js"></script>
<script src="../assets/js/employee_payments/make_payment.js"></script>
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
    $('#salary, #karwanhisabi, #bonus').on('input change', calcTotalAdd);
    // Auto-fill salary in Add Payment Modal
    $('#employee_id').on('change', function() {
        var salary = $(this).find('option:selected').data('salary') || '';
        $('#salary').val(salary);
        calcTotalAdd();
    });
    // Initial calculation
    calcTotalAdd();
});
</script>
</body>
</html>
