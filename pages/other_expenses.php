<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!hasPermission('view_other_expenses')) {
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
    <title>خەرجی تر</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/other_expenses.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/summary_cards.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- AG Grid CSS -->
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css" rel="stylesheet">
    <link href="../assets/css/comon/ag_grid.css" rel="stylesheet">
    <link href="../assets/css/other_expenses/ag_grid_other_expenses.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    
    <style>
        .export-btn {
            background: var(--warning) !important;
            border-color: var(--warning) !important;
            color: #212529 !important;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background: #e0a800 !important;
            border-color: #e0a800 !important;
            transform: translateY(-1px);
            color: #212529 !important;
        }
        
        .summary-export-card {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
            border: none !important;
            color: white !important;
        }
        
        .summary-export-card .card-icon {
            color: white !important;
            font-size: 2rem !important;
        }
        
        .summary-export-card .card-title {
            color: white !important;
            font-weight: bold !important;
        }
        
        .summary-export-card .btn-light {
            background: rgba(255, 255, 255, 0.9) !important;
            border: none !important;
            color: #28a745 !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
        }
        
        .summary-export-card .btn-light:hover {
            background: white !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
        }
    </style>
  
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">

        <button class="btn export-btn" onclick="exportOtherExpensesToExcel()" title="ئیکسپۆرتی هەموو زانیارییەکانی خەرجی تر بۆ Excel">
            <i class="fas fa-file-excel me-1"></i>ئیکسپۆرتی Excel
        </button>
        <?php if (hasPermission('add_other_expenses')): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addExpenseModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی خەرجی تر</button>
        <?php endif; ?>
    </div>
    

    <div class="mb-4">
      <!-- Advanced Filter Section -->
      <div class="card shadow-sm">
        <div class="card-header" style="background: var(--kelly-green); color: var(--seafoam-green);">
          <h6 class="mb-0">
            <i class="fas fa-filter me-2"></i>فلتەر - خەرجی سەیارەکان + خەرجی تر
          </h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <!-- Date Range Filters -->
            <div class="col-md-3">
              <label for="dateFrom" class="form-label">لە بەروار:</label>
              <input type="date" id="dateFrom" class="form-control">
              <div class="auto-filter-indicator mt-1">
                <i class="fas fa-bolt me-1"></i>
                خودکار - سەیارەکان + خەرجی تر
              </div>
            </div>
            <div class="col-md-3">
              <label for="dateTo" class="form-label">بۆ بەروار:</label>
              <input type="date" id="dateTo" class="form-control">
              <div class="auto-filter-indicator mt-1">
                <i class="fas fa-bolt me-1"></i>
                خودکار - سەیارەکان + خەرجی تر
              </div>
            </div>
            <div class="col-md-3">
              <label for="monthFilter" class="form-label">مانگ:</label>
              <input type="month" id="monthFilter" class="form-control">
              <div class="auto-filter-indicator mt-1">
                <i class="fas fa-bolt me-1"></i>
                خودکار - سەیارەکان + خەرجی تر
              </div>
            </div>

            
            <!-- Entity Filters -->
            <div class="col-md-3">
              <label for="carFilter" class="form-label">سەیارە:</label>
              <select id="carFilter" class="form-control">
                <option value="">هەموو سەیارەکان</option>
              </select>
              <div class="auto-filter-indicator mt-1">
                <i class="fas fa-bolt me-1"></i>
                خودکار - سەیارەکان + خەرجی تر
              </div>
            </div>
            <div class="col-md-3">
              <label for="employeeFilter" class="form-label">کارمەند:</label>
              <select id="employeeFilter" class="form-control">
                <option value="">هەموو کارمەندەکان</option>
              </select>
              <div class="auto-filter-indicator mt-1">
                <i class="fas fa-bolt me-1"></i>
                خودکار - سەیارەکان + خەرجی تر
              </div>
            </div>
            <div class="col-md-3">
              <label for="personFilter" class="form-label">کەس:</label>
              <select id="personFilter" class="form-control">
                <option value="">هەموو کەسەکان</option>
              </select>
              <div class="auto-filter-indicator mt-1">
                <i class="fas fa-bolt me-1"></i>
                خودکار - سەیارەکان + خەرجی تر
              </div>
            </div>
            <div class="col-md-3">
              <label for="paymentTypeFilter" class="form-label">جۆری پارەدان:</label>
              <select id="paymentTypeFilter" class="form-control">
                <option value="">هەموو جۆرەکان</option>
                <option value="نەقد">نەقد</option>
                <option value="قەرز">قەرز</option>
              </select>
              <div class="auto-filter-indicator mt-1">
                <i class="fas fa-bolt me-1"></i>
                خودکار - سەیارەکان + خەرجی تر
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">جۆری خەرجی</label>
              <div class="d-flex gap-3 flex-wrap">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="expenseTypeOther" value="خەرجی تر">
                  <label class="form-check-label" for="expenseTypeOther">
                    خەرجی تر
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="expenseTypeMaterial" value="بەکارهێنانی کاڵای کۆگا">
                  <label class="form-check-label" for="expenseTypeMaterial">
                    بەکارهێنانی کاڵای کۆگا
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="expenseTypeGas" value="بەکارهێنانی گاز">
                  <label class="form-check-label" for="expenseTypeGas">
                    بەکارهێنانی گاز
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="filter_expense_type_khwardnga" name="expenseTypes[]" value="خواردنگە">
                  <label class="form-check-label" for="filter_expense_type_khwardnga">خواردنگە</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="filter_expense_type_office" name="expenseTypes[]" value="ئۆفیس">
                  <label class="form-check-label" for="filter_expense_type_office">ئۆفیس</label>
                </div>
              </div>
              <div class="auto-filter-indicator mt-1">
                <i class="fas fa-check-square me-1"></i>
                چۆیس - سەیارەکان + خەرجی تر
              </div>
            </div>
            


            
            <!-- Action Buttons -->
            <div class="col-12">
              <div class="d-flex gap-2 flex-wrap">
                <button type="button" id="clearFilters" class="btn btn-secondary">
                  <i class="fas fa-times me-1"></i>سڕینەوەی فلتەر
                </button>
                <button type="button" id="exportReport" class="btn btn-success">
                  <i class="fas fa-download me-1"></i>داگرتنی ڕاپۆرت
                </button>
                <button type="button" onclick="exportOtherExpensesToExcel()" class="btn export-btn">
                  <i class="fas fa-file-excel me-1"></i>ئیکسپۆرتی Excel
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
      <!-- First row: 4 main expense cards -->
      <div class="row w-100 mt-3 g-3">
        <div class="col-md-3">
          <div class="card gradient-card green-gradient">
            <div class="card-body">
              <h6 class="card-title">خەرجی سەیارەکان (کاڵا)</h6>
              <div id="totalCarMaterialCost" class="card-value">$0</div>
              <small>بەکارهێنانی کاڵای کۆگا</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card gradient-card orange-gradient">
            <div class="card-body">
              <h6 class="card-title">خەرجی سەیارەکان (گاز)</h6>
              <div id="totalCarGasCost" class="card-value">$0</div>
              <small>بەکارهێنانی گاز</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card gradient-card teal-gradient">
            <div class="card-body">
              <h6 class="card-title">خەرجی تر</h6>
              <div id="totalOtherExpenses" class="card-value">$0</div>
              <small>خەرجی تر (نەک سەیارە)</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card gradient-card purple-gradient">
            <div class="card-body">
              <h6 class="card-title">کۆی گشتی</h6>
              <div id="totalCarExpenses" class="card-value">$0</div>
              <small>کاڵا + گاز + خەرجی تر</small>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Second row: IQD and USD totals -->
      <div class="row w-100 mt-2 g-3">
        <div class="col-md-6">
          <div class="card gradient-card blue-gradient">
            <div class="card-body">
              <h6 class="card-title">کۆی گشتی خەرجییەکان بە دینار</h6>
              <div id="totalExpensesIQD" class="card-value">0 د.ع</div>
              <small>کۆی هەموو خەرجییەکان بە دینار</small>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card gradient-card yellow-gradient">
            <div class="card-body">
              <h6 class="card-title">کۆی گشتی خەرجییەکان بە دۆلار</h6>
              <div id="totalExpensesUSD" class="card-value">$0</div>
              <small>کۆی هەموو خەرجییەکان بە دۆلار</small>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Third row: Export and USD exchange rate -->
      <div class="row w-100 mt-2 g-3">
        <div class="col-md-6">
          <div class="card gradient-card summary-export-card">
            <div class="card-body">
              <i class="fas fa-file-excel card-icon"></i>
              <h6 class="card-title">ئیکسپۆرتی کورتە</h6>
              <button class="btn btn-sm btn-light mt-2" onclick="exportOtherExpensesSummaryToExcel()" title="ئیکسپۆرتی کورتەی خەرجی تر بۆ Excel">
                <i class="fas fa-download me-1"></i>داگرتن
              </button>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card gradient-card red-gradient">
            <div class="card-body">
              <h6 class="card-title">نرخی دۆلار</h6>
              <div id="usdExchangeRate" class="card-value">0 د.ع</div>
              <small>نرخی 100 دۆلار بە دینار</small>
              <button class="btn btn-sm btn-outline-light mt-2" id="refreshUsdRate" title="نوێکردنەوەی نرخی دۆلار">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="table-responsive">
        <div id="otherExpensesGrid" class="ag-grid-container ag-theme-alpine"></div>
    </div>
</div>
<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addExpenseForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addExpenseModalLabel">زیادکردنی خەرجی تر</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="purpose" class="form-label">مەبەستی سەرف کردن</label>
            <textarea class="form-control" id="purpose" name="purpose" rows="2"></textarea>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4">
              <label for="employee_id" class="form-label">کارمەند</label>
              <select class="form-control" id="employee_id" name="employee_id">
                <option value="">-- هەلبژێرە --</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="car_id" class="form-label">سەیارە</label>
              <select class="form-control" id="car_id" name="car_id">
                <option value="">-- هەلبژێرە --</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="gas_liters" class="form-label">بڕی گاز (لیتر)</label>
              <input type="number" step="0.01" class="form-control" id="gas_liters" name="gas_liters" placeholder="0">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4">
              <label for="expense_type" class="form-label">جۆری خەرجی</label>
              <select class="form-control" id="expense_type" name="expense_type" required>
                <option value="">-- هەلبژێرە --</option>
                <option value="خەرجی تر">خەرجی تر</option>
                <option value="بەکارهێنانی کاڵای کۆگا">بەکارهێنانی کاڵای کۆگا</option>
                <option value="بەکارهێنانی گاز">بەکارهێنانی گاز</option>
                <option value="خواردنگە">خواردنگە</option>
                <option value="ئۆفیس">ئۆفیس</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="material_id" class="form-label">کاڵا لە کۆگا</label>
              <select class="form-control" id="material_id" name="material_id">
                <option value="">-- هەلبژێرە --</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="usage_unit_type" class="form-label">یەکەی بەکارهێنان</label>
              <select class="form-control" id="usage_unit_type" name="usage_unit_type">
                <option value="">یەکەی بەکارهێنان هەڵبژێرە</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="material_quantity" class="form-label">بڕی عەدەدی کاڵا</label>
              <input type="number" step="0.01" class="form-control" id="material_quantity" name="material_quantity" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="material_purchase_price_iqd" class="form-label">نرخی کڕینی کاڵا بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="material_purchase_price_iqd" name="material_purchase_price_iqd" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="material_purchase_price_usd" class="form-label">نرخی کڕینی کاڵا بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="material_purchase_price_usd" name="material_purchase_price_usd" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="material_total_cost" class="form-label">کۆی نرخی کاڵای بەکارهاتوو</label>
              <input type="number" step="0.01" class="form-control" id="material_total_cost" name="material_total_cost" placeholder="0" readonly>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 gas-material-field">
              <label for="gas_purchase_price_input" class="form-label">ئینپوتی نرخی کڕینی گاز</label>
              <input type="number" step="0.01" class="form-control" id="gas_purchase_price_input" name="gas_purchase_price_input" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="gas_total_cost" class="form-label">کۆی نرخی گازی بەکارهاتوو</label>
              <input type="number" step="0.01" class="form-control" id="gas_total_cost" name="gas_total_cost" placeholder="0" readonly>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="person_id" class="form-label">کەس</label>
              <div class="input-group">
                <select class="form-control" id="person_id" name="person_id">
                  <option value="">-- هەلبژێرە --</option>
                </select>
                <button class="btn" type="button" id="addPersonBtn" data-bs-toggle="modal" data-bs-target="#addPersonModal" style="background: var(--seafoam-green); color: white; font-weight: bold;">+
                </button>
              </div>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="payment_type" class="form-label">جۆری مامەڵە</label>
              <select class="form-control" id="payment_type" name="payment_type">
                <option value="نەقد">نەقد</option>
                <option value="قەرز">قەرز</option>
              </select>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="currency_type" class="form-label">جۆری پارە</label>
              <select class="form-control" id="currency_type" name="currency_type">
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
                <option value="تێکەڵ">تێکەڵ</option>
              </select>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="invoice_number" class="form-label">ژمارەی وەسڵ</label>
              <input type="text" class="form-control" id="invoice_number" name="invoice_number">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="amount_iqd" class="form-label">بڕی پارە بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="amount_iqd" name="amount_iqd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="amount_usd" class="form-label">بڕی پارە بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="amount_usd" name="amount_usd" value="0">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="paid_iqd" class="form-label">پارەی دراو بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="paid_iqd" name="paid_iqd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="paid_usd" class="form-label">پارەی دراو بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="paid_usd" name="paid_usd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="exchange_rate" class="form-label">نرخی 100 دۆلار بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="exchange_rate" name="exchange_rate" value="139250">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="remaining_iqd" class="form-label">بڕی ماوە بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="remaining_iqd" name="remaining_iqd" value="0" readonly>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="remaining_usd" class="form-label">بڕی ماوە بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="remaining_usd" name="remaining_usd" value="0" readonly>
            </div>
            <div class="col-md-4">
              <label for="date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="date" name="date">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success" style="background: var(--lime-green); font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Add Person Modal -->
<div class="modal fade" id="addPersonModal" tabindex="-1" aria-labelledby="addPersonModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addPersonForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addPersonModalLabel">زیادکردنی کەس</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="person_name" class="form-label">ناوی کەس</label>
            <input type="text" class="form-control" id="person_name" name="person_name" required>
          </div>
          <div class="mb-3">
            <label for="person_expense_usd" class="form-label">خەرجی کەس بە دۆلار</label>
            <input type="number" step="0.01" class="form-control" id="person_expense_usd" name="expense_usd" value="0">
          </div>
          <div class="mb-3">
            <label for="person_expense_iqd" class="form-label">خەرجی کەس بە دینار</label>
            <input type="number" step="0.01" class="form-control" id="person_expense_iqd" name="expense_iqd" value="0">
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
<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editExpenseForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editExpenseModalLabel">دەستکاری خەرجی تر</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_id" name="id">
          <div class="mb-3">
            <label for="edit_purpose" class="form-label">مەبەستی سەرف کردن</label>
            <textarea class="form-control" id="edit_purpose" name="purpose" rows="2"></textarea>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4">
              <label for="edit_employee_id" class="form-label">کارمەند</label>
              <select class="form-control" id="edit_employee_id" name="employee_id">
                <option value="">-- هەلبژێرە --</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="edit_car_id" class="form-label">سەیارە</label>
              <select class="form-control" id="edit_car_id" name="car_id">
                <option value="">-- هەلبژێرە --</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_gas_liters" class="form-label">بڕی گاز (لیتر)</label>
              <input type="number" step="0.01" class="form-control" id="edit_gas_liters" name="gas_liters" placeholder="0">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4">
              <label for="edit_expense_type" class="form-label">جۆری خەرجی</label>
              <select class="form-control" id="edit_expense_type" name="expense_type" required>
                <option value="">-- هەلبژێرە --</option>
                <option value="خەرجی تر">خەرجی تر</option>
                <option value="بەکارهێنانی کاڵای کۆگا">بەکارهێنانی کاڵای کۆگا</option>
                <option value="بەکارهێنانی گاز">بەکارهێنانی گاز</option>
                <option value="خواردنگە">خواردنگە</option>
                <option value="ئۆفیس">ئۆفیس</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_material_id" class="form-label">کاڵا لە کۆگا</label>
              <select class="form-control" id="edit_material_id" name="material_id">
                <option value="">-- هەلبژێرە --</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_usage_unit_type" class="form-label">یەکەی بەکارهێنان</label>
              <select class="form-control" id="edit_usage_unit_type" name="usage_unit_type">
                <option value="">یەکەی بەکارهێنان هەڵبژێرە</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_material_quantity" class="form-label">بڕی عەدەدی کاڵا</label>
              <input type="number" step="0.01" class="form-control" id="edit_material_quantity" name="material_quantity" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_material_purchase_price_iqd" class="form-label">نرخی کڕینی کاڵا بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="edit_material_purchase_price_iqd" name="material_purchase_price_iqd" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_material_purchase_price_usd" class="form-label">نرخی کڕینی کاڵا بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="edit_material_purchase_price_usd" name="material_purchase_price_usd" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_material_total_cost" class="form-label">کۆی نرخی کاڵای بەکارهاتوو</label>
              <input type="number" step="0.01" class="form-control" id="edit_material_total_cost" name="material_total_cost" placeholder="0" readonly>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 gas-material-field">
              <label for="edit_gas_purchase_price_input" class="form-label">ئینپوتی نرخی کڕینی گاز</label>
              <input type="number" step="0.01" class="form-control" id="edit_gas_purchase_price_input" name="gas_purchase_price_input" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_gas_total_cost" class="form-label">کۆی نرخی گازی بەکارهاتوو</label>
              <input type="number" step="0.01" class="form-control" id="edit_gas_total_cost" name="gas_total_cost" placeholder="0" readonly>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_person_id" class="form-label">کەس</label>
              <select class="form-control" id="edit_person_id" name="person_id">
                <option value="">-- هەلبژێرە --</option>
              </select>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_payment_type" class="form-label">جۆری مامەڵە</label>
              <select class="form-control" id="edit_payment_type" name="payment_type">
                <option value="نەقد">نەقد</option>
                <option value="قەرز">قەرز</option>
              </select>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_currency_type" class="form-label">جۆری پارە</label>
              <select class="form-control" id="edit_currency_type" name="currency_type">
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
                <option value="تێکەڵ">تێکەڵ</option>
              </select>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_invoice_number" class="form-label">ژمارەی وەسڵ</label>
              <input type="text" class="form-control" id="edit_invoice_number" name="invoice_number">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_amount_iqd" class="form-label">بڕی پارە بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="edit_amount_iqd" name="amount_iqd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_amount_usd" class="form-label">بڕی پارە بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="edit_amount_usd" name="amount_usd" value="0">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_paid_iqd" class="form-label">پارەی دراو بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="edit_paid_iqd" name="paid_iqd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_paid_usd" class="form-label">پارەی دراو بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="edit_paid_usd" name="paid_usd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_exchange_rate" class="form-label">نرخی 100 دۆلار بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="edit_exchange_rate" name="exchange_rate" value="139250">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_remaining_iqd" class="form-label">بڕی ماوە بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="edit_remaining_iqd" name="remaining_iqd" value="0" readonly>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_remaining_usd" class="form-label">بڕی ماوە بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="edit_remaining_usd" name="remaining_usd" value="0" readonly>
            </div>
            <div class="col-md-4">
              <label for="edit_date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="edit_date" name="date">
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- AG Grid JS -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js"></script>
<script src="../assets/js/comon/ag_grid_base.js"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/other_expenses/error_logger.js"></script>
<script src="../assets/js/other_expenses/debug_panel.js"></script>
<script src="../assets/js/other_expenses/advanced_filters.js"></script>
<script src="../assets/js/other_expenses/add_expenses.js"></script>
<script src="../assets/js/other_expenses/ag_grid_other_expenses.js"></script>
<script src="../assets/js/other_expenses/other_expenses.js"></script>
<script src="../assets/js/other_expenses/delete_expenses.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../assets/js/comon/select2_script.js"></script>
<script src="../assets/js/other_expenses/update_expenses.js"></script>
    <script src="../assets/js/other_expenses/export_functions.js?v=<?= time() ?>"></script>
</body>
</html>

