<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
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
// Loan issuance: same practical access as cash operations / payroll (server also checks).
$can_issue_employee_loan = hasPermission('add_payment') || hasPermission('add_cash_box');
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com" nonce="<?php echo $csp_nonce; ?>"></script>
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex flex-column flex-lg-row flex-wrap align-items-lg-center justify-content-between gap-3 mb-4" style="position: relative; z-index: 20;">
        <h2 class="mb-0 order-1 order-lg-0" style="color: var(--seafoam-green); font-weight: bold;">بەڕێوەبردنی خەرجی کارمەندەکان</h2>
        <div class="d-flex flex-wrap align-items-center gap-2 order-0 order-lg-1 justify-content-lg-end" style="position: relative; z-index: 21;">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addIncomeExpenseModal" style="background: var(--seafoam-green); font-weight: bold;">
                <i class="fas fa-plus"></i> زیادکردنی مووچە/بەخشیش/کاروانحیسابی
            </button>
            <button type="button"
                    id="btnIssueEmployeeLoan"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border-2 border-sky-800 bg-sky-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-sky-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300"
                    style="min-width: 10.5rem; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);"
                    data-bs-toggle="modal"
                    data-bs-target="#issueEmployeeLoanModal"
                    aria-haspopup="dialog">
                <i class="fas fa-hand-holding-usd" aria-hidden="true"></i>
                <span>قەرزی کارمەند</span>
            </button>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addDeductionExpenseModal" style="font-weight: bold;">
                <i class="fas fa-minus"></i> زیادکردنی پێشەکی/کەمکردنەوە/سزا
            </button>
        </div>
    </div>
    
    
    <!-- Filters -->
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <label for="month-filter" class="form-label">فلتەر بە مانگ:</label>
            <select class="form-select" id="month-filter">
                <option value="">هەموو مانگەکان</option>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label for="start-date" class="form-label">لە بەرواری:</label>
            <input type="date" class="form-control" id="start-date">
        </div>
        <div class="col-md-3 mb-3">
            <label for="end-date" class="form-label">بۆ بەرواری:</label>
            <input type="date" class="form-control" id="end-date">
        </div>
        <div class="col-md-3 mb-3">
            <label for="employee-filter" class="form-label">فلتەر بە کارمەند:</label>
            <select class="form-select" id="employee-filter">
                <option value="">هەموو کارمەندەکان</option>
            </select>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row g-3 mb-4 text-center">
        <!-- Income Cards & Net Pay -->
        <div class="col-12 col-md">
            <div class="card p-3 shadow-sm border-0 d-flex flex-column align-items-center justify-content-center h-100" style="background: linear-gradient(135deg, #e3f2fd, #bbdefb);">
                <i class="fas fa-money-bill-wave fa-2x mb-2 text-primary"></i>
                <h6 class="text-muted fw-bold">کۆی مووچە</h6>
                <h4 id="total-salary" class="mb-0 text-primary">0 د.ع</h4>
            </div>
        </div>
        <div class="col-12 col-md">
            <div class="card p-3 shadow-sm border-0 d-flex flex-column align-items-center justify-content-center h-100" style="background: linear-gradient(135deg, #f3e5f5, #e1bee7);">
                <i class="fas fa-gift fa-2x mb-2 text-purple" style="color: #9c27b0;"></i>
                <h6 class="text-muted fw-bold">کۆی بەخشیش</h6>
                <h4 id="total-bonus" class="mb-0" style="color: #9c27b0;">0 د.ع</h4>
            </div>
        </div>
        <div class="col-12 col-md">
            <div class="card p-3 shadow-sm border-0 d-flex flex-column align-items-center justify-content-center h-100" style="background: linear-gradient(135deg, #e8f5e9, #c8e6c9);">
                <i class="fas fa-coins fa-2x mb-2 text-success"></i>
                <h6 class="text-muted fw-bold">مووچە + بەخشیش</h6>
                <h4 id="total-salary-bonus" class="mb-0 text-success">0 د.ع</h4>
            </div>
        </div>
        
        <div class="col-12 col-md">
             <div class="card p-3 shadow-sm border-0 d-flex flex-column align-items-center justify-content-center h-100" style="background: linear-gradient(135deg, #e0f7fa, #b2ebf2);">
                <i class="fas fa-truck-mixer fa-2x mb-2 text-info"></i>
                <h6 class="text-muted fw-bold">کۆی کاروان حیسابی</h6>
                <h4 id="total-overtime" class="mb-0 text-info">0 د.ع</h4>
            </div>
        </div>
        
        <div class="col-12 col-md">
            <div class="card p-3 shadow-sm border-0 d-flex flex-column align-items-center justify-content-center h-100" style="background: linear-gradient(135deg, #fff3e0, #ffe0b2);">
                <i class="fas fa-balance-scale fa-2x mb-2 text-warning"></i>
                <h6 class="text-muted fw-bold">باڵانسی مووچە (Net Pay)</h6>
                <h4 id="net-payable" class="mb-0 text-warning">0 د.ع</h4>
                 <small class="text-muted" style="font-size: 0.7rem;">(مووچە+بەخشیش+کاروان) - (سزا+کەمکردنەوە+پێشەکی)</small>
            </div>
        </div>
        <div class="col-12 col-md">
            <div class="card p-3 shadow-sm border-0 d-flex flex-column align-items-center justify-content-center h-100" style="background: linear-gradient(135deg, #e1f5fe, #b3e5fc);">
                <i class="fas fa-calendar-day fa-2x mb-2 text-primary"></i>
                <h6 class="text-muted fw-bold">باڵانسی مووچە بە پێی ڕۆژ</h6>
                <h4 id="daily-balance" class="mb-0 text-primary">0 د.ع</h4>
                <small class="text-muted" style="font-size: 0.7rem;" id="daily-balance-details">هەتا ئەمڕۆ</small>
            </div>
        </div>
    </div>
    
    <div class="row g-3 mb-4 text-center">
        <!-- Deduction Cards -->
        <div class="col-12 col-md">
             <div class="card p-3 shadow-sm border-0 d-flex flex-column align-items-center justify-content-center h-100" style="background: #ffebee;">
                <i class="fas fa-hand-holding-usd fa-2x mb-2 text-danger"></i>
                <h6 class="text-muted fw-bold">پێشەکی / قەرز</h6>
                <h4 id="total-advance" class="mb-0 text-danger">0 د.ع</h4>
            </div>
        </div>
        <div class="col-12 col-md">
             <div class="card p-3 shadow-sm border-0 d-flex flex-column align-items-center justify-content-center h-100" style="background: #ffebee;">
                <i class="fas fa-minus-circle fa-2x mb-2 text-danger"></i>
                <h6 class="text-muted fw-bold">کەمکردنەوە</h6>
                <h4 id="total-deduction" class="mb-0 text-danger">0 د.ع</h4>
            </div>
        </div>
        <div class="col-12 col-md">
             <div class="card p-3 shadow-sm border-0 d-flex flex-column align-items-center justify-content-center h-100" style="background: #ffebee;">
                <i class="fas fa-gavel fa-2x mb-2 text-danger"></i>
                <h6 class="text-muted fw-bold">سزا</h6>
                <h4 id="total-penalty" class="mb-0 text-danger">0 د.ع</h4>
            </div>
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
                    <th>بڕ / قاسە</th>
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
            <label for="income_total_add" class="form-label">کۆی خەرجی (حساب — د.ع)</label>
            <input type="text" class="form-control" id="income_total_add" readonly>
          </div>

          <div class="rounded-xl border border-teal-200 bg-teal-50/80 p-4 mb-3 text-start shadow-sm" dir="rtl">
            <p class="text-sm font-bold text-teal-900 mb-3 flex items-center gap-2 border-b border-teal-200 pb-2">
              <i class="fas fa-vault text-teal-600"></i> قاسە — ئەم بڕانە ڕاستەوخۆ لە قاسە دەکەم
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl mx-auto">
              <div class="sm:col-span-1">
                <label for="income_amount_usd" class="block text-sm font-medium text-slate-700 mb-1">بڕی پارە بە دۆلار</label>
                <div class="flex items-center gap-2">
                  <span class="text-slate-500 text-sm">$</span>
                  <input type="number" min="0" step="0.01" value="0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" id="income_amount_usd" name="amount_usd">
                </div>
              </div>
              <div class="sm:col-span-1">
                <label for="income_amount_iqd" class="block text-sm font-medium text-slate-700 mb-1">بڕی پارە بە دینار</label>
                <input type="number" min="0" step="0.01" value="0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" id="income_amount_iqd" name="amount_iqd">
                <span class="text-xs text-slate-500 mt-1 block">دینار (د.ع)</span>
              </div>
              <div class="sm:col-span-2">
                <label for="income_exchange_rate" class="block text-sm font-medium text-slate-700 mb-1">نرخی گۆڕینەوە — ١ دۆلار بە چەند؟</label>
                <input type="number" min="0" step="0.0001" value="0" class="w-full max-w-md rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" id="income_exchange_rate" name="exchange_rate" placeholder="نمونە: 1500">
              </div>
            </div>
            <div class="mt-4 rounded-lg bg-white/90 border border-teal-100 px-3 py-2 text-sm">
              <span class="text-slate-600">کۆی خەرجی بە دینار (هاوتا):</span>
              <strong class="text-teal-800 ms-1" id="income_cash_equiv_display">0</strong>
              <span class="text-slate-500">د.ع</span>
              <span class="text-xs text-slate-500 d-block mt-1" id="income_cash_equiv_hint">(دۆلار × نرخ) + دینار — دەبێت یەکسان بێت بە کۆی خەرجی لە سەرەوە</span>
            </div>
            <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50/90 p-4 text-start shadow-sm" dir="rtl">
              <p class="text-sm font-bold text-indigo-900 mb-2 flex items-center gap-2 border-b border-indigo-200 pb-2">
                <i class="fas fa-piggy-bank text-indigo-600"></i> قەرزی کارمەند (کەمکردنەوە لە مووچە)
              </p>
              <div class="text-xs text-indigo-800 mb-3 rounded-lg bg-white/80 px-2 py-2 border border-indigo-100">
                <span class="text-slate-600">قەرزی ماوە:</span>
                <strong id="income_loan_outstanding_usd">0</strong> <span class="text-slate-500">$</span>
                <span class="mx-2 text-slate-300">|</span>
                <strong id="income_loan_outstanding_iqd">0</strong> <span class="text-slate-500">د.ع</span>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-2xl mx-auto">
                <div>
                  <label for="income_deduct_loan_usd" class="block text-sm font-medium text-slate-700 mb-1">کەمکردنەوەی قەرز بە دۆلار (ئارەزوومەندانە)</label>
                  <div class="flex items-center gap-2">
                    <span class="text-slate-500 text-sm">$</span>
                    <input type="number" min="0" step="0.01" value="0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" id="income_deduct_loan_usd" name="deduct_loan_usd" autocomplete="off">
                  </div>
                </div>
                <div>
                  <label for="income_deduct_loan_iqd" class="block text-sm font-medium text-slate-700 mb-1">کەمکردنەوەی قەرز بە دینار (ئارەزوومەندانە)</label>
                  <input type="number" min="0" step="0.01" value="0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" id="income_deduct_loan_iqd" name="deduct_loan_iqd" autocomplete="off">
                </div>
              </div>
              <p class="text-xs text-indigo-800 mt-3 mb-0">
                <strong>پارەی خاو لە قاسە (دوای قەرز):</strong>
                <span id="income_net_cash_display">0</span> د.ع
                <span class="text-slate-500 d-block mt-1">ئەم بڕە دەبێت لە خانەکانی قاسە لە سەرەوە بنووسرێت (نابێت دووبارە لە قاسە دەرچێت بۆ بەشی قەرز).</span>
              </p>
            </div>
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

