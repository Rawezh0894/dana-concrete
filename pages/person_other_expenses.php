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
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.1.1/styles/ag-grid.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.1.1/styles/ag-theme-alpine.css" rel="stylesheet">
    <style>
        /* Professional AG Grid Styling */
        :root {
            --grid-primary: var(--seafoam-green);
            --grid-secondary: var(--spearmint);
            --grid-light: var(--kelly-green);
            --grid-header-bg: linear-gradient(135deg, var(--seafoam-green) 0%, var(--spearmint) 100%);
            --grid-border: #e0e0e0;
            --grid-hover: #f5f9ff;
            --grid-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Grid Container */
        .ag-grid-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid var(--grid-border);
        }

        /* AG Grid RTL Support & Professional Styling */
        .ag-theme-alpine {
            direction: rtl;
            font-family: 'Rabar', Arial, sans-serif;
            font-size: 14px;
            --ag-header-background-color: transparent;
            --ag-header-foreground-color: white;
            --ag-header-cell-hover-background-color: rgba(255, 255, 255, 0.15);
            --ag-header-cell-moving-background-color: rgba(255, 255, 255, 0.2);
            --ag-border-color: var(--grid-border);
            --ag-row-hover-color: var(--grid-hover);
            --ag-odd-row-background-color: #fafbfc;
            --ag-selected-row-background-color: rgba(0, 59, 115, 0.1);
            --ag-range-selection-background-color: rgba(0, 59, 115, 0.15);
            --ag-header-height: 50px;
            --ag-row-height: 55px;
        }

        /* Header Styling */
        .ag-theme-alpine .ag-header {
            background: var(--grid-header-bg);
            border-bottom: 3px solid var(--grid-primary);
            box-shadow: 0 2px 8px rgba(0, 59, 115, 0.15);
        }

        .ag-theme-alpine .ag-header-cell {
            text-align: right;
            padding: 0 15px;
            font-weight: 600;
            font-size: 14px;
            color: white;
            letter-spacing: 0.3px;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .ag-theme-alpine .ag-header-cell:last-child {
            border-right: none;
        }

        .ag-theme-alpine .ag-header-cell-label {
            justify-content: flex-start;
            align-items: center;
            height: 100%;
        }

        .ag-theme-alpine .ag-header-cell:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        /* Header Icons */
        .ag-theme-alpine .ag-header-cell-menu-button,
        .ag-theme-alpine .ag-sort-order {
            color: white !important;
            opacity: 0.9;
        }

        .ag-theme-alpine .ag-header-cell-menu-button:hover {
            opacity: 1;
        }

        /* Row Styling */
        .ag-theme-alpine .ag-row {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s ease;
        }

        .ag-theme-alpine .ag-row:hover {
            background: var(--grid-hover) !important;
            transform: translateX(-2px);
            box-shadow: 2px 0 8px rgba(0, 59, 115, 0.1);
        }

        .ag-theme-alpine .ag-row-even {
            background: white;
        }

        .ag-theme-alpine .ag-row-odd {
            background: #fafbfc;
        }

        .ag-theme-alpine .ag-row-selected {
            background: rgba(0, 59, 115, 0.08) !important;
        }

        /* Cell Styling */
        .ag-theme-alpine .ag-cell {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-right: 1px solid #f5f5f5;
            font-size: 14px;
        }

        .ag-theme-alpine .ag-cell-value {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Floating Filter */
        .ag-theme-alpine .ag-floating-filter-input {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .ag-theme-alpine .ag-floating-filter-input:focus {
            border-color: var(--grid-primary);
            box-shadow: 0 0 0 3px rgba(0, 59, 115, 0.1);
            outline: none;
        }

        /* Pagination Panel */
        .ag-theme-alpine .ag-paging-panel {
            direction: rtl;
            background: linear-gradient(to bottom, #f8f9fa, white);
            border-top: 2px solid var(--grid-border);
            padding: 15px 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .ag-theme-alpine .ag-paging-button {
            background: white;
            border: 1.5px solid var(--grid-primary);
            color: var(--grid-primary);
            padding: 8px 16px;
            border-radius: 6px;
            margin: 0 3px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .ag-theme-alpine .ag-paging-button:hover:not(:disabled) {
            background: var(--grid-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 59, 115, 0.2);
        }

        .ag-theme-alpine .ag-paging-button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .ag-theme-alpine .ag-paging-button.ag-disabled {
            opacity: 0.4;
        }

        /* Page Size Selector */
        .ag-theme-alpine .ag-paging-page-size {
            border: 1.5px solid var(--grid-primary);
            border-radius: 6px;
            padding: 6px 12px;
            background: white;
            color: var(--grid-primary);
            font-weight: 600;
            margin: 0 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .ag-theme-alpine .ag-paging-page-size:hover {
            background: var(--grid-hover);
        }

        /* Pagination Info Text */
        .ag-theme-alpine .ag-paging-row-summary-panel {
            color: var(--grid-primary);
            font-weight: 600;
        }

        /* Toolbar Styling */
        .grid-toolbar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--grid-border);
        }

        .grid-search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .grid-search-box .search-icon {
            position: absolute;
            right: 15px;
            color: var(--grid-primary);
            z-index: 10;
        }

        .grid-search-box input {
            padding-right: 45px;
            padding-left: 40px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            height: 45px;
        }

        .grid-search-box input:focus {
            border-color: var(--grid-primary);
            box-shadow: 0 0 0 4px rgba(0, 59, 115, 0.1);
            outline: none;
        }

        .clear-search {
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 35px;
            padding: 0;
        }

        .grid-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .grid-stats {
            background: linear-gradient(135deg, var(--grid-primary), var(--grid-secondary));
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0, 59, 115, 0.2);
        }

        /* Footer Styling */
        .grid-footer {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 10px;
            border: 1px solid var(--grid-border);
        }

        .footer-info small {
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-info i {
            color: var(--grid-primary);
        }

        .footer-actions {
            display: flex;
            gap: 10px;
        }

        .footer-actions .btn {
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-width: 1.5px;
        }

        .footer-actions .btn-outline-primary {
            border-color: var(--grid-primary);
            color: var(--grid-primary);
        }

        .footer-actions .btn-outline-primary:hover {
            background: var(--grid-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 59, 115, 0.2);
        }

        .footer-actions .btn-outline-success {
            border-color: #28a745;
            color: #28a745;
        }

        .footer-actions .btn-outline-success:hover {
            background: #28a745;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
        }

        /* Pinned Columns */
        .ag-theme-alpine .ag-pinned-right-cols-container {
            direction: ltr;
            border-left: 2px solid var(--grid-primary);
            box-shadow: -2px 0 8px rgba(0, 0, 0, 0.05);
        }

        .ag-theme-alpine .ag-pinned-left-cols-container {
            direction: ltr;
            border-right: 2px solid var(--grid-primary);
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
        }

        /* No Rows Message */
        .ag-theme-alpine .ag-overlay-no-rows-wrapper {
            padding: 40px;
        }

        .ag-theme-alpine .ag-overlay-no-rows-center {
            color: #999;
            font-size: 16px;
        }

        /* Loading Overlay */
        .ag-theme-alpine .ag-overlay-loading-wrapper {
            background: rgba(255, 255, 255, 0.9);
        }

        /* Scrollbar Styling */
        .ag-theme-alpine .ag-body-vertical-scroll {
            width: 12px;
        }

        .ag-theme-alpine .ag-body-vertical-scroll::-webkit-scrollbar,
        .ag-theme-alpine .ag-body-horizontal-scroll::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        .ag-theme-alpine .ag-body-vertical-scroll::-webkit-scrollbar-track,
        .ag-theme-alpine .ag-body-horizontal-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
        }

        .ag-theme-alpine .ag-body-vertical-scroll::-webkit-scrollbar-thumb,
        .ag-theme-alpine .ag-body-horizontal-scroll::-webkit-scrollbar-thumb {
            background: var(--grid-primary);
            border-radius: 6px;
        }

        .ag-theme-alpine .ag-body-vertical-scroll::-webkit-scrollbar-thumb:hover,
        .ag-theme-alpine .ag-body-horizontal-scroll::-webkit-scrollbar-thumb:hover {
            background: var(--grid-secondary);
        }

        /* Action Buttons in Cells */
        .ag-theme-alpine .ag-cell button {
            transition: all 0.2s ease;
            border-radius: 6px;
        }

        .ag-theme-alpine .ag-cell button:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .grid-toolbar {
                flex-direction: column;
            }

            .grid-search-box {
                max-width: 100%;
                width: 100%;
            }

            .grid-footer {
                flex-direction: column;
                text-align: center;
            }

            .footer-actions {
                justify-content: center;
                width: 100%;
            }
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
    
    <!-- AG Grid Toolbar -->
    <div class="grid-toolbar mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="grid-search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="gridSearchInput" class="form-control" placeholder="گەڕان لە تابل..." autocomplete="off">
                <button class="btn btn-sm btn-outline-secondary clear-search" id="clearSearchBtn" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="grid-info">
                <span class="grid-stats" id="gridStats">0 کەس</span>
            </div>
        </div>
    </div>
    
    <!-- AG Grid Container -->
    <div class="ag-grid-container">
        <div class="ag-theme-alpine" id="personTable" style="height: 650px; width: 100%;"></div>
    </div>
    
    <!-- Grid Footer -->
    <div class="grid-footer mt-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="footer-info">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    دەتوانیت بە کلیک لەسەر هەر هێدەرێک جۆربکەیتەوە یان فیلتەر بکەیت
                </small>
            </div>
            <div class="footer-actions">
                <button class="btn btn-sm btn-outline-primary" id="resetFiltersBtn">
                    <i class="fas fa-redo"></i> پاککردنەوەی فیلتەرەکان
                </button>
                <button class="btn btn-sm btn-outline-success" id="exportDataBtn">
                    <i class="fas fa-download"></i> دۆزینەوە
                </button>
            </div>
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
<script src="../assets/js/swalAlert.js"></script>
<!-- AG Grid JS -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.1.1/dist/ag-grid-community.min.js"></script>
<script src="../assets/js/person_other_expenses/select_person.js"></script>
<script src="../assets/js/person_other_expenses/add_person.js"></script>
<script src="../assets/js/person_other_expenses/update_person.js"></script>
<script src="../assets/js/person_other_expenses/delete_person.js"></script>
</body>
</html>
