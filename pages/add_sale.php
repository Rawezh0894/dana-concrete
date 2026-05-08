<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
}
if (!hasPermission('view_sale')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
// Note: add_sale permission is checked in the UI, not here
// Users with only view_sale permission can still access the page
$customers = $pdo->query("SELECT id, name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$formulas = $pdo->query("SELECT id, name FROM concrete_formulas")->fetchAll(PDO::FETCH_ASSOC);

// Get recipients: both from recipients table and customers with is_recipient = 1
$recipients_from_table = $pdo->query("SELECT id, name, phone1, phone2 FROM recipients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$recipients_from_customers = $pdo->query("SELECT id, name, mobile1 AS phone1, mobile2 AS phone2 FROM customers WHERE is_recipient = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Combine both and remove duplicates by name
$recipients = [];
$recipient_names = [];
foreach ($recipients_from_table as $r) {
    $recipients[] = $r;
    $recipient_names[] = strtolower(trim($r['name']));
}
foreach ($recipients_from_customers as $r) {
    if (!in_array(strtolower(trim($r['name'])), $recipient_names)) {
        $recipients[] = $r;
        $recipient_names[] = strtolower(trim($r['name']));
    }
}
// Sort by name
usort($recipients, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زیادکردنی فرۆشتن</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- AG Grid CSS -->
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css" rel="stylesheet">
    <link href="../assets/css/comon/ag_grid.css" rel="stylesheet">
    <link href="../assets/css/sale/ag_grid_sale.css" rel="stylesheet">
    
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
        
        /* DataTables Custom Styling */
        .dataTables_wrapper {
            margin-top: 1rem;
        }
        
        .dataTables_wrapper .dataTables_length select {
            padding: 0.375rem 1.75rem 0.375rem 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            background-color: #fff;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            padding: 0.375rem 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            margin-right: 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            margin-left: 2px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            background-color: #fff;
            color: #495057 !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--seafoam-green) !important;
            border-color: var(--seafoam-green) !important;
            color: #fff !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--seafoam-green) !important;
            border-color: var(--seafoam-green) !important;
            color: #fff !important;
        }
        
        .dataTables_wrapper .dataTables_info {
            color: #6c757d;
        }
        
        .table thead {
            background: var(--kelly-green);
        }
        
        .table thead th {
            background-color: var(--kelly-green) !important;
            color: var(--seafoam-green) !important;
        }
        
        /* Column filter inputs styling */
        .column-filter {
            background: rgba(255, 255, 255, 0.95) !important;
            font-size: 0.8rem !important;
        }
        
        .column-filter:focus {
            background: #fff !important;
            border-color: var(--seafoam-green) !important;
            outline: none !important;
            box-shadow: 0 0 0 0.2rem rgba(32, 178, 170, 0.25) !important;
        }
        
        .column-filter::placeholder {
            color: #999 !important;
            font-size: 0.75rem !important;
        }
        
        /* DataTables sort indicator */
        table.dataTable thead .sorting,
        table.dataTable thead .sorting_asc,
        table.dataTable thead .sorting_desc,
        table.dataTable thead .sorting_asc_disabled,
        table.dataTable thead .sorting_desc_disabled {
            cursor: pointer;
            position: relative;
            padding-right: 30px !important;
        }
        
        table.dataTable thead .sorting:before,
        table.dataTable thead .sorting_asc:before,
        table.dataTable thead .sorting_desc:before {
            content: "⇅";
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.5;
            font-size: 0.9rem;
        }
        
        table.dataTable thead .sorting_asc:before {
            content: "↑";
            opacity: 1;
            color: var(--seafoam-green);
        }
        
        table.dataTable thead .sorting_desc:before {
            content: "↓";
            opacity: 1;
            color: var(--seafoam-green);
        }
        
        /* DataTables buttons styling */
        .dt-buttons {
            margin-bottom: 1rem;
        }
        
        .dt-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        /* Ensure table headers are not too tall */
        .table thead th {
            white-space: nowrap;
            vertical-align: top !important;
        }
        
        /* Wrap column filter inputs properly */
        .table thead th > input {
            margin-top: 5px !important;
        }
        
        .page-actions-wrapper {
            width: 100%;
            margin-bottom: 1.5rem;
        }
        
        .page-action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .page-action-buttons .btn {
            min-width: 160px;
            font-weight: bold;
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .page-action-buttons {
                justify-content: flex-start;
            }
        }
        
        @media (max-width: 576px) {
            .page-action-buttons {
                width: 100%;
            }
            .page-action-buttons .btn {
                flex: 1 1 calc(50% - 0.75rem);
                min-width: 130px;
            }
        }
    </style>

    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="page-actions-wrapper">
        <div class="page-action-buttons">
            <button class="btn export-btn" onclick="exportSaleToExcel()" title="ئیکسپۆرتی هەموو زانیارییەکانی فرۆشتن بۆ Excel">
                <i class="fas fa-file-excel me-1"></i>ئیکسپۆرتی Excel
            </button>
            <a href="summery_concrete_receipts.php" class="btn btn-warning" style="color: white; font-weight: bold;">
                <i class="fas fa-chart-bar me-1"></i>پوختەی پسووڵەکان
            </a>
            <?php if (hasPermission('add_sale')): ?>
            <button class="btn" data-bs-toggle="modal" data-bs-target="#addSaleModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردنی فرۆشتن</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4" id="summary-cards">
        <div class="col-md-2 mb-3">
            <div class="card text-center shadow  card-gradient-danger card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-money-bill-wave card-icon"></i>
                    <h6 class="card-title">کۆی قەرزی کڕیاران</h6>
                    <div class="fs-4 fw-bold" id="total-customer-debt">$0</div>
                    <small class="text-light">کۆی قەرزی هەموو کڕیارەکان</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-user-times card-icon"></i>
                    <h6 class="card-title">کڕیارانی قەرزدار</h6>
                    <div class="fs-4 fw-bold" id="customers-with-debt">0</div>
                    <small class="text-light">ژمارەی کڕیارانی قەرزدار</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-center shadow  card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-shopping-cart card-icon"></i>
                    <h6 class="card-title">کۆی فرۆشتنەکان</h6>
                    <div class="fs-4 fw-bold" id="total-sales">0</div>
                    <small class="text-light">ژمارەی هەموو فرۆشتنەکان</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-center shadow  card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-cube card-icon"></i>
                    <h6 class="card-title">کۆی مەتر فرۆشراوەکان</h6>
                    <div class="fs-4 fw-bold" id="total-cubic-meters">0</div>
                    <small class="text-light">کۆی م³ فرۆشراوەکان</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-center shadow card-animate-hover summary-export-card">
                <div class="card-body">
                    <i class="fas fa-file-excel card-icon"></i>
                    <h6 class="card-title">ئیکسپۆرتی کورتە</h6>
                    <button class="btn btn-sm btn-light mt-2" onclick="exportSaleSummaryToExcel()" title="ئیکسپۆرتی کورتەی فرۆشتنەکان بۆ Excel">
                        <i class="fas fa-download me-1"></i>داگرتن
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
      <div class="col-md-3">
        <label>لە بەروار:</label>
        <input type="date" id="filter_from" class="form-control">
      </div>
      <div class="col-md-3">
        <label>بۆ بەروار:</label>
        <input type="date" id="filter_to" class="form-control">
      </div>
      <div class="col-md-3">
        <label for="filter_customer">کڕیار:</label>
        <select class="form-select" id="filter_customer">
          <option value="">هەموو کڕیارەکان</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label for="filter_quantity_min">کەمترین بڕ (م³)</label>
        <input type="number" class="form-control" id="filter_quantity_min" step="0.01" placeholder="بۆ نموونە 5">
      </div>
      <div class="col-md-3">
        <label for="filter_quantity_max">زۆرترین بڕ (م³)</label>
        <input type="number" class="form-control" id="filter_quantity_max" step="0.01" placeholder="بۆ نموونە 10">
      </div>
      <div class="col-md-3 d-flex align-items-end gap-2">
        <button class="btn btn-secondary" id="clearFilterBtn" type="button">پاککردنەوە</button>
      </div>
    </div>
    
    <!-- Duplicate invoice number information -->
    <div class="alert alert-info mb-3" role="alert">
      <i class="fas fa-info-circle me-2"></i>
      <strong>تێبینی:</strong> هەر فرۆشتنێک کە ژمارەی پسووڵەکەی دووبارەیە، ڕیزەکەی بە پاشبنەمای سوور و تێکستی سپی نیشاندەدرێت.
    </div>
    
    <!-- AG Grid Container -->
    <div class="table-responsive">
        <div id="salesGrid" class="ag-theme-alpine"></div>
    </div>
</div>
<!-- Add Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addSaleForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addSaleModalLabel">زیادکردنی فرۆشتن</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="customer_id" class="form-label select2-filter">کڕیار</label>
              <select class="form-select" id="customer_id" name="customer_id">
                <option value="">هەڵبژێرە</option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="recipient" class="form-label">وەرگر</label>
              <select class="form-select" id="recipient" name="recipient_id" data-placeholder="وەرگرێک هەڵبژێرە">
                <option value="">وەرگرێک هەڵبژێرە</option>
                <?php foreach ($recipients as $recipient): 
                    $phoneList = array_filter([
                        !empty($recipient['phone1']) ? $recipient['phone1'] : '',
                        !empty($recipient['phone2']) ? $recipient['phone2'] : ''
                    ]);
                    $searchMeta = trim($recipient['name'] . ' ' . implode(' ', $phoneList));
                ?>
                    <option 
                        value="<?= (int)$recipient['id'] ?>"
                        data-name="<?= htmlspecialchars($recipient['name']) ?>"
                        data-search="<?= htmlspecialchars($searchMeta) ?>"
                    >
                        <?= htmlspecialchars($recipient['name']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="location" class="form-label">شوێن</label>
              <input type="text" class="form-control" id="location" name="location">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="invoice_number" class="form-label">ژمارەی پسوڵە</label>
              <textarea class="form-control" id="invoice_number" name="invoice_number" rows="1" required style="resize: none;"></textarea>
            </div>
            <div class="col-md-4 mb-3">
              <label for="formula_id" class="form-label">فۆرمۆلا</label>
              <select class="form-select" id="formula_id" name="formula_id" required>
                <option value="">هەڵبژێرە</option>
                <?php foreach ($formulas as $f): ?>
                  <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="order_date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="order_date" name="order_date" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="quantity" class="form-label">بڕ (م³)</label>
              <input type="number" class="form-control" id="quantity" name="quantity" min="0" step="0.0001" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="price_per_unit" class="form-label">نرخی یەکە</label>
              <input type="number" class="form-control" id="price_per_unit" name="price_per_unit" min="0" step="0.0001" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="total_price" class="form-label">کۆی نرخ</label>
              <input type="number" class="form-control" id="total_price" name="total_price" min="0" step="0.0001" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="payment_type" class="form-label">جۆری پارەدان</label>
              <select class="form-select" id="payment_type" name="payment_type" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="نەقد">نەقد</option>
                <option value="قەرز">قەرز</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="amount_paid_iq" class="form-label">پارەی دراو بە دینار</label>
              <input type="number" class="form-control" id="amount_paid_iq" name="amount_paid_iq" min="0" step="0.0001" value="0">
            </div>
            <div class="col-md-4 mb-3">
              <label for="amount_paid_usd" class="form-label">پارەی دراو بە دۆلار</label>
              <input type="number" class="form-control" id="amount_paid_usd" name="amount_paid_usd" min="0" step="0.0001" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="remaining_amount" class="form-label">پارەی ماوە</label>
              <input type="number" class="form-control" id="remaining_amount" name="remaining_amount" min="0" step="0.0001" value="0">
            </div>
            <div class="col-md-4 mb-3">
              <label for="dolar_rate" class="form-label">نرخی ١٠٠ دۆلار</label>
              <div class="input-group">
                <input type="number" class="form-control" id="dolar_rate" name="dolar_rate" min="0" step="0.0001" value="150000">
                <button type="button" class="btn btn-outline-secondary" id="refreshDollarRate" title="نوێکردنەوەی نرخی دۆلار">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <label for="discount" class="form-label">داشکاندن</label>
              <div class="input-group">
                <input type="number" class="form-control" id="discount" name="discount" min="0" step="0.0001" value="0">
                <button type="button" class="btn btn-outline-info balance-sale-btn" data-target="discount" title="هاوسەنگکردن">
                  <i class="fas fa-magic"></i>
                </button>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <label for="change_back_iq" class="form-label">باقی بە دینار</label>
              <input type="number" class="form-control" id="change_back_iq" name="change_back_iq" min="0" step="0.0001" value="0">
            </div>
            <div class="col-md-4 mb-3">
              <label for="change_back_usd" class="form-label">باقی بە دۆلار</label>
              <input type="number" class="form-control" id="change_back_usd" name="change_back_usd" min="0" step="0.0001" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-12 mb-3">
              <label for="notes" class="form-label">تێبینی</label>
              <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a href="summery_concrete_receipts.php" class="btn btn-warning me-auto" style="color: white; font-weight: bold;">
            <i class="fas fa-chart-bar me-1"></i>پوختەی پسووڵەکان
          </a>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Update Sale Modal -->
<div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editSaleForm">
        <input type="hidden" id="edit_sale_id" name="edit_sale_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editSaleModalLabel">نوێکردنەوەی فرۆشتن</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="edit_customer_id" class="form-label">کڕیار</label>
              <select class="form-select" id="edit_customer_id" name="edit_customer_id"></select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_recipient" class="form-label">وەرگر</label>
              <select class="form-select" id="edit_recipient" name="edit_recipient_id" data-placeholder="وەرگرێک هەڵبژێرە">
                <option value="">وەرگرێک هەڵبژێرە</option>
                <?php foreach ($recipients as $recipient): 
                    $phoneList = array_filter([
                        !empty($recipient['phone1']) ? $recipient['phone1'] : '',
                        !empty($recipient['phone2']) ? $recipient['phone2'] : ''
                    ]);
                    $searchMeta = trim($recipient['name'] . ' ' . implode(' ', $phoneList));
                ?>
                    <option 
                        value="<?= (int)$recipient['id'] ?>"
                        data-name="<?= htmlspecialchars($recipient['name']) ?>"
                        data-search="<?= htmlspecialchars($searchMeta) ?>"
                    >
                        <?= htmlspecialchars($recipient['name']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_location" class="form-label">شوێن</label>
              <input type="text" class="form-control" id="edit_location" name="edit_location" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="edit_invoice_number" class="form-label">ژمارەی پسوڵە</label>
              <textarea class="form-control" id="edit_invoice_number" name="edit_invoice_number" rows="1" required style="resize: none;"></textarea>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_formula_id" class="form-label">فۆرمۆلا</label>
              <select class="form-select" id="edit_formula_id" name="edit_formula_id" required></select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_order_date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="edit_order_date" name="edit_order_date" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="edit_quantity" class="form-label">بڕ (m³)</label>
              <input type="number" class="form-control" id="edit_quantity" name="edit_quantity" min="0" step="0.0001" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_price_per_unit" class="form-label">نرخی یەکە</label>
              <input type="number" class="form-control" id="edit_price_per_unit" name="edit_price_per_unit" min="0" step="0.0001" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_total_price" class="form-label">کۆی نرخ</label>
              <input type="number" class="form-control" id="edit_total_price" name="edit_total_price" min="0" step="0.0001" required readonly>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="edit_payment_type" class="form-label">جۆری پارەدان</label>
              <select class="form-select" id="edit_payment_type" name="edit_payment_type" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="نەقد">نەقد</option>
                <option value="قەرز">قەرز</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_amount_paid_iq" class="form-label">پارەی دراو بە دینار</label>
              <input type="number" class="form-control" id="edit_amount_paid_iq" name="edit_amount_paid_iq" min="0" step="0.0001" value="0">
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_amount_paid_usd" class="form-label">پارەی دراو بە دۆلار</label>
              <input type="number" class="form-control" id="edit_amount_paid_usd" name="edit_amount_paid_usd" min="0" step="0.0001" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="edit_remaining_amount" class="form-label">پارەی ماوە</label>
              <input type="number" class="form-control" id="edit_remaining_amount" name="edit_remaining_amount" min="0" step="0.0001" value="0" readonly>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_dolar_rate" class="form-label">نرخی ١٠٠ دۆلار</label>
              <div class="input-group">
                <input type="number" class="form-control" id="edit_dolar_rate" name="edit_dolar_rate" min="0" step="0.0001" value="150000">
                <button type="button" class="btn btn-outline-secondary" id="refreshDollarRateEdit" title="نوێکردنەوەی نرخی دۆلار">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_discount" class="form-label">داشکاندن</label>
              <div class="input-group">
                <input type="number" class="form-control" id="edit_discount" name="edit_discount" min="0" step="0.0001" value="0">
                <button type="button" class="btn btn-outline-info balance-sale-btn" data-target="edit_discount" title="هاوسەنگکردن">
                  <i class="fas fa-magic"></i>
                </button>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_change_back_iq" class="form-label">باقی بە دینار</label>
              <input type="number" class="form-control" id="edit_change_back_iq" name="edit_change_back_iq" min="0" step="0.0001" value="0">
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_change_back_usd" class="form-label">باقی بە دۆلار</label>
              <input type="number" class="form-control" id="edit_change_back_usd" name="edit_change_back_usd" min="0" step="0.0001" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-12 mb-3">
              <label for="edit_notes" class="form-label">تێبینی</label>
              <textarea class="form-control" id="edit_notes" name="edit_notes" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a href="summery_concrete_receipts.php" class="btn btn-warning me-auto" style="color: white; font-weight: bold;">
            <i class="fas fa-chart-bar me-1"></i>پوختەی پسووڵەکان
          </a>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">نوێکردنەوە</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<!-- AG Grid JS -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/ag_grid_base.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/select2_script.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script nonce="<?php echo $csp_nonce; ?>">
    // Pass permissions to JavaScript
    window.userPermissions = {
      canAdd: <?php echo hasPermission('add_sale') ? 'true' : 'false'; ?>,
      canEdit: <?php echo hasPermission('update_sale') ? 'true' : 'false'; ?>,
      canDelete: <?php echo hasPermission('delete_sale') ? 'true' : 'false'; ?>
    };
</script>
<script src="../assets/js/sale/add_sale.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/sale/ag_grid_sale.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/sale/delete_sale.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/sale/update_sale.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/sale/sale.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/sale/summary_cards.js" nonce="<?php echo $csp_nonce; ?>"></script>

<script nonce="<?php echo $csp_nonce; ?>">
// Filter functionality for customer and date
$(document).ready(function() {
    // Add event listeners for all filters
    $('#filter_customer, #filter_from, #filter_to, #filter_quantity_min, #filter_quantity_max').on('input change', function() {
        applyFilters();
    });
    
    // Clear all filters
    $('#clearFilterBtn').on('click', function() {
        $('#filter_customer').val('');
        $('#filter_from').val('');
        $('#filter_to').val('');
        $('#filter_quantity_min').val('');
        $('#filter_quantity_max').val('');
        applyFilters();
    });
    
    // Function to apply all filters
    function applyFilters() {
        const customerId = $('#filter_customer').val();
        const fromDate = $('#filter_from').val();
        const toDate = $('#filter_to').val();
        const minQuantity = $('#filter_quantity_min').val();
        const maxQuantity = $('#filter_quantity_max').val();
        
        // Build filter parameters
        const params = new URLSearchParams();
        if (customerId) params.append('customer_id', customerId);
        if (fromDate) params.append('from', fromDate);
        if (toDate) params.append('to', toDate);
        if (minQuantity) params.append('min_quantity', minQuantity);
        if (maxQuantity) params.append('max_quantity', maxQuantity);
        
        // Call the existing loadSales function with filters
        if (typeof loadSales === 'function') {
            loadSales(params.toString());
        } else if (typeof window.reloadSales === 'function') {
            window.reloadSales();
        }
        
        // Also update summary cards if the function exists
        if (typeof loadSummaryCardsData === 'function') {
            loadSummaryCardsData(params.toString());
        }
    }
    
    // Set default date filters to current month
    const now = new Date();
    const currentMonth = now.getMonth() + 1;
    const currentYear = now.getFullYear();
    const fromDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`;
    const toDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${new Date(currentYear, currentMonth, 0).getDate()}`;
    
    if (!$('#filter_from').val()) $('#filter_from').val(fromDate);
    if (!$('#filter_to').val()) $('#filter_to').val(toDate);
    
    // Apply filters on page load
    setTimeout(applyFilters, 100);
});
</script>

</body>
</html>