<!-- Issue employee loan (cash box outflow) — always in DOM for Bootstrap target -->
<div class="modal fade" id="issueEmployeeLoanModal" tabindex="-1" aria-labelledby="issueEmployeeLoanModalLabel" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="z-index: 1061;">
    <div class="modal-content">
      <form id="issueEmployeeLoanForm">
        <div class="modal-header">
          <h5 class="modal-title" id="issueEmployeeLoanModalLabel">قەرزی کارمەند — دەرچوون لە قاسە</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning small mb-3">بڕی دۆلار و/یان دینار ڕاستەوخۆ لە قاسە دەکەم وەک «Employee Loan Issued». دواتر لە کاتی مووچەدا دەتوانیت قەرز کەم بکەیتەوە.</div>
          <div class="mb-3">
            <label for="loan_employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="loan_employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach ($employees as $emp): ?>
                <option value="<?= (int) $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" dir="rtl">
            <div>
              <label for="loan_usd" class="form-label">بڕی قەرز بە دۆلار ($)</label>
              <input type="number" class="form-control" id="loan_usd" name="loan_usd" min="0" step="0.01" value="0">
            </div>
            <div>
              <label for="loan_iqd" class="form-label">بڕی قەرز بە دینار (د.ع)</label>
              <input type="number" class="form-control" id="loan_iqd" name="loan_iqd" min="0" step="0.01" value="0">
            </div>
            <div class="sm:col-span-2">
              <label for="loan_date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="loan_date" name="loan_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
            </div>
            <div class="sm:col-span-2">
              <label for="loan_notes" class="form-label">تێبینی</label>
              <textarea class="form-control" id="loan_notes" name="notes" rows="2" placeholder="ئارەزوومەندانە"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-primary" style="background: var(--seafoam-green); font-weight: bold;">تۆمارکردن و دەرچوون لە قاسە</button>
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
              <option value="overtime_payment">کاروان حیسابی (پێدان)</option>
            </select>
          </div>
          
          <div class="rounded-xl border border-amber-200 bg-amber-50/90 p-4 mb-3 text-start shadow-sm" dir="rtl">
            <p class="text-sm font-bold text-amber-900 mb-3 flex items-center gap-2 border-b border-amber-200 pb-2">
              <i class="fas fa-vault text-amber-600"></i> قاسە — ڕاستەوخۆ لە قاسە دەکەم
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl mx-auto">
              <div>
                <label for="deduction_amount_usd" class="block text-sm font-medium text-slate-700 mb-1">بڕی پارە بە دۆلار</label>
                <div class="flex items-center gap-2">
                  <span class="text-slate-500 text-sm">$</span>
                  <input type="number" min="0" step="0.01" value="0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" id="deduction_amount_usd">
                </div>
              </div>
              <div>
                <label for="deduction_amount_iqd" class="block text-sm font-medium text-slate-700 mb-1">بڕی پارە بە دینار</label>
                <input type="number" min="0" step="0.01" value="0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" id="deduction_amount_iqd">
                <span class="text-xs text-slate-500 mt-1 block">دینار (د.ع)</span>
              </div>
              <div class="sm:col-span-2">
                <label for="deduction_exchange_rate" class="block text-sm font-medium text-slate-700 mb-1">نرخی گۆڕینەوە — ١ دۆلار بە چەند؟</label>
                <input type="number" min="0" step="0.0001" value="0" class="w-full max-w-md rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" id="deduction_exchange_rate" placeholder="1500">
              </div>
            </div>
            <div class="mt-4 rounded-lg bg-white/90 border border-amber-100 px-3 py-2 text-sm">
              <span class="text-slate-600">کۆی خەرجی بە دینار (حساب):</span>
              <strong class="text-amber-900 ms-1" id="deduction_ledger_total_display">0</strong>
              <span class="text-slate-500">د.ع</span>
              <span class="text-xs text-slate-500 d-block mt-1">(دۆلار × نرخ) + دینار</span>
            </div>
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
              <option value="overtime_payment">پێدانی کاروانحیسابی</option>
            </select>
          </div>
          
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 mb-3 text-start shadow-sm" dir="rtl">
            <p class="text-sm font-bold text-slate-800 mb-3 border-b border-slate-200 pb-2">قاسە</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl mx-auto">
              <div>
                <label for="update_amount_usd" class="block text-sm font-medium text-slate-700 mb-1">بڕی پارە بە دۆلار</label>
                <div class="flex items-center gap-2">
                  <span class="text-slate-500 text-sm">$</span>
                  <input type="number" min="0" step="0.01" value="0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" id="update_amount_usd" name="amount_usd">
                </div>
              </div>
              <div>
                <label for="update_amount_iqd" class="block text-sm font-medium text-slate-700 mb-1">بڕی پارە بە دینار</label>
                <input type="number" min="0" step="0.01" value="0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" id="update_amount_iqd" name="amount_iqd">
              </div>
              <div class="sm:col-span-2">
                <label for="update_exchange_rate" class="block text-sm font-medium text-slate-700 mb-1">نرخی گۆڕینەوە — ١ دۆلار بە چەند؟</label>
                <input type="number" min="0" step="0.0001" value="0" class="w-full max-w-md rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" id="update_exchange_rate" name="exchange_rate">
              </div>
            </div>
            <div class="mt-3 text-sm">
              <span class="text-slate-600">کۆی خەرجی بە دینار:</span>
              <strong id="update_ledger_display" class="text-slate-900 ms-1">0</strong> د.ع
            </div>
            <input type="hidden" id="update_amount" name="amount" value="0">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/table-controler.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/employee_payments/add_expense.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/employee_payments/select_expenses.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/employee_payments/balances.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/employee_payments/delete.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/employee_payments/update_expense.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/employee_payments/summary_expenses.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script nonce="<?php echo $csp_nonce; ?>">
