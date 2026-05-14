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
// Fetch employees for dropdowns (reuse $employees everywhere on this page).
$employees = [];
$bonusExists = false;
try {
    $checkColumns = $pdo->query("SHOW COLUMNS FROM employees LIKE 'bonus'");
    $bonusExists = $checkColumns && $checkColumns->rowCount() > 0;
} catch (Exception $e) {
    // ignore
}
try {
    if ($bonusExists) {
        $stmt = $pdo->query('SELECT id, name, salary, COALESCE(bonus, 0) AS bonus FROM employees ORDER BY name ASC');
    } else {
        $stmt = $pdo->query('SELECT id, name, salary, 0 AS bonus FROM employees ORDER BY name ASC');
    }
    $employees = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    error_log('employee_expenses.php employee list: ' . $e->getMessage());
    $employees = [];
}
// Loan issuance / direct repayment: same practical access as cash operations / payroll (server also checks).
$can_issue_employee_loan = hasPermission('add_payment') || hasPermission('add_cash_box');

$active_employee_loans = [];
try {
    $chkLoans = $pdo->query("SHOW TABLES LIKE 'employee_loans'");
    if ($chkLoans && $chkLoans->rowCount() > 0) {
        $stLoans = $pdo->query(
            "SELECT el.id AS loan_id, el.employee_id, e.name AS employee_name,
                    el.remaining_usd, el.remaining_iqd, el.loan_date
             FROM employee_loans el
             INNER JOIN employees e ON e.id = el.employee_id
             WHERE el.status = 'active' AND (el.remaining_usd > 0.005 OR el.remaining_iqd > 0.005)
             ORDER BY e.name ASC, el.loan_date ASC, el.id ASC"
        );
        $active_employee_loans = $stLoans ? $stLoans->fetchAll(PDO::FETCH_ASSOC) : [];
    }
} catch (Exception $e) {
    error_log('employee_expenses.php active loans: ' . $e->getMessage());
    $active_employee_loans = [];
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

    <!-- Active employee loans -->
    <section class="mb-5 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden" dir="rtl">
        <div class="border-b border-slate-200 bg-gradient-to-l from-slate-50 to-white px-4 py-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-lg font-bold text-slate-800 m-0 flex items-center gap-2">
                <i class="fas fa-piggy-bank text-sky-600" aria-hidden="true"></i>
                قەرزی کارمەندەکان <span class="text-sm font-normal text-slate-500">(چالاک)</span>
            </h3>
            <span class="text-xs text-slate-500">remaining_usd / remaining_iqd</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-right font-semibold text-slate-700">#</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold text-slate-700">کارمەند</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold text-slate-700">بەرواری قەرز</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold text-slate-700">ماوە ($)</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold text-slate-700">ماوە (د.ع)</th>
                        <th scope="col" class="px-4 py-3 text-center font-semibold text-slate-700">کردار</th>
                    </tr>
                </thead>
                <tbody id="active-employee-loans-tbody" class="divide-y divide-slate-100 bg-white">
<?php if (count($active_employee_loans) === 0): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">هیچ قەرزی چالاک نییە.</td>
                    </tr>
<?php else: ?>
<?php
    $loanRowNum = 0;
    foreach ($active_employee_loans as $al):
        ++$loanRowNum;
        $lid = (int) ($al['loan_id'] ?? 0);
        $ename = htmlspecialchars((string) ($al['employee_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $rUsd = (float) ($al['remaining_usd'] ?? 0);
        $rIqd = (float) ($al['remaining_iqd'] ?? 0);
        $loanDate = htmlspecialchars((string) ($al['loan_date'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-4 py-3 text-right text-slate-600"><?= $loanRowNum ?></td>
                        <td class="px-4 py-3 text-right font-medium text-slate-900"><?= $ename ?></td>
                        <td class="px-4 py-3 text-right text-slate-600"><?= $loanDate ?></td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-800"><?= number_format($rUsd, 2) ?></td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-800"><?= number_format($rIqd, 0) ?></td>
                        <td class="px-4 py-3 text-center">
<?php if ($can_issue_employee_loan): ?>
                            <button type="button"
                                    class="direct-loan-repay-btn inline-flex items-center justify-center gap-1.5 rounded-lg border border-emerald-700 bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300"
                                    data-loan-id="<?= $lid ?>"
                                    data-employee-name="<?= $ename ?>"
                                    data-remaining-usd="<?= htmlspecialchars((string) $rUsd, ENT_QUOTES, 'UTF-8') ?>"
                                    data-remaining-iqd="<?= htmlspecialchars((string) $rIqd, ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-hand-holding-usd text-[10px]" aria-hidden="true"></i>
                                گەڕاندنەوەی قەرز بە نەقد
                            </button>
<?php else: ?>
                            <span class="text-xs text-slate-400">—</span>
<?php endif; ?>
                        </td>
                    </tr>
<?php endforeach; ?>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

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
          <div class="alert alert-warning small mb-3">بڕی دۆلار و/یان دینار ڕاستەوخۆ لە قاسە دەکەم وەک «Employee Loan Issued». دواتر دەتوانیت قەرز لە ڕێگەی گەڕاندنەوەی نەقد یان لە کاتی مووچەدا کەم بکەیتەوە.</div>
          <div class="mb-3">
            <label for="loan_employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="loan_employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
<?php foreach ($employees as $emp): ?>
              <option value="<?= (int) ($emp['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($emp['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
            </select>
            <?php if (count($employees) === 0): ?>
            <div class="form-text text-danger mt-1">هیچ کارمەندێک لە خشتەکە نەدۆزرایەوە. تکایە تۆمارەکانی employees بپشکنە.</div>
            <?php endif; ?>
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

<!-- Direct loan repayment (cash in to cash_box) -->
<div class="modal fade" id="directLoanRepaymentModal" tabindex="-1" aria-labelledby="directLoanRepaymentModalLabel" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered" style="z-index: 1061;">
    <div class="modal-content">
      <form id="directLoanRepaymentForm">
        <input type="hidden" id="direct_repay_loan_id" name="loan_id" value="">
        <div class="modal-header border-b border-slate-200">
          <h5 class="modal-title fw-bold" id="directLoanRepaymentModalLabel">گەڕاندنەوەی قەرز بە نەقد</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-start" dir="rtl">
          <div class="rounded-lg border border-emerald-100 bg-emerald-50/80 px-3 py-2 mb-3 text-sm text-emerald-900">
            <strong id="direct_repay_employee_display"></strong>
            <div class="mt-1 text-xs text-slate-600">
              ماوە: <span class="tabular-nums font-semibold text-slate-800" id="direct_repay_remaining_usd"></span> $
              <span class="mx-1 text-slate-300">|</span>
              <span class="tabular-nums font-semibold text-slate-800" id="direct_repay_remaining_iqd"></span> د.ع
            </div>
          </div>
          <p class="small text-muted mb-3">پارەکە وەک <strong>هاتوو</strong> لە قاسە تۆمار دەکرێت (Direct Loan Repayment).</p>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="direct_repay_usd" class="form-label">گەڕاندنەوە بە دۆلار ($)</label>
              <input type="number" class="form-control" id="direct_repay_usd" name="repay_usd" min="0" step="0.01" value="0" autocomplete="off">
            </div>
            <div class="col-md-6">
              <label for="direct_repay_iqd" class="form-label">گەڕاندنەوە بە دینار (د.ع)</label>
              <input type="number" class="form-control" id="direct_repay_iqd" name="repay_iqd" min="0" step="0.01" value="0" autocomplete="off">
            </div>
            <div class="col-12">
              <label for="direct_repay_date" class="form-label">بەرواری وەرگرتن</label>
              <input type="date" class="form-control" id="direct_repay_date" name="repayment_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
            </div>
          </div>
        </div>
        <div class="modal-footer border-t border-slate-200">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success fw-bold" style="background: var(--seafoam-green);">تۆمارکردن</button>
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

    function escAttr(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function fmtNum(n, frac) {
        var x = parseFloat(n) || 0;
        return x.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: frac });
    }

    window.refreshActiveEmployeeLoansTable = function () {
        $.get('../process/employee_payments/active_loans_list.php', function (r) {
            if (!r || !r.success) {
                return;
            }
            var $tb = $('#active-employee-loans-tbody');
            $tb.empty();
            if (!r.rows || r.rows.length === 0) {
                $tb.append(
                    '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">هیچ قەرزی چالاک نییە.</td></tr>'
                );
                return;
            }
            r.rows.forEach(function (row, idx) {
                var lid = parseInt(row.loan_id, 10);
                var name = row.employee_name || '';
                var ru = parseFloat(row.remaining_usd) || 0;
                var ri = parseFloat(row.remaining_iqd) || 0;
                var ld = row.loan_date || '';
                var btn;
                if (r.can_repay) {
                    btn =
                        '<button type="button" class="direct-loan-repay-btn inline-flex items-center justify-center gap-1.5 rounded-lg border border-emerald-700 bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300" data-loan-id="' +
                        lid +
                        '" data-employee-name="' +
                        escAttr(name) +
                        '" data-remaining-usd="' +
                        escAttr(String(ru)) +
                        '" data-remaining-iqd="' +
                        escAttr(String(ri)) +
                        '">' +
                        '<i class="fas fa-hand-holding-usd text-[10px]" aria-hidden="true"></i> گەڕاندنەوەی قەرز بە نەقد</button>';
                } else {
                    btn = '<span class="text-xs text-slate-400">—</span>';
                }
                $tb.append(
                    '<tr class="hover:bg-slate-50/80 transition-colors">' +
                        '<td class="px-4 py-3 text-right text-slate-600">' +
                        (idx + 1) +
                        '</td>' +
                        '<td class="px-4 py-3 text-right font-medium text-slate-900">' +
                        escAttr(name) +
                        '</td>' +
                        '<td class="px-4 py-3 text-right text-slate-600">' +
                        escAttr(ld) +
                        '</td>' +
                        '<td class="px-4 py-3 text-right tabular-nums text-slate-800">' +
                        fmtNum(ru, 2) +
                        '</td>' +
                        '<td class="px-4 py-3 text-right tabular-nums text-slate-800">' +
                        fmtNum(ri, 0) +
                        '</td>' +
                        '<td class="px-4 py-3 text-center">' +
                        btn +
                        '</td>' +
                        '</tr>'
                );
            });
        }, 'json');
    };

    $(document).on('click', '.direct-loan-repay-btn', function () {
        if (!window.CAN_ISSUE_EMPLOYEE_LOAN) {
            swalAlert('هەڵە', 'ڕێگەپێدراوە نییە.', 'error');
            return;
        }
        var $b = $(this);
        $('#direct_repay_loan_id').val($b.data('loan-id'));
        $('#direct_repay_employee_display').text($b.data('employee-name') || '');
        var ru = parseFloat($b.data('remaining-usd')) || 0;
        var ri = parseFloat($b.data('remaining-iqd')) || 0;
        $('#direct_repay_remaining_usd').text(ru.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 }));
        $('#direct_repay_remaining_iqd').text(ri.toLocaleString('en-US', { maximumFractionDigits: 0 }));
        $('#direct_repay_usd').val(0);
        $('#direct_repay_iqd').val(0);
        $('#direct_repay_date').val(new Date().toISOString().slice(0, 10));
        $('#directLoanRepaymentForm').data('maxUsd', ru).data('maxIqd', ri);
        var modal = new bootstrap.Modal(document.getElementById('directLoanRepaymentModal'));
        modal.show();
    });

    $('#directLoanRepaymentForm').on('submit', function (e) {
        e.preventDefault();
        if (!window.CAN_ISSUE_EMPLOYEE_LOAN) {
            swalAlert('هەڵە', 'ڕێگەپێدراوە نییە.', 'error');
            return;
        }
        var lid = parseInt($('#direct_repay_loan_id').val(), 10);
        var u = parseFloat($('#direct_repay_usd').val()) || 0;
        var iq = parseFloat($('#direct_repay_iqd').val()) || 0;
        var maxU = parseFloat($('#directLoanRepaymentForm').data('maxUsd')) || 0;
        var maxI = parseFloat($('#directLoanRepaymentForm').data('maxIqd')) || 0;
        if (lid <= 0) {
            swalAlert('هەڵە', 'قەرز نادروستە', 'error');
            return;
        }
        if (u <= 0 && iq <= 0) {
            swalAlert('هەڵە', 'لانیکەم یەک بڕ بنووسە', 'error');
            return;
        }
        if (u > maxU + 0.0001) {
            swalAlert('هەڵە', 'بڕی دۆلار زیاترە لە ماوە', 'error');
            return;
        }
        if (iq > maxI + 0.0001) {
            swalAlert('هەڵە', 'بڕی دینار زیاترە لە ماوە', 'error');
            return;
        }
        $.post('../process/employee_payments/direct_loan_repayment.php', $(this).serialize(), function (res) {
            if (res.success) {
                swalAlert('سەرکەوتوو', res.message || 'تۆمارکرا', 'success');
                var dm = document.getElementById('directLoanRepaymentModal');
                var m = bootstrap.Modal.getInstance(dm);
                if (m) {
                    m.hide();
                } else {
                    $(dm).modal('hide');
                }
                window.refreshActiveEmployeeLoansTable();
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

    var now = new Date();
    var month = (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
    $('#deduction_expense_date').val(month);

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

    function initIssueLoanEmployeeSelect2() {
        var $sel = $('#loan_employee_id');
        var $modal = $('#issueEmployeeLoanModal');
        if (!$sel.length || !$modal.length) {
            return;
        }
        if ($sel.data('select2')) {
            $sel.select2('destroy');
        }
        var $parent = $modal.find('.modal-content');
        $sel.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- هەلبژێرە --',
            allowClear: true,
            dir: 'rtl',
            dropdownParent: $parent
        });
    }

    $('#issueEmployeeLoanModal').on('shown.bs.modal', function () {
        initIssueLoanEmployeeSelect2();
    });
    $('#issueEmployeeLoanModal').on('hidden.bs.modal', function () {
        var $sel = $('#loan_employee_id');
        if ($sel.length && $sel.data('select2')) {
            $sel.select2('destroy');
        }
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
                var $loanEmp = $('#loan_employee_id');
                if ($loanEmp.data('select2')) {
                    $loanEmp.val(null).trigger('change');
                }
                $('#issueEmployeeLoanModal').modal('hide');
                window.refreshActiveEmployeeLoansTable();
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

    $(document).on('change', '#employee-filter', function() {
        if (window.loadBalances) {
            window.loadBalances();
        }
    });
});
</script>
</body>
</html>

