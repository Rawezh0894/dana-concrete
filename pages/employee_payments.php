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
    
    <!-- Balance Cards -->
    <div class="row mb-4" id="balance-cards">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-hand-holding-usd card-icon"></i>
                    <h6 class="card-title">کۆی قەرزی کۆمپانیا</h6>
                    <div class="fs-4 fw-bold" id="total-payable">0 د.ع</div>
                    <small class="text-light">کۆی قەرزی کۆمپانیا بە کارمەندەکان</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow card-gradient-danger card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-money-check-alt card-icon"></i>
                    <h6 class="card-title">کۆی قەرزی کارمەند</h6>
                    <div class="fs-4 fw-bold" id="total-receivable">0 د.ع</div>
                    <small class="text-light">کۆی قەرزی کارمەندەکان بە کۆمپانیا</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-balance-scale card-icon"></i>
                    <h6 class="card-title">باڵانسی خالص</h6>
                    <div class="fs-4 fw-bold" id="net-balance">0 د.ع</div>
                    <small class="text-light">جیاوازی نێوان قەرزەکان</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow card-gradient-purple card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-users card-icon"></i>
                    <h6 class="card-title">ژمارەی کارمەند</h6>
                    <div class="fs-4 fw-bold" id="employee-count">0</div>
                    <small class="text-light">کۆی کارمەندەکان</small>
                </div>
            </div>
        </div>
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
    <div class="table-responsive mb-4">
        <h4 class="mb-3">خەرجی کارمەندەکان</h4>
        <table class="table table-bordered table-hover align-middle text-center" id="employeeExpensesTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>کارمەند</th>
                    <th>جۆری خەرجی</th>
                    <th>بڕ (د.ع)</th>
                    <th>مانگ</th>
                    <th>تێبینی</th>
                    <th>باڵانسی کارمەند</th>
                    <th>بەروار</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Expenses will be loaded here by JS -->
            </tbody>
        </table>
    </div>
    
    <div class="table-responsive">
        <h4 class="mb-3">پارەدانە کۆنەکان (سیستەمی کۆن - بۆ مێژوو)</h4>
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle"></i> 
            <strong>تێبینی:</strong> ئەم خشتەیە بۆ پارەدانە کۆنەکانە. 
            بۆ خەرجی نوێکان، لە خشتەی سەرەوە بەکار بهێنە.
            ئەم تەیبڵە باڵانس بە شێوەیەکی خۆکار ناگۆڕێت.
        </div>
        <table class="table table-bordered table-hover align-middle text-center" id="employeePaymentsTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>کارمەند</th>
                    <th>مووچە (د.ع)</th>
                    <th>کاروانحیسابی</th>
                    <th>بەخشیش (د.ع)</th>
                    <th>کۆی گشتی</th>
                    <th>مانگ</th>
                    <th>بەروار</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Payments will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addPaymentForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addPaymentModalLabel">زیادکردنی پارەدان</h5>
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
            <small class="form-text text-muted" id="employee-balance-info" style="display: none;"></small>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
            <label for="salary" class="form-label">مووچە (د.ع)</label>
              <input type="number" class="form-control" id="salary" name="salary" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="bonus" class="form-label">بەخشیش (د.ع)</label>
              <input type="number" class="form-control" id="bonus" name="bonus" min="0" step="0.01" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="overtime" class="form-label">کاروانحیسابی (د.ع)</label>
              <input type="number" class="form-control" id="overtime" name="overtime" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="advance" class="form-label">پێشەکی/قەرز (د.ع)</label>
              <input type="number" class="form-control" id="advance" name="advance" min="0" step="0.01" value="0">
              <small class="form-text text-muted">پێشەکی یەکەم لە مووچە (باڵانس) دەکەم</small>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="deduction" class="form-label">کەمکردنەوە (د.ع)</label>
              <input type="number" class="form-control" id="deduction" name="deduction" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="penalty" class="form-label">سزا (د.ع)</label>
              <input type="number" class="form-control" id="penalty" name="penalty" min="0" step="0.01" value="0">
            </div>
          </div>
          <div class="mb-3">
            <label for="total_add" class="form-label">کۆی گشتی</label>
            <input type="text" class="form-control" id="total_add" readonly>
            <small class="form-text text-muted">
              <strong>تێبینی:</strong> پێشەکی و کەمکردنەوە و سزا لە مووچە (باڵانس) دەکەم. 
              ئەگەر مووچە کەم بوو، زیاد بە قەرزی کارمەند دەکرێت.
            </small>
          </div>
          <div class="mb-3">
            <label for="expense_date" class="form-label">مانگ (YYYY-MM)</label>
            <input type="month" class="form-control" id="expense_date" name="expense_date" required>
          </div>
          <div class="mb-3">
            <label for="notes" class="form-label">تێبینی</label>
            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
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
<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editPaymentForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editPaymentModalLabel">دەستکاری پارەدان</h5>
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
            <label for="edit_salary" class="form-label">مووچە (د.ع)</label>
            <input type="text" class="form-control" id="edit_salary" name="salary" required>
          </div>
          <div class="mb-3">
            <label for="edit_karwanhisabi" class="form-label">کاروانحیسابی</label>
            <input type="text" class="form-control" id="edit_karwanhisabi" name="karwanhisabi" required>
          </div>
          <div class="mb-3">
            <label for="edit_bonus" class="form-label">بەخشیش (د.ع)</label>
            <input type="number" class="form-control" id="edit_bonus" name="bonus" min="0" step="0.01" value="0">
          </div>
          <div class="mb-3">
            <label for="total_edit" class="form-label">کۆی گشتی (مووچە + کاروانحیسابی + بەخشیش)</label>
            <input type="text" class="form-control" id="total_edit" readonly>
          </div>
          <div class="mb-3">
            <label for="edit_pay_month" class="form-label">مانگ</label>
            <input type="month" class="form-control" id="edit_pay_month" name="pay_month" required>
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
<script src="../assets/js/employee_payments/add_expense.js"></script>
<script src="../assets/js/employee_payments/select.js"></script>
<script src="../assets/js/employee_payments/select_expenses.js"></script>
<script src="../assets/js/employee_payments/balances.js"></script>
<script src="../assets/js/employee_payments/update.js"></script>
<script src="../assets/js/employee_payments/delete.js"></script>
<script src="../assets/js/employee_payments/summary.js"></script>
<script>
$(function() {
    function calcTotalAdd() {
        var salary = parseFloat($('#salary').val()) || 0;
        var bonus = parseFloat($('#bonus').val()) || 0;
        var overtime = parseFloat($('#overtime').val()) || 0;
        var advance = parseFloat($('#advance').val()) || 0;
        var deduction = parseFloat($('#deduction').val()) || 0;
        var penalty = parseFloat($('#penalty').val()) || 0;
        var total = salary + bonus + overtime + advance + deduction + penalty;
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
    $('#salary, #bonus, #overtime, #advance, #deduction, #penalty').on('input change', calcTotalAdd);
    $('#edit_salary, #edit_karwanhisabi, #edit_bonus').on('input change', calcTotalEdit);
    // Auto-fill salary in Add Payment Modal and show balance
    $('#employee_id').on('change', function() {
        var employeeId = $(this).val();
        var salary = $(this).find('option:selected').data('salary') || '';
        $('#salary').val(salary);
        calcTotalAdd();
        
        // Load and display employee balance
        if (employeeId) {
            $.get('../process/employee_payments/get_employee_current_balance.php', {employee_id: employeeId}, function(response) {
                if (response.success) {
                    var balanceInfo = $('#employee-balance-info');
                    var data = response.data;
                    var balanceText = 'باڵانسی ئێستا: ';
                    if (data.net_balance >= 0) {
                        balanceText += '<span class="text-success">' + data.balance_message + '</span>';
                    } else {
                        balanceText += '<span class="text-danger">' + data.balance_message + '</span>';
                    }
                    balanceInfo.html(balanceText).show();
                }
            }, 'json');
        } else {
            $('#employee-balance-info').hide();
        }
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
    
    // Set default month to current month
    var now = new Date();
    var month = (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
    $('#expense_date').val(month);
});
</script>
</body>
</html>