window.CAN_ISSUE_EMPLOYEE_LOAN = <?php echo $can_issue_employee_loan ? 'true' : 'false'; ?>;
</script>
<script nonce="<?php echo $csp_nonce; ?>">
$(function() {
    if (!window.CAN_ISSUE_EMPLOYEE_LOAN) {
        $('#btnIssueEmployeeLoan').removeAttr('data-bs-toggle').removeAttr('data-bs-target');
        $('#btnIssueEmployeeLoan').on('click', function () {
            swalAlert('هەڵە', 'ئەم کردارە پێویستی ڕێگەی «زیادکردنی پارەدان» یان «زیادکردنی قاسە» هەیە. تکایە لە بەڕێوەبەری سیستەم بپرسە.', 'error');
        });
    }
    // Calculate total for Income Expense Modal (مووچە/بەخشیش/کاروانحیسابی)
    function calcIncomeTotal() {
        var salary = parseFloat($('#income_salary').val()) || 0;
        var bonus = parseFloat($('#income_bonus').val()) || 0;
        var overtime = parseFloat($('#income_overtime').val()) || 0;
        var total = salary + bonus + overtime;
        $('#income_total_add').val(total.toLocaleString('en-US') + ' د.ع');
        refreshIncomeCashEquiv();
    }
    function incomeLoanDeductionEquiv() {
        var u = parseFloat($('#income_deduct_loan_usd').val()) || 0;
        var iq = parseFloat($('#income_deduct_loan_iqd').val()) || 0;
        var rate = parseFloat($('#income_exchange_rate').val()) || 0;
        if (u > 0 && rate <= 0) {
            return null;
        }
        return Math.round((iq + u * rate) * 100) / 100;
    }
    function loadIncomeLoanBalance(employeeId) {
        if (!employeeId) {
            $('#income_loan_outstanding_usd').text('0');
            $('#income_loan_outstanding_iqd').text('0');
            $('#income_deduct_loan_usd').data('maxOutstanding', 0);
            $('#income_deduct_loan_iqd').data('maxOutstanding', 0);
            return;
        }
        $.get('../process/employee_payments/get_employee_loan_balance.php', { employee_id: employeeId }, function (r) {
            if (r.success) {
                $('#income_loan_outstanding_usd').text(parseFloat(r.outstanding_usd || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 }));
                $('#income_loan_outstanding_iqd').text(parseFloat(r.outstanding_iqd || 0).toLocaleString('en-US', { maximumFractionDigits: 0 }));
                $('#income_deduct_loan_usd').data('maxOutstanding', parseFloat(r.outstanding_usd || 0));
                $('#income_deduct_loan_iqd').data('maxOutstanding', parseFloat(r.outstanding_iqd || 0));
            }
        }, 'json');
    }
    function refreshIncomeCashEquiv() {
        var salary = parseFloat($('#income_salary').val()) || 0;
        var bonus = parseFloat($('#income_bonus').val()) || 0;
        var overtime = parseFloat($('#income_overtime').val()) || 0;
        var gross = salary + bonus + overtime;
        var usd = parseFloat($('#income_amount_usd').val()) || 0;
        var iqd = parseFloat($('#income_amount_iqd').val()) || 0;
        var rate = parseFloat($('#income_exchange_rate').val()) || 0;
        var eq = Math.round((iqd + usd * rate) * 100) / 100;
        if (usd > 0 && rate <= 0) {
            $('#income_cash_equiv_display').text('— (نرخ پێویستە)');
        } else {
            $('#income_cash_equiv_display').text(eq.toLocaleString('en-US'));
        }
        var le = incomeLoanDeductionEquiv();
        if (le === null) {
            $('#income_net_cash_display').text('—');
            $('#income_cash_equiv_hint').text('(دۆلار × نرخ) + دینار — بۆ قەرزی دۆلار نرخ پێویستە');
            return;
        }
        var net = Math.round((gross - le) * 100) / 100;
        $('#income_net_cash_display').text(net.toLocaleString('en-US'));
        $('#income_cash_equiv_hint').text('(دۆلار × نرخ) + دینار دەبێت یەکسان بێت بە کۆی خەرجی (' + gross.toLocaleString('en-US') + ') − قەرز (' + le.toLocaleString('en-US') + ') = ' + net.toLocaleString('en-US') + ' د.ع');
    }
    $('#income_salary, #income_bonus, #income_overtime, #income_amount_usd, #income_amount_iqd, #income_exchange_rate, #income_deduct_loan_usd, #income_deduct_loan_iqd').on('input change', function() {
        calcIncomeTotal();
    });
    
    // Auto-fill salary, bonus, and overtime in Income Expense Modal and show balance
    $('#income_employee_id').on('change', function() {
        var employeeId = $(this).val();
        loadIncomeLoanBalance(employeeId);
        if (!employeeId) {
            $('#income_deduct_loan_usd').val(0);
            $('#income_deduct_loan_iqd').val(0);
        }
        var salary = $(this).find('option:selected').data('salary') || '';
        var bonus = $(this).find('option:selected').data('bonus') || 0;
        $('#income_salary').val(salary);
        $('#income_bonus').val(bonus);
        
        // Load overtime amount based on concrete receipts (only for employees with role "شۆفێری میکسەر")
        if (employeeId) {
            // First check if employee has role "شۆفێری میکسەر"
            var selectedEmployee = $('#income_employee_id option:selected');
            var employeeName = selectedEmployee.text();
            
            // Get employee role from server
            $.get('../process/employee/get_employee_role.php', {employee_id: employeeId}, function(roleResponse) {
                var hasMixerRole = false;
                if (roleResponse.success && roleResponse.role) {
                    var role = roleResponse.role;
                    hasMixerRole = role.includes('شۆفێری میکسەر');
                }
                
                if (!hasMixerRole) {
                    // Employee doesn't have mixer role, set overtime to 0
                    $('#income_overtime').val(0);
                    calcIncomeTotal();
                    $('#income-employee-balance-info').html('<div class="small text-muted">کاروان حیسابی تەنها بۆ کارمەندەکانی بە ڕۆڵی "شۆفێری میکسەر" هەژمار دەکرێت.</div>').show();
                    return;
                }
                
                // Employee has mixer role, proceed with overtime calculation
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
            }, 'json').fail(function() {
                // If can't get role, set overtime to 0
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
    
    // Initialize Select2 for employee selects in modals
    $('#income_employee_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- هەلبژێرە --',
        allowClear: true,
        dir: 'rtl',
        dropdownParent: $('#addIncomeExpenseModal')
    });
    
    $('#deduction_employee_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- هەلبژێرە --',
        allowClear: true,
        dir: 'rtl',
        dropdownParent: $('#addDeductionExpenseModal')
    });
    
    $('#update_employee_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- هەلبژێرە --',
        allowClear: true,
        dir: 'rtl',
        dropdownParent: $('#updateExpenseModal')
    });

    if ($('#loan_employee_id').length) {
        $('#loan_employee_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- هەلبژێرە --',
            allowClear: true,
            dir: 'rtl',
            dropdownParent: $('#issueEmployeeLoanModal')
        });
        $('#issueEmployeeLoanForm').on('submit', function (e) {
            e.preventDefault();
            if (!window.CAN_ISSUE_EMPLOYEE_LOAN) {
                swalAlert('هەڵە', 'ڕێگەپێدراوە نییە.', 'error');
                return;
            }
            var usd = parseFloat($('#loan_usd').val()) || 0;
            var iqd = parseFloat($('#loan_iqd').val()) || 0;
            if (usd <= 0 && iqd <= 0) {
                swalAlert('هەڵە', 'لانیکەم یەک بڕ بنووسە', 'error');
                return;
            }
            $.post('../process/employee_payments/issue_employee_loan.php', $(this).serialize(), function (res) {
                if (res.success) {
                    swalAlert('سەرکەوتوو', res.message || 'تۆمارکرا', 'success');
                    $('#issueEmployeeLoanForm')[0].reset();
                    $('#loan_date').val(new Date().toISOString().slice(0, 10));
                    var emp = $('#income_employee_id').val();
                    if (emp) {
                        loadIncomeLoanBalance(emp);
                    }
                    $('#issueEmployeeLoanModal').modal('hide');
                    setTimeout(function () {
                        if (window.loadBalances) {
                            window.loadBalances();
                        }
                    }, 400);
                } else {
                    swalAlert('هەڵە', res.message || 'هەڵە', 'error');
                }
            }, 'json').fail(function (xhr) {
                var msg = 'هەڵەی پەیوەندی';
                try {
                    var j = JSON.parse(xhr.responseText);
                    if (j.message) {
                        msg = j.message;
                    }
                } catch (err) { /* ignore */ }
                swalAlert('هەڵە', msg, 'error');
            });
        });
    }
    
    // Reload balance cards when employee filter changes (Select2)
    $(document).on('change', '#employee-filter', function() {
        if (window.loadBalances) {
            window.loadBalances();
        }
    });
});
</script>
</body>
</html>

