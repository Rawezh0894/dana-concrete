<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!hasPermission('view_person_other_expenses')) {
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
    <title>پڕۆفایلی کەسانی خەرجی تر</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <!-- AG Grid CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css">
    <style>
        /* RTL Support for AG Grid */
        #personGrid {
            direction: rtl;
            font-family: 'Rabar', 'Arial', 'Tahoma', sans-serif !important;
        }
        
        /* Apply Rabar font to all AG Grid elements */
        .ag-theme-alpine,
        .ag-theme-alpine *,
        .ag-theme-alpine .ag-header,
        .ag-theme-alpine .ag-header-cell,
        .ag-theme-alpine .ag-header-cell-text,
        .ag-theme-alpine .ag-cell,
        .ag-theme-alpine .ag-row,
        .ag-theme-alpine .ag-paging-panel,
        .ag-theme-alpine .ag-filter,
        .ag-theme-alpine .ag-filter-body,
        .ag-theme-alpine .ag-input-field-input,
        .ag-theme-alpine .ag-select,
        .ag-theme-alpine button {
            font-family: 'Rabar', 'Arial', 'Tahoma', sans-serif !important;
        }
        
        .ag-theme-alpine {
            --ag-foreground-color: rgb(33, 37, 41);
            --ag-background-color: rgb(255, 255, 255);
            --ag-header-foreground-color: var(--seafoam-green);
            --ag-header-background-color: var(--kelly-green);
            --ag-odd-row-background-color: rgb(249, 249, 249);
            --ag-header-column-resize-handle-color: var(--seafoam-green);
            --ag-font-family: 'Rabar', 'Arial', 'Tahoma', sans-serif;
            --ag-font-size: 14px;
            --ag-header-height: 50px;
            --ag-row-height: 45px;
            --ag-border-color: rgba(0, 0, 0, 0.1);
            --ag-header-foreground-color: #fff;
        }
        
        .ag-theme-alpine .ag-header-cell-label {
            justify-content: center;
            text-align: center;
            font-weight: bold;
            font-size: 15px;
        }
        
        .ag-theme-alpine .ag-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            font-size: 14px;
        }
        
        .ag-theme-alpine .ag-cell.text-end {
            justify-content: flex-end;
            padding-right: 15px;
        }
        
        .ag-theme-alpine .ag-cell.text-center {
            justify-content: center;
        }
        
        .ag-theme-alpine .ag-row {
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .ag-theme-alpine .ag-row:hover {
            background-color: rgba(0, 0, 0, 0.03) !important;
        }
        
        .ag-theme-alpine .ag-row-selected {
            background-color: rgba(79, 172, 254, 0.15) !important;
        }
        
        /* Button styling in cells */
        .ag-theme-alpine .ag-cell button {
            font-family: 'Rabar', 'Arial', 'Tahoma', sans-serif !important;
            font-weight: bold;
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .ag-theme-alpine .ag-cell button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Filter styling */
        .ag-theme-alpine .ag-filter-body {
            font-family: 'Rabar', 'Arial', 'Tahoma', sans-serif !important;
        }
        
        /* Pagination styling */
        .ag-theme-alpine .ag-paging-panel {
            font-family: 'Rabar', 'Arial', 'Tahoma', sans-serif !important;
            padding: 10px;
            background-color: #f8f9fa;
        }
        
        .ag-theme-alpine .ag-paging-button {
            font-family: 'Rabar', 'Arial', 'Tahoma', sans-serif !important;
        }
    </style>
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">کەسانی خەرجی تر</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPersonModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی کەس</button>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card summary-card card-gradient-success card-animate-hover card-shadow-medium card-rounded">
                <div class="card-body text-center">
                    <div class="card-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h5 class="card-title">کۆی قەرزی ئێمە بە دۆلار</h5>
                    <h3 class="card-value" id="totalDebtUSD">$0.00</h3>
                    <div class="card-details">
                        <small>خەرجی تر: <span id="otherExpensesUSD">$0.00</span></small><br>
                        <small>کڕینی کاڵا: <span id="purchaseMaterialsUSD">$0.00</span></small><br>
                        <small>قەرزی سەرەتایی: <span id="personsOpeningUSD">$0.00</span></small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card summary-card card-gradient-warning card-animate-hover card-shadow-medium card-rounded">
                <div class="card-body text-center">
                    <div class="card-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h5 class="card-title">کۆی قەرزی ئێمە بە دینار</h5>
                    <h3 class="card-value" id="totalDebtIQD">0 دینار</h3>
                    <div class="card-details">
                        <small>خەرجی تر: <span id="otherExpensesIQD">0 دینار</span></small><br>
                        <small>کڕینی کاڵا: <span id="purchaseMaterialsIQD">0 دینار</span></small><br>
                        <small>قەرزی سەرەتایی: <span id="personsOpeningIQD">0 دینار</span></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <div id="personGrid" class="ag-theme-alpine" style="height: 600px; width: 100%;"></div>
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
            <label for="person_expense_usd" class="form-label d-none">خەرجی بە دۆلار</label>
            <input type="number" step="0.01" class="form-control d-none" id="person_expense_usd" name="expense_usd" value="0" readonly>
          </div>
          <div class="mb-3">
            <label for="person_expense_iqd" class="form-label d-none">خەرجی بە دینار</label>
            <input type="number" step="0.01" class="form-control d-none" id="person_expense_iqd" name="expense_iqd" value="0" readonly>
          </div>
          <div class="mb-3">
            <label for="person_opening_debt_usd" class="form-label">قەرزی سەرەتایی بە دۆلار</label>
            <input type="number" step="0.01" class="form-control" id="person_opening_debt_usd" name="opening_debt_usd" value="0">
          </div>
          <div class="mb-3">
            <label for="person_opening_debt_iqd" class="form-label">قەرزی سەرەتایی بە دینار</label>
            <input type="number" step="0.01" class="form-control" id="person_opening_debt_iqd" name="opening_debt_iqd" value="0">
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- AG Grid JS -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/person_other_expenses/select_person.js"></script>
<script src="../assets/js/person_other_expenses/add_person.js"></script>
<script src="../assets/js/person_other_expenses/update_person.js"></script>
<script src="../assets/js/person_other_expenses/delete_person.js"></script>
</body>
</html>
