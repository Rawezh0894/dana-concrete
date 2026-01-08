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
// Check if bonus column exists
$bonusExists = false;
try {
    $checkColumns = $pdo->query("SHOW COLUMNS FROM employees LIKE 'bonus'");
    $bonusExists = $checkColumns->rowCount() > 0;
} catch (Exception $e) {
    // Column doesn't exist
}

if ($bonusExists) {
    $employees = $pdo->query('SELECT id, name, salary, COALESCE(bonus, 0) as bonus FROM employees ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
} else {
    $employees = $pdo->query('SELECT id, name, salary, 0 as bonus FROM employees ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی خەرجی کارمەندەکان</title>
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
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">بەڕێوەبردنی خەرجی کارمەندەکان</h2>
        <div>
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addIncomeExpenseModal" style="background: var(--seafoam-green); font-weight: bold;">
                <i class="fas fa-plus"></i> زیادکردنی مووچە/بەخشیش/کاروانحیسابی
            </button>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addDeductionExpenseModal" style="font-weight: bold;">
                <i class="fas fa-minus"></i> زیادکردنی پێشەکی/کەمکردنەوە/سزا
            </button>
        </div>
    </div>
    
    <!-- Employee Count Card -->
    <div class="row mb-4">
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
    
    <!-- Daily Balance Card (Special Card for Daily Balance Calculation) -->
    <div class="row mb-4" id="daily-balance-card-row" style="display: none;">
        <div class="col-12">
            <div class="card shadow-lg border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calculator me-2"></i>
                        باڵانسی ڕۆژانە (بە پێی ژمارەی ڕۆژەکانی مانگ)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-user-tie fa-2x text-primary mb-2"></i>
                                <h6 class="text-muted mb-1">کارمەند</h6>
                                <h5 class="mb-0" id="daily-balance-employee-name">-</h5>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-calendar-alt fa-2x text-info mb-2"></i>
                                <h6 class="text-muted mb-1">مانگ</h6>
                                <h5 class="mb-0" id="daily-balance-month">-</h5>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-calendar-day fa-2x text-warning mb-2"></i>
                                <h6 class="text-muted mb-1">ژمارەی ڕۆژەکان</h6>
                                <h5 class="mb-0">
                                    <span id="daily-balance-days-used">0</span> / <span id="daily-balance-days-total">0</span>
                                </h5>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                <h6 class="text-muted mb-1">مووچەی مانگانە</h6>
                                <h5 class="mb-0" id="daily-balance-monthly-salary">0 د.ع</h5>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-coins fa-2x text-info mb-2"></i>
                                    <h6 class="text-muted mb-2">نرخی ڕۆژانە</h6>
                                    <h4 class="text-info mb-0" id="daily-balance-daily-rate">0 د.ع/ڕۆژ</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-arrow-up fa-2x text-success mb-2"></i>
                                    <h6 class="text-muted mb-2">مووچەی بەدەستهاتوو</h6>
                                    <h4 class="text-success mb-0" id="daily-balance-earned-salary">0 د.ع</h4>
                                    <small class="text-muted" id="daily-balance-earned-details"></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <i class="fas fa-arrow-down fa-2x text-danger mb-2"></i>
                                    <h6 class="text-muted mb-2">پێشەکی وەرگیراو</h6>
                                    <h4 class="text-danger mb-0" id="daily-balance-advance-taken">0 د.ع</h4>
                                    <small class="text-muted" id="daily-balance-advance-details"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card" id="daily-balance-net-card">
                                <div class="card-body text-center">
                                    <h5 class="mb-3">
                                        <i class="fas fa-balance-scale me-2"></i>
                                        باڵانسی کۆتایی
                                    </h5>
                                    <h2 class="mb-0" id="daily-balance-net-balance">0 د.ع</h2>
                                    <p class="mb-0 mt-2" id="daily-balance-message"></p>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <div class="fs-4 fw-bold" id="total-overtime">0 د.ع</div>
                    <small class="text-light">کۆی کاروانحیسابی</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow card-gradient-danger card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-hand-holding-usd card-icon"></i>
                    <h6 class="card-title">کۆی پێشەکی</h6>
                    <div class="fs-4 fw-bold" id="total-advance">0 د.ع</div>
                    <small class="text-light">کۆی پێشەکی کارمەندەکان</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <label for="month-filter" class="form-label">فلتەر بە مانگ:</label>
            <select class="form-select" id="month-filter">
                <option value="">هەموو مانگەکان</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="employee-filter" class="form-label">فلتەر بە کارمەند:</label>
            <select class="form-select" id="employee-filter">
                <option value="">هەموو کارمەندەکان</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="daily-balance-employee-select" class="form-label">
                <i class="fas fa-calculator me-1"></i>
                کارمەند بۆ باڵانسی ڕۆژانە:
            </label>
            <select class="form-select" id="daily-balance-employee-select">
                <option value="">-- هەلبژێرە --</option>
                <?php foreach($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <!-- Expenses Table -->
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
</div>

<!-- Add Income Expense Modal (مووچە/بەخشیش/کاروانحیسابی) -->
<div class="modal fade" id="addIncomeExpenseModal" tabindex="-1" aria-labelledby="addIncomeExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addIncomeExpenseForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addIncomeExpenseModalLabel">زیادکردنی مووچە/بەخشیش/کاروانحیسابی</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="income_employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="income_employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" data-salary="<?= $emp['salary'] ?>" data-bonus="<?= $emp['bonus'] ?? 0 ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="form-text text-muted" id="income-employee-balance-info" style="display: none;"></small>
          </div>
          
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>تێبینی:</strong> دەتوانیت لە یەک کاتدا هەم مووچە و هەم بەخشیش و هەم کاروانحیسابی بنووسیت.
          </div>
          
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="income_salary" class="form-label">مووچە (د.ع)</label>
              <input type="number" class="form-control" id="income_salary" name="salary" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-4 mb-3">
              <label for="income_bonus" class="form-label">بەخشیش (د.ع)</label>
              <input type="number" class="form-control" id="income_bonus" name="bonus" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-4 mb-3">
              <label for="income_overtime" class="form-label">کاروانحیسابی (د.ع)</label>
              <input type="number" class="form-control" id="income_overtime" name="overtime" min="0" step="0.01" value="0">
            </div>
          </div>
          <div class="mb-3">
            <label for="income_total_add" class="form-label">کۆی گشتی</label>
            <input type="text" class="form-control" id="income_total_add" readonly>
          </div>
          <div class="mb-3">
            <label for="income_expense_date" class="form-label">مانگ (YYYY-MM)</label>
            <input type="month" class="form-control" id="income_expense_date" name="expense_date" required>
          </div>
          <div class="mb-3">
            <label for="income_notes" class="form-label">تێبینی</label>
            <textarea class="form-control" id="income_notes" name="notes" rows="2"></textarea>
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

<!-- Add Deduction Expense Modal (پێشەکی/کەمکردنەوە/سزا) -->
<div class="modal fade" id="addDeductionExpenseModal" tabindex="-1" aria-labelledby="addDeductionExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addDeductionExpenseForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addDeductionExpenseModalLabel">زیادکردنی پێشەکی/کەمکردنەوە/سزا</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="deduction_employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="deduction_employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="form-text text-muted" id="deduction-employee-balance-info" style="display: none;"></small>
          </div>
          
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>تێبینی:</strong> پێشەکی و کەمکردنەوە و سزا یەکەم لە مووچە (باڵانس) دەکەم. 
            ئەگەر مووچە کەم بوو، زیاد بە قەرزی کارمەند دەکرێت.
          </div>
          
          <div class="mb-3">
            <label for="deduction_expense_type" class="form-label">جۆری خەرجی</label>
            <select class="form-select" id="deduction_expense_type" name="expense_type" required>
              <option value="">-- هەلبژێرە --</option>
              <option value="advance">پێشەکی/قەرز</option>
              <option value="deduction">کەمکردنەوە</option>
              <option value="penalty">سزا</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="deduction_amount" class="form-label">بڕ (د.ع)</label>
            <input type="number" class="form-control" id="deduction_amount" name="amount" min="0" step="0.01" required>
          </div>
          
          <div class="mb-3">
            <label for="deduction_expense_date" class="form-label">مانگ (YYYY-MM)</label>
            <input type="month" class="form-control" id="deduction_expense_date" name="expense_date" required>
          </div>
          
          <div class="mb-3">
            <label for="deduction_notes" class="form-label">تێبینی</label>
            <textarea class="form-control" id="deduction_notes" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-warning" style="font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Update Expense Modal -->
<div class="modal fade" id="updateExpenseModal" tabindex="-1" aria-labelledby="updateExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="updateExpenseForm">
        <input type="hidden" id="update_expense_id" name="id">
        <div class="modal-header">
          <h5 class="modal-title" id="updateExpenseModalLabel">نوێکردنەوەی خەرجی کارمەند</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="update_employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="update_employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="update_expense_type" class="form-label">جۆری خەرجی</label>
            <select class="form-select" id="update_expense_type" name="expense_type" required>
              <option value="">-- هەلبژێرە --</option>
              <option value="salary">مووچە</option>
              <option value="bonus">بەخشیش</option>
              <option value="overtime">کاروانحیسابی</option>
              <option value="advance">پێشەکی</option>
              <option value="deduction">کەمکردنەوە</option>
              <option value="penalty">سزا</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="update_amount" class="form-label">بڕ (د.ع)</label>
            <input type="number" class="form-control" id="update_amount" name="amount" min="0" step="0.01" required>
          </div>
          
          <div class="mb-3">
            <label for="update_expense_date" class="form-label">مانگ (YYYY-MM)</label>
            <input type="month" class="form-control" id="update_expense_date" name="expense_date" required>
          </div>
          
          <div class="mb-3">
            <label for="update_notes" class="form-label">تێبینی</label>
            <textarea class="form-control" id="update_notes" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-primary" style="background: var(--seafoam-green); font-weight: bold;">نوێکردنەوە</button>
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
<script src="../assets/js/employee_payments/add_expense.js"></script>
<script src="../assets/js/employee_payments/select_expenses.js"></script>
<script src="../assets/js/employee_payments/balances.js"></script>
<script src="../assets/js/employee_payments/delete.js"></script>
<script src="../assets/js/employee_payments/update_expense.js"></script>
<script src="../assets/js/employee_payments/summary_expenses.js"></script>
<script>
$(function() {
    // Calculate total for Income Expense Modal (مووچە/بەخشیش/کاروانحیسابی)
    function calcIncomeTotal() {
        var salary = parseFloat($('#income_salary').val()) || 0;
        var bonus = parseFloat($('#income_bonus').val()) || 0;
        var overtime = parseFloat($('#income_overtime').val()) || 0;
        var total = salary + bonus + overtime;
        $('#income_total_add').val(total.toLocaleString('en-US') + ' د.ع');
    }
    
    $('#income_salary, #income_bonus, #income_overtime').on('input change', calcIncomeTotal);
    
    // Auto-fill salary, bonus, and overtime in Income Expense Modal and show balance
    $('#income_employee_id').on('change', function() {
        var employeeId = $(this).val();
        var salary = $(this).find('option:selected').data('salary') || '';
        var bonus = $(this).find('option:selected').data('bonus') || 0;
        $('#income_salary').val(salary);
        $('#income_bonus').val(bonus);
        
        // Load overtime amount based on concrete receipts
        if (employeeId) {
            var selectedMonth = $('#income_expense_date').val() || '';
            var params = {employee_id: employeeId};
            if (selectedMonth) {
                params.month = selectedMonth.substring(0, 7); // Extract YYYY-MM
            }
            
            // Get overtime amount
            $.get('../process/employee_payments/get_employee_overtime.php', params, function(response) {
                if (response.success) {
                    var overtimeAmount = parseFloat(response.data.overtime_amount) || 0;
                    $('#income_overtime').val(overtimeAmount.toFixed(2));
                    calcIncomeTotal();
                    
                    // Show overtime calculation details
                    var balanceInfo = $('#income-employee-balance-info');
                    var data = response.data;
                    var existingText = balanceInfo.html() || '';
                    var overtimeText = '<div class="small mt-2 border-top pt-2">';
                    overtimeText += '<strong>کاروانحیسابی:</strong><br>';
                    overtimeText += 'ژمارەی پسوڵە (میکسەر): ' + (data.mixer_receipt_count || 0) + '<br>';
                    if ((data.pump_receipt_count || 0) > 0) {
                        overtimeText += 'ژمارەی پسوڵە (پەمپ): ' + (data.pump_receipt_count || 0) + '<br>';
                    }
                    overtimeText += 'کۆی گشتی پسوڵەکان: ' + (data.total_receipts || 0) + '<br>';
                    overtimeText += 'کۆی گشتی مەتر: ' + parseFloat(data.total_meter || 0).toFixed(2) + ' م³<br>';
                    overtimeText += 'نرخی کاروانحیسابی: ' + parseFloat(data.overtime_rate || 0).toLocaleString('en-US') + ' د.ع/پسوڵە<br>';
                    overtimeText += '<strong>کۆی کاروانحیسابی: ' + (data.total_receipts || 0) + ' × ' + parseFloat(data.overtime_rate || 0).toLocaleString('en-US') + ' = ' + overtimeAmount.toLocaleString('en-US') + ' د.ع</strong>';
                    overtimeText += '</div>';
                    
                    if (existingText) {
                        balanceInfo.html(existingText + overtimeText);
                    } else {
                        balanceInfo.html(overtimeText).show();
                    }
                } else {
                    // If error, set overtime to 0 and log error
                    console.error('Error loading overtime:', response.message || 'Unknown error');
                    $('#income_overtime').val(0);
                    calcIncomeTotal();
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('AJAX Error loading overtime:', status, error, xhr.responseText);
                $('#income_overtime').val(0);
                calcIncomeTotal();
            });
        } else {
            $('#income_overtime').val(0);
            calcIncomeTotal();
        }
        
        // Load and display employee balance with daily calculation
        if (employeeId) {
            var selectedMonth = $('#income_expense_date').val() || '';
            var params = {employee_id: employeeId};
            if (selectedMonth) {
                params.month = selectedMonth.substring(0, 7); // Extract YYYY-MM
            }
            
            $.get('../process/employee_payments/get_employee_current_balance.php', params, function(response) {
                if (response.success) {
                    var balanceInfo = $('#income-employee-balance-info');
                    var data = response.data;
                    var balanceText = '<div class="small">';
                    balanceText += '<strong>باڵانسی ئێستا (بە پێی ڕۆژەکان):</strong><br>';
                    
                    if (data.net_balance >= 0) {
                        balanceText += '<span class="text-success">' + data.balance_message + '</span>';
                    } else {
                        balanceText += '<span class="text-danger">' + data.balance_message + '</span>';
                    }
                    
                    // Add calculation details
                    if (data.calculation_details) {
                        balanceText += '<br><small class="text-muted mt-2 d-block">';
                        balanceText += 'مووچەی مانگانە: ' + data.calculation_details.monthly_salary + '<br>';
                        balanceText += 'ژمارەی ڕۆژەکان: ' + data.calculation_details.days_used + ' / ' + data.calculation_details.days_in_month + '<br>';
                        balanceText += 'نرخی ڕۆژانە: ' + data.calculation_details.daily_salary_rate + '<br>';
                        balanceText += 'مووچەی بەدەستهاتوو: ' + data.calculation_details.earned_salary + '<br>';
                        if (parseFloat(data.calculation_details.advance_taken) > 0) {
                            balanceText += 'پێشەکی وەرگیراو: ' + data.calculation_details.advance_taken;
                        }
                        balanceText += '</small>';
                    }
                    
                    balanceText += '</div>';
                    
                    // Append to existing content (overtime info)
                    var existingContent = balanceInfo.html();
                    if (existingContent && existingContent.includes('کاروانحیسابی')) {
                        // Overtime info already exists, prepend balance info
                        balanceInfo.html(balanceText + existingContent);
                    } else {
                        balanceInfo.html(balanceText).show();
                    }
                }
            }, 'json');
        } else {
            $('#income-employee-balance-info').hide();
        }
    });
    
    // Load balance for Deduction Expense Modal
    $('#deduction_employee_id').on('change', function() {
        var employeeId = $(this).val();
        
        if (employeeId) {
            var selectedMonth = $('#deduction_expense_date').val() || '';
            var params = {employee_id: employeeId};
            if (selectedMonth) {
                params.month = selectedMonth.substring(0, 7);
            }
            
            $.get('../process/employee_payments/get_employee_current_balance.php', params, function(response) {
                if (response.success) {
                    var balanceInfo = $('#deduction-employee-balance-info');
                    var data = response.data;
                    var balanceText = '<div class="small">';
                    balanceText += '<strong>باڵانسی ئێستا:</strong><br>';
                    
                    if (data.net_balance >= 0) {
                        balanceText += '<span class="text-success">' + data.balance_message + '</span>';
                    } else {
                        balanceText += '<span class="text-danger">' + data.balance_message + '</span>';
                    }
                    balanceText += '</div>';
                    balanceInfo.html(balanceText).show();
                }
            }, 'json');
        } else {
            $('#deduction-employee-balance-info').hide();
        }
    });
    
    // Initial calculation
    calcIncomeTotal();
    
    // Set default month to current month for both modals
    var now = new Date();
    var month = (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
    $('#income_expense_date').val(month);
    $('#deduction_expense_date').val(month);
    
    // Reload balance and overtime when month changes in Income Modal
    $('#income_expense_date').on('change', function() {
        var employeeId = $('#income_employee_id').val();
        if (employeeId) {
            $('#income_employee_id').trigger('change');
        }
    });
    
    // Reload balance when month changes in Deduction Modal
    $('#deduction_expense_date').on('change', function() {
        var employeeId = $('#deduction_employee_id').val();
        if (employeeId) {
            $('#deduction_employee_id').trigger('change');
        }
    });
    
    // Initialize Select2 for employee filter
    $('#employee-filter').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'هەموو کارمەندەکان',
        allowClear: true,
        dir: 'rtl'
    });
    
    // Initialize Select2 for daily balance employee select
    $('#daily-balance-employee-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- هەلبژێرە --',
        allowClear: true,
        dir: 'rtl'
    });
    
    // Load daily balance when employee is selected
    $('#daily-balance-employee-select').on('change', function() {
        var employeeId = $(this).val();
        var selectedMonth = $('#month-filter').val() || '';
        
        if (employeeId) {
            loadDailyBalance(employeeId, selectedMonth);
        } else {
            $('#daily-balance-card-row').hide();
        }
    });
    
    // Reload daily balance when month filter changes
    $('#month-filter').on('change', function() {
        var employeeId = $('#daily-balance-employee-select').val();
        var selectedMonth = $(this).val() || '';
        
        if (employeeId) {
            loadDailyBalance(employeeId, selectedMonth);
        }
        
        // Also reload balance cards when month filter changes
        if (window.loadBalances) {
            window.loadBalances();
        }
    });
    
    // Reload balance cards when employee filter changes (Select2)
    $(document).on('change', '#employee-filter', function() {
        if (window.loadBalances) {
            window.loadBalances();
        }
    });
    
    // Function to load daily balance
    function loadDailyBalance(employeeId, month) {
        var params = {employee_id: employeeId};
        if (month) {
            params.month = month;
        }
        
        $.get('../process/employee_payments/get_employee_current_balance.php', params, function(response) {
            if (response.success) {
                var data = response.data;
                
                // Show the card
                $('#daily-balance-card-row').show();
                
                // Update employee name
                $('#daily-balance-employee-name').text(data.employee_name || '-');
                
                // Update month
                $('#daily-balance-month').text(data.month || '-');
                
                // Update days
                $('#daily-balance-days-used').text(data.days_used || 0);
                $('#daily-balance-days-total').text(data.days_in_month || 0);
                
                // Update monthly salary
                if (data.calculation_details && data.calculation_details.monthly_salary) {
                    $('#daily-balance-monthly-salary').text(data.calculation_details.monthly_salary);
                } else {
                    $('#daily-balance-monthly-salary').text(formatCurrency(data.monthly_salary || 0));
                }
                
                // Update daily rate
                if (data.calculation_details && data.calculation_details.daily_salary_rate) {
                    $('#daily-balance-daily-rate').text(data.calculation_details.daily_salary_rate);
                } else {
                    var dailyRate = data.days_in_month > 0 ? (data.monthly_salary || 0) / data.days_in_month : 0;
                    $('#daily-balance-daily-rate').text(formatCurrency(dailyRate) + '/ڕۆژ');
                }
                
                // Update earned salary
                $('#daily-balance-earned-salary').text(formatCurrency(data.total_earned_salary || 0));
                if (data.calculation_details) {
                    var earnedDetails = '';
                    if (data.calculation_details.monthly_salary && data.calculation_details.days_in_month && data.calculation_details.days_used) {
                        earnedDetails = data.calculation_details.monthly_salary + ' ÷ ' + 
                                      data.calculation_details.days_in_month + ' × ' + 
                                      data.calculation_details.days_used + ' = ' + 
                                      (data.calculation_details.earned_salary || formatCurrency(data.total_earned_salary));
                    }
                    $('#daily-balance-earned-details').text(earnedDetails);
                }
                
                // Update advance taken
                $('#daily-balance-advance-taken').text(formatCurrency(data.total_advance || 0));
                if (parseFloat(data.total_advance) > 0) {
                    $('#daily-balance-advance-details').text('پێشەکی بە پێی ڕۆژەکان');
                } else {
                    $('#daily-balance-advance-details').text('');
                }
                
                // Update net balance
                var netBalance = parseFloat(data.net_balance || 0);
                $('#daily-balance-net-balance').text(formatCurrency(Math.abs(netBalance)));
                
                // Update message and card color
                var netCard = $('#daily-balance-net-card');
                var message = $('#daily-balance-message');
                
                if (netBalance >= 0) {
                    netCard.removeClass('border-danger').addClass('border-success');
                    message.removeClass('text-danger').addClass('text-success');
                    message.html('<i class="fas fa-check-circle me-1"></i>کۆمپانیا قەرزی کارمەندە: ' + formatCurrency(netBalance));
                } else {
                    netCard.removeClass('border-success').addClass('border-danger');
                    message.removeClass('text-success').addClass('text-danger');
                    message.html('<i class="fas fa-exclamation-circle me-1"></i>کارمەند قەرزی کۆمپانیایە: ' + formatCurrency(Math.abs(netBalance)));
                }
            } else {
                $('#daily-balance-card-row').hide();
            }
        }, 'json').fail(function() {
            $('#daily-balance-card-row').hide();
        });
    }
    
    // Format currency helper function
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US').format(parseFloat(amount).toFixed(2)) + ' د.ع';
    }
});
</script>
</body>
</html>

