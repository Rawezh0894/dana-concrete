<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}
if (!hasPermission('view_purchase')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
// Note: add_purchase permission is checked in the UI, not here
// Users with only view_purchase permission can still access the page
$bins = $pdo->query("SELECT id, name FROM bins_silos")->fetchAll(PDO::FETCH_ASSOC);
$materials = $pdo->query("SELECT id, name FROM materials")->fetchAll(PDO::FETCH_ASSOC);
$locations = $pdo->query("SELECT id, name FROM locations")->fetchAll(PDO::FETCH_ASSOC);
$drivers = $pdo->query("SELECT id, name FROM drivers")->fetchAll(PDO::FETCH_ASSOC);
$companies = $pdo->query("SELECT id, name FROM company")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زیادکردنی کڕین</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
   
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- AG Grid CSS -->
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css" rel="stylesheet">
    <link href="../assets/css/comon/ag_grid.css" rel="stylesheet">
    <link href="../assets/css/purchase/ag_grid_purchase.css" rel="stylesheet">
    
    <style>
        /* Filter styling */
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .filter-section label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .filter-section .form-select {
            border: 1px solid #ced4da;
            border-radius: 6px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .filter-section .form-select:focus {
            border-color: var(--seafoam-green);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .filter-section .form-control {
            border: 1px solid #ced4da;
            border-radius: 6px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .filter-section .form-control:focus {
            border-color: var(--seafoam-green);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .clear-filter-btn {
            background: var(--kelly-green) !important;
            border-color: var(--kelly-green) !important;
            color: white !important;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .clear-filter-btn:hover {
            background: #1e7e34 !important;
            border-color: #1e7e34 !important;
            transform: translateY(-1px);
        }
        
        /* Button Styles */
        .btn-drivers {
            background: var(--kelly-green) !important;
            border-color: var(--kelly-green) !important;
            color: white !important;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            white-space: nowrap;
        }
        
        .btn-drivers:hover {
            background: #157347 !important;
            border-color: #157347 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(21, 115, 71, 0.3);
        }
        
        .btn-add-purchase {
            background: var(--seafoam-green) !important;
            border-color: var(--seafoam-green) !important;
            color: white !important;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            white-space: nowrap;
        }
        
        .btn-add-purchase:hover {
            background: #1aa179 !important;
            border-color: #1aa179 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(32, 201, 151, 0.3);
        }
        
        .btn-monthly-report {
            background: var(--seafoam-green) !important;
            border-color: var(--seafoam-green) !important;
            color: white !important;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .btn-monthly-report:hover {
            background: #1aa179 !important;
            border-color: #1aa179 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(32, 201, 151, 0.3);
        }
        
        .export-btn {
            background: var(--warning) !important;
            border-color: var(--warning) !important;
            color: #212529 !important;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .export-btn:hover {
            background: #e0a800 !important;
            border-color: #e0a800 !important;
            transform: translateY(-1px);
            color: #212529 !important;
            box-shadow: 0 4px 8px rgba(224, 168, 0, 0.3);
        }
        
        /* Responsive button group styling */
        .btn-group {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 0.375rem;
            overflow: hidden;
        }
        
        .btn-group .btn {
            border-radius: 0 !important;
        }
        
        .btn-group .btn:first-child {
            border-top-right-radius: 0.375rem !important;
            border-bottom-right-radius: 0.375rem !important;
        }
        
        .btn-group .btn:last-child {
            border-top-left-radius: 0.375rem !important;
            border-bottom-left-radius: 0.375rem !important;
        }
        
        /* Mobile responsive adjustments */
        @media (max-width: 767.98px) {
            .d-flex.flex-wrap {
                justify-content: center;
            }
            
            .btn-group {
                width: 100%;
            }
            
            .btn-group .btn {
                flex: 1;
            }
            
            .btn-drivers,
            .btn-add-purchase {
                width: 100%;
            }
        }
        
        @media (min-width: 576px) and (max-width: 767.98px) {
            .btn-drivers,
            .btn-add-purchase {
                width: auto;
            }
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
        
        /* Fix for select2 in flexbox layout */
        .d-flex .select2-container {
            flex: 1 1 auto;
            min-width: 0;
        }
        
        .d-flex .select2-container--default {
            width: 100% !important;
        }
        
        /* Ensure button has fixed width and proper alignment */
        .d-flex .btn.flex-shrink-0 {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 38px;
        }
    </style>
    <!-- jQuery (پێش هەموو شت) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <!-- select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto">
            <button class="btn btn-drivers" data-bs-toggle="modal" data-bs-target="#driversManagementModal">
                <i class="fas fa-users me-1"></i> <span class="d-none d-sm-inline">شۆفێرەکان</span>
            </button>
            <div class="btn-group" role="group">
                <button class="btn export-btn" onclick="exportPurchaseToExcel()" title="ئیکسپۆرتی هەموو زانیارییەکانی کڕین بۆ Excel">
                    <i class="fas fa-file-excel me-1"></i><span class="d-none d-sm-inline">Excel</span>
                </button>
                <button class="btn export-btn" onclick="exportPurchaseToCSV()" title="ئیکسپۆرتی هەموو زانیارییەکانی کڕین بۆ CSV">
                    <i class="fas fa-file-csv me-1"></i><span class="d-none d-sm-inline">CSV</span>
                </button>
            </div>
            <div class="btn-group" role="group">
                <button class="btn btn-monthly-report" onclick="exportPurchaseMonthlyReport()" title="ڕاپۆرتی مانگانەی کڕینەکان بۆ Excel">
                    <i class="fas fa-chart-line me-1"></i><span class="d-none d-sm-inline">Excel</span>
                </button>
                <button class="btn btn-monthly-report" onclick="exportPurchaseMonthlyReportToCSV()" title="ڕاپۆرتی مانگانەی کڕینەکان بۆ CSV">
                    <i class="fas fa-file-csv me-1"></i><span class="d-none d-sm-inline">CSV</span>
                </button>
            </div>
            <?php if (hasPermission('add_purchase')): ?>
            <button class="btn btn-add-purchase" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">
                <i class="fas fa-plus me-1"></i> <span class="d-none d-sm-inline">زیادکردنی کڕین</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4" id="purchaseSummaryCards">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-danger card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-money-bill-wave card-icon"></i>
                    <h6 class="card-title">کۆی قەرزی ئێمە</h6>
                    <div class="fs-4 fw-bold" id="total-debt">$0</div>
                    <small class="text-light">کۆی قەرزی کۆمپانیاکان</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-building card-icon"></i>
                    <h6 class="card-title">کۆی ژمارەی کۆمپانیاکان</h6>
                    <div class="fs-4 fw-bold" id="total-companies">0</div>
                    <small class="text-light">ژمارەی هەموو کۆمپانیاکان</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-hand-holding-usd card-icon"></i>
                    <h6 class="card-title">کۆمپانیاکانی قەرزدار</h6>
                    <div class="fs-4 fw-bold" id="indebted-companies">0</div>
                    <small class="text-light">کۆمپانیاکانی قەرزدار</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow card-animate-hover summary-export-card">
                <div class="card-body">
                    <i class="fas fa-file-excel card-icon"></i>
                    <h6 class="card-title">ئیکسپۆرتی کورتە</h6>
                    <button class="btn btn-sm btn-light mt-2" onclick="exportPurchaseSummaryToExcel()" title="ئیکسپۆرتی کورتەی کڕینەکان بۆ Excel">
                        <i class="fas fa-download me-1"></i>داگرتن
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Date Filters Row -->
    <div class="filter-section">
      <div class="row">
        <div class="col-md-3">
          <label>لە بەروار:</label>
          <input type="date" id="filter_from" class="form-control">
        </div>
        <div class="col-md-3">
          <label>بۆ بەروار:</label>
          <input type="date" id="filter_to" class="form-control">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn clear-filter-btn" id="clearFilterBtn" type="button">پاککردنەوەی هەموو فلتەرەکان</button>
        </div>
      </div>
    </div>
    
    <!-- Global Search Row -->
    <div class="filter-section">
      <div class="row align-items-end">
        <div class="col-md-10">
          <label for="purchase_global_search">گەڕان لە هەموو خانەکاندا:</label>
          <input type="text" class="form-control" id="purchase_global_search" placeholder="گەڕان بە کۆمپانیا، شوێن، شۆفێر، ژمارەی پسوڵە، مەواد، بەروار...">
          <small class="text-muted">گەڕان لە هەموو داتاکانی database</small>
        </div>
        <div class="col-md-2">
          <button class="btn btn-warning w-100" id="clearColumnFiltersBtn" type="button">
            <i class="fas fa-filter-circle-xmark me-1"></i>پاککردنەوەی فلتەرەکانی کۆڵۆم
          </button>
        </div>
      </div>
    </div>
    
    <!-- Additional Filters Row -->
    <div class="filter-section">
      <div class="row">
        <div class="col-md-3">
          <label for="filter_company">کۆمپانیا:</label>
          <select class="form-select select2" id="filter_company">
            <option value="">هەموو کۆمپانیاکان</option>
            <?php foreach ($companies as $comp): ?>
              <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_location">شوێن:</label>
          <select class="form-select select2" id="filter_location">
            <option value="">هەموو شوێنەکان</option>
            <?php foreach ($locations as $loc): ?>
              <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_driver">شۆفێر:</label>
          <select class="form-select select2" id="filter_driver">
            <option value="">هەموو شۆفێرەکان</option>
            <?php foreach ($drivers as $drv): ?>
              <option value="<?= $drv['id'] ?>"><?= htmlspecialchars($drv['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_material">مەواد:</label>
          <select class="form-select select2" id="filter_material">
            <option value="">هەموو مەوادەکان</option>
            <?php foreach ($materials as $mat): ?>
              <option value="<?= $mat['id'] ?>"><?= htmlspecialchars($mat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    <!-- AG Grid Container -->
    <div class="table-responsive">
        <div id="purchaseGrid" class="ag-grid-container ag-theme-alpine"></div>
    </div>
</div>
<!-- Add Purchase Modal -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-labelledby="addPurchaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addPurchaseForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addPurchaseModalLabel">زیادکردنی کڕین</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
          <div class="col-md-6 mb-3">
              <label for="company_id" class="form-label">کۆمپانیا</label>
              <select class="form-select select2" id="company_id" name="company_id" required>
                <option value="">کۆمپانیا</option>
                <?php foreach ($companies as $comp): ?>
                  <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="driver_id" class="form-label">شۆفێر</label>
              <div class="input-group">
                <!-- <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#addDriverModal" style="background: var(--seafoam-green); color: white; font-weight: bold;">+</button> -->
                <select class="form-select select2" id="driver_id" name="driver_id" required>
                  <option value="">شۆفێرەکان</option>
                  <?php foreach ($drivers as $drv): ?>
                    <option value="<?= $drv['id'] ?>"><?= htmlspecialchars($drv['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <div class="col-md-12 mb-3">
              <label class="form-label fw-bold">تڕێلە (ئەگەر هی کارگە بوو)</label>
              <select class="form-select select2" id="factory_truck_id" name="factory_truck_id">
                <option value="">-- بارهەڵگری دەرەکی (هیچ کام) --</option>
                <?php 
                $ft_stmt = $pdo->query("SELECT id, truck_name FROM factory_trucks WHERE is_active = 1");
                while($ft = $ft_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                  <option value="<?= $ft['id'] ?>"><?= htmlspecialchars($ft['truck_name']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
          <div class="row">
          <div class="col-md-6 mb-3">
              <label for="location_id" class="form-label">شوێن</label>
              <div class="d-flex gap-2">
                <select class="form-select select2 flex-grow-1" id="location_id" name="location_id" required>
                  <option value="">شوێن</option>
                  <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" id="openAddLocationFromPurchaseBtn" class="btn flex-shrink-0" style="background: var(--seafoam-green); color: white; font-weight: bold; width: 45px; padding: 0;">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label for="invoice_number" class="form-label">ژمارەی پسوڵە</label>
              <input type="text" class="form-control" id="invoice_number" name="invoice_number" required>
            </div>
           
          </div>
          <div class="row">
           
            <div class="col-md-6 mb-3">
              <label for="material_id" class="form-label">مەواد</label>
              <select class="form-select select2" id="material_id" name="material_id" required>
                <option value="">هەڵبژێرە</option>
                <?php foreach ($materials as $mat): ?>
                  <option value="<?= $mat['id'] ?>"><?= htmlspecialchars($mat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="bin_id" class="form-label">چاۆ/سایلۆ</label>
              <select class="form-select" id="bin_id" name="bin_id">
                <option value="">هەڵبژێرە</option>
                <?php foreach ($bins as $bin): ?>
                  <option value="<?= $bin['id'] ?>"><?= htmlspecialchars($bin['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="date" name="date" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="type" class="form-label">جۆری دراو</label>
              <select class="form-select" id="type" name="type" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="total_weight" class="form-label">کێشی گشتی (کگم)</label>
              <input type="number" class="form-control" id="total_weight" min="0" step="0.01" placeholder="کێشی گشتی">
      
            </div>
            <div class="col-md-6 mb-3">
              <label for="kg" class="form-label">چەند کیلۆ</label>
              <input type="number" class="form-control" id="kg" name="kg" min="0" step="0.01" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12 mb-3">
                <div id="pricePerKgIqdGroup">
                    <label for="price_per_kg_iqd" class="form-label">نرخی یەک طەن بە دینار</label>
                    <input type="number" class="form-control" id="price_per_kg_iqd" name="price_per_kg_iqd" min="0" step="0.01" value="0">
                </div>
                <div id="pricePerKgUsdGroup">
                    <label for="price_per_kg_usd" class="form-label">نرخی یەک طەن بە دۆلار</label>
                    <input type="number" class="form-control" id="price_per_kg_usd" name="price_per_kg_usd" min="0" step="0.01" value="0">
                </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="exchange_rate" class="form-label">نرخی 100 دۆلار بە دینار</label>
              <input type="number" class="form-control" id="exchange_rate" name="exchange_rate" min="0" step="1" required value="141000">
            </div>
           
            <div class="col-md-6 mb-3">
              <label for="payment_type" class="form-label">جۆری پارەدان</label>
              <select class="form-select" id="payment_type" name="payment_type" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="نەقد">نەقد</option>
                <option value="قەرز">قەرز</option>
              </select>
            </div>
          </div>
          <div class="row">
          <div class="col-md-6 mb-3">
              <label for="price" class="form-label">بڕی پارە بە دۆلار</label>
              <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="amount_iqd" class="form-label">بڕی پارە بە دینار</label>
              <input type="number" class="form-control" id="amount_iqd" name="amount_iqd" min="0" step="0.01" value="0">
            </div>
          
          </div>
          <div class="row">
          <div class="col-md-6 mb-3">
              <label for="paid_usd" class="form-label">بری پارەی دراو بە دۆلار</label>
              <input type="number" class="form-control" id="paid_usd" name="paid_usd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="paid_iqd" class="form-label">بری پارەی دراو بە دینار</label>
              <input type="number" class="form-control" id="paid_iqd" name="paid_iqd" min="0" step="0.01" value="0">
            </div>
            
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="remaining_usd" class="form-label">بری پارەی ماوە بە دۆلار</label>
              <input type="number" class="form-control" id="remaining_usd" name="remaining_usd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="remaining_iqd" class="form-label">بری پارەی ماوە بە دینار</label>
              <input type="number" class="form-control" id="remaining_iqd" name="remaining_iqd" min="0" step="0.01" value="0">
            </div>
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
<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addLocationForm">
        <div class="modal-header">
          <h5 class="modal-title">زیادکردنی شوێن</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="text" class="form-control" name="name" placeholder="ناوی شوێن" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Drivers Management Modal -->
<div class="modal fade" id="driversManagementModal" tabindex="-1" aria-labelledby="driversManagementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="driversManagementModalLabel">وردەکاری شۆفێرەکان</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Add Driver Form -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0">زیادکردنی شۆفێری نوێ</h6>
          </div>
          <div class="card-body">
            <form id="addDriverFormManagement">
              <div class="row">
                <div class="col-md-6">
                  <label for="driver_name" class="form-label">ناوی شۆفێر</label>
                  <input type="text" class="form-control" id="driver_name" name="name" placeholder="ناوی شۆفێر" required>
                </div>
                <div class="col-md-6">
                  <label for="driver_load_capacity" class="form-label">بەتاڵەی بارهەڵگر (کگم)</label>
                  <input type="number" class="form-control" id="driver_load_capacity" name="load_capacity" placeholder="بەتاڵەی بارهەڵگر" min="0" step="0.01">
                </div>
              </div>
              <div class="mt-3">
                <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">
                  <i class="fas fa-plus me-1"></i>زیادکردن
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Drivers List -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0">لیستی شۆفێرەکان</h6>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-hover" id="driversTable">
                <thead style="background: var(--kelly-green); color: white;">
                  <tr>
                    <th>#</th>
                    <th>ناوی شۆفێر</th>
                    <th>بەتاڵەی بارهەڵگر</th>
                    <th>کردارەکان</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Drivers will be loaded here -->
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Locations List -->
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">لیستی شوێنەکان</h6>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-hover" id="locationsTable">
                <thead style="background: var(--kelly-green); color: white;">
                  <tr>
                    <th>#</th>
                    <th>ناوی شوێن</th>
                    <th>کردارەکان</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Locations will be loaded here -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Driver Modal -->
<div class="modal fade" id="editDriverModal" tabindex="-1" aria-labelledby="editDriverModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editDriverForm">
        <input type="hidden" id="edit_driver_modal_id" name="id">
        <div class="modal-header">
          <h5 class="modal-title" id="editDriverModalLabel">نوێکردنەوەی شۆفێر</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_driver_name" class="form-label">ناوی شۆفێر</label>
            <input type="text" class="form-control" id="edit_driver_name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="edit_driver_load_capacity" class="form-label">بەتاڵەی بارهەڵگر (کگم)</label>
            <input type="number" class="form-control" id="edit_driver_load_capacity" name="load_capacity" min="0" step="0.01">
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

<!-- Add Driver Modal (هەمان شێوە) -->
<div class="modal fade" id="addDriverModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addDriverForm">
        <div class="modal-header">
          <h5 class="modal-title">زیادکردنی شۆفێر</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="text" class="form-control" name="name" placeholder="ناوی شۆفێر" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit Purchase Modal -->
<div class="modal fade" id="editPurchaseModal" tabindex="-1" aria-labelledby="editPurchaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editPurchaseForm">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editPurchaseModalLabel">نوێکردنەوەی کڕین</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_company_id" class="form-label">کۆمپانیا</label>
              <select class="form-select select2" id="edit_company_id" name="company_id" required>
                <option value="">کۆمپانیا</option>
                <?php foreach ($companies as $comp): ?>
                  <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_driver_id" class="form-label">شۆفێر</label>
              <select class="form-select select2" id="edit_driver_id" name="driver_id" required>
                <option value="">شۆفێرەکان</option>
                <?php foreach ($drivers as $drv): ?>
                  <option value="<?= $drv['id'] ?>"><?= htmlspecialchars($drv['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_location_id" class="form-label">شوێن</label>
              <select class="form-select select2" id="edit_location_id" name="location_id" required>
                <option value="">شوێن</option>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_invoice_number" class="form-label">ژمارەی پسوڵە</label>
              <input type="text" class="form-control" id="edit_invoice_number" name="invoice_number" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_material_id" class="form-label">مەواد</label>
              <select class="form-select select2" id="edit_material_id" name="material_id" required>
                <option value="">هەڵبژێرە</option>
                <?php foreach ($materials as $mat): ?>
                  <option value="<?= $mat['id'] ?>"><?= htmlspecialchars($mat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_bin_id" class="form-label">چاۆ/سایلۆ</label>
              <select class="form-select" id="edit_bin_id" name="bin_id" required>
                <option value="">هەڵبژێرە</option>
                <?php foreach ($bins as $bin): ?>
                  <option value="<?= $bin['id'] ?>"><?= htmlspecialchars($bin['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="edit_date" name="date" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_type" class="form-label">جۆری دراو</label>
              <select class="form-select" id="edit_type" name="type" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_kg" class="form-label">چەند کیلۆ</label>
              <input type="number" class="form-control" id="edit_kg" name="kg" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
                <div id="edit_pricePerKgIqdGroup">
                    <label for="edit_price_per_kg_iqd" class="form-label">نرخی یەک کیلۆ بە دینار</label>
                    <input type="number" class="form-control" id="edit_price_per_kg_iqd" name="price_per_kg_iqd" min="0" step="0.01" required>
                </div>
                <div id="edit_pricePerKgUsdGroup">
                    <label for="edit_price_per_kg_usd" class="form-label">نرخی یەک کیلۆ بە دۆلار</label>
                    <input type="number" class="form-control" id="edit_price_per_kg_usd" name="price_per_kg_usd" min="0" step="0.01" required>
                </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_exchange_rate" class="form-label">نرخی 100 دۆلار بە دینار</label>
              <input type="number" class="form-control" id="edit_exchange_rate" name="exchange_rate" min="0" step="1" required value="141000">
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_payment_type" class="form-label">جۆری پارەدان</label>
              <select class="form-select" id="edit_payment_type" name="payment_type" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="نەقد">نەقد</option>
                <option value="قەرز">قەرز</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_price" class="form-label">بڕی پارە بە دۆلار</label>
              <input type="number" class="form-control" id="edit_price" name="price" min="0" step="0.01" required value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_amount_iqd" class="form-label">بڕی پارە بە دینار</label>
              <input type="number" class="form-control" id="edit_amount_iqd" name="amount_iqd" min="0" step="0.01" value="0" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_paid_usd" class="form-label">بری پارەی دراو بە دۆلار</label>
              <input type="number" class="form-control" id="edit_paid_usd" name="paid_usd" min="0" step="0.01" value="0" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_paid_iqd" class="form-label">بری پارەی دراو بە دینار</label>
              <input type="number" class="form-control" id="edit_paid_iqd" name="paid_iqd" min="0" step="0.01" value="0" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_remaining_usd" class="form-label">بری پارەی ماوە بە دۆلار</label>
              <input type="number" class="form-control" id="edit_remaining_usd" name="remaining_usd" min="0" step="0.01" value="0" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_remaining_iqd" class="form-label">بری پارەی ماوە بە دینار</label>
              <input type="number" class="form-control" id="edit_remaining_iqd" name="remaining_iqd" min="0" step="0.01" value="0" required>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<!-- AG Grid JS -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/ag_grid_base.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/table-controler.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/select2_script.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script nonce="<?php echo $csp_nonce; ?>">
    // Pass permissions to JavaScript
    window.userPermissions = {
      canAdd: <?php echo hasPermission('add_purchase') ? 'true' : 'false'; ?>,
      canEdit: <?php echo hasPermission('edit_purchase') ? 'true' : 'false'; ?>,
      canDelete: <?php echo hasPermission('delete_purchase') ? 'true' : 'false'; ?>
    };
</script>
<script src="../assets/js/purchase/add_purchase.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/purchase/ag_grid_purchase.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/purchase/summary.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/location_driver/driver.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/location_driver/location.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/location_driver/load_locations.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/location_driver/delete_location.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/purchase/delete_purchase.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/purchase/purchase.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/purchase/update_purchase.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/drivers/drivers_management.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script nonce="<?php echo $csp_nonce; ?>">
// Add modal: dynamic price per kg fields
$(function() {
    function handleAddTypeChange() {
        togglePricePerKgInputsFor('#type', '#pricePerKgIqdGroup', '#pricePerKgUsdGroup');
    }
    $('#type').on('change', handleAddTypeChange);
    handleAddTypeChange();
});
$('#kg, #price_per_kg_iqd, #price_per_kg_usd, #type, #price, #paid_usd, #paid_iqd, #exchange_rate').on('input change', function() {
    updateAmountsFor('');
});

// Special handling for amount_iqd to allow manual input
$('#amount_iqd').on('input', function() {
    updateAmountsFor('');
});

// Edit modal: dynamic price per kg fields
$(function() {
    function handleEditTypeChange() {
        togglePricePerKgInputsFor('#edit_type', '#edit_pricePerKgIqdGroup', '#edit_pricePerKgUsdGroup');
    }
    $('#edit_type').on('change', handleEditTypeChange);
    handleEditTypeChange();
});
$('#edit_kg, #edit_price_per_kg_iqd, #edit_price_per_kg_usd, #edit_type, #edit_price, #edit_paid_usd, #edit_paid_iqd, #edit_exchange_rate').on('input change', function() {
    updateAmountsFor('edit_');
});

// Special handling for edit_amount_iqd to allow manual input
$('#edit_amount_iqd').on('input', function() {
    updateAmountsFor('edit_');
});
$('#editPurchaseModal').on('shown.bs.modal', function() {
    updateAmountsFor('edit_');
});

// Filter functionality for company, location, driver, and material
$(document).ready(function() {
    let searchTimeout = null;
    
    // Global search with debounce
    $('#purchase_global_search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            applyFilters();
        }, 500); // Wait 500ms after user stops typing
    });
    
    // Add event listeners for all filters
    $('#filter_company, #filter_location, #filter_driver, #filter_material, #filter_from, #filter_to').on('change', function() {
        applyFilters();
    });
    
    // Clear all filters
    $('#clearFilterBtn').on('click', function() {
        $('#filter_company').val('');
        $('#filter_location').val('');
        $('#filter_driver').val('');
        $('#filter_material').val('');
        $('#filter_from').val('');
        $('#filter_to').val('');
        $('#purchase_global_search').val('');
        applyFilters();
    });
    
    // Clear column filters
    $('#clearColumnFiltersBtn').on('click', function() {
        if (typeof gridApi !== 'undefined' && gridApi) {
            gridApi.setFilterModel(null);
            if (typeof loadPurchaseData === 'function') {
                loadPurchaseData();
            }
        }
    });
    
    // Function to apply all filters
    function applyFilters() {
        // Use AG Grid load function if available
        if (typeof loadPurchaseData === 'function') {
            loadPurchaseData();
        }
        
        // Also update summary cards if the function exists
        if (typeof loadPurchaseSummary === 'function') {
            const params = new URLSearchParams();
        const companyId = $('#filter_company').val();
        const locationId = $('#filter_location').val();
        const driverId = $('#filter_driver').val();
        const materialId = $('#filter_material').val();
        const fromDate = $('#filter_from').val();
        const toDate = $('#filter_to').val();
        
        if (companyId) params.append('company_id', companyId);
        if (locationId) params.append('location_id', locationId);
        if (driverId) params.append('driver_id', driverId);
        if (materialId) params.append('material_id', materialId);
        if (fromDate) params.append('from', fromDate);
        if (toDate) params.append('to', toDate);
        
            loadPurchaseSummary(params.toString());
        }
    }
    
    // Apply filters on page load (shows all records by default)
    setTimeout(applyFilters, 100);
});

// Handle nested modals (Add Purchase -> Drivers/Location) safely
(function manageNestedPurchaseModals() {
    const addPurchaseModalEl = document.getElementById('addPurchaseModal');
    const addLocationModalEl = document.getElementById('addLocationModal');
    const driversManagementModalEl = document.getElementById('driversManagementModal');
    if (!addPurchaseModalEl) return;

    let shouldReopenAddPurchase = false;

    function cleanupExtraBackdrops() {
        const openModals = document.querySelectorAll('.modal.show').length;
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (openModals === 0) {
            backdrops.forEach((bd) => bd.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
        } else if (backdrops.length > openModals) {
            for (let i = 0; i < backdrops.length - openModals; i++) {
                backdrops[i].remove();
            }
        }
    }

    function openChildModalSafely(childModalEl) {
        if (!childModalEl) return;
        const addPurchaseIsOpen = addPurchaseModalEl.classList.contains('show');
        shouldReopenAddPurchase = addPurchaseIsOpen;
        if (addPurchaseIsOpen) {
            // Prevent aria-hidden/focus conflict: blur focused element before hiding parent modal
            const active = document.activeElement;
            if (active && addPurchaseModalEl.contains(active) && typeof active.blur === 'function') {
                active.blur();
            }
            document.body.focus();
            bootstrap.Modal.getOrCreateInstance(addPurchaseModalEl).hide();
        }
        setTimeout(() => {
            bootstrap.Modal.getOrCreateInstance(childModalEl).show();
        }, 120);
    }

    // + location button inside Add Purchase modal
    const addLocationFromPurchaseBtn = document.getElementById('openAddLocationFromPurchaseBtn');
    if (addLocationFromPurchaseBtn) {
        addLocationFromPurchaseBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openChildModalSafely(addLocationModalEl);
        });
    }

    // Drivers button can be clicked while Add Purchase is open
    document.querySelectorAll('[data-bs-target="#driversManagementModal"]').forEach((btn) => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openChildModalSafely(driversManagementModalEl);
        });
    });

    [addLocationModalEl, driversManagementModalEl].forEach((modalEl) => {
        if (!modalEl) return;
        modalEl.addEventListener('hidden.bs.modal', function () {
            cleanupExtraBackdrops();
            if (shouldReopenAddPurchase) {
                bootstrap.Modal.getOrCreateInstance(addPurchaseModalEl).show();
                shouldReopenAddPurchase = false;
            }
        });
    });

    addPurchaseModalEl.addEventListener('hidden.bs.modal', cleanupExtraBackdrops);
})();

// Load drivers data with load_capacity for automatic kg calculation
let driversData = {};

function loadDriversData() {
    $.ajax({
        url: '../process/drivers/select_drivers.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Store drivers data in an object for quick lookup
                driversData = {};
                response.data.forEach(driver => {
                    driversData[driver.id] = {
                        id: driver.id,
                        name: driver.name,
                        load_capacity: driver.load_capacity ? parseFloat(driver.load_capacity) : 0
                    };
                });
            }
        },
        error: function() {
            console.error('هەڵە لە وەرگرتنی زانیارییەکانی شۆفێرەکان');
        }
    });
}

// Calculate kg automatically when total_weight is entered
function calculateKgFromTotalWeight() {
    const driverId = $('#driver_id').val();
    const totalWeight = parseFloat($('#total_weight').val()) || 0;
    
    if (!driverId) {
        // If no driver selected, show a message
        if (totalWeight > 0) {
            $('#total_weight').addClass('is-invalid');
            return;
        } else {
            $('#total_weight').removeClass('is-invalid');
            return;
        }
    }
    
    $('#total_weight').removeClass('is-invalid');
    
    const driver = driversData[driverId];
    if (!driver) {
        console.error('شۆفێر نەدۆزرایەوە');
        return;
    }
    
    const loadCapacity = driver.load_capacity || 0;
    
    if (totalWeight > 0 && loadCapacity > 0) {
        const calculatedKg = totalWeight - loadCapacity;
        if (calculatedKg >= 0) {
            $('#kg').val(calculatedKg.toFixed(2));
            // Trigger the amount calculation
            $('#kg').trigger('input');
        } else {
            // If calculated value is negative, show warning but still set it
            $('#kg').val(calculatedKg.toFixed(2));
            $('#kg').trigger('input');
        }
    } else if (totalWeight > 0 && loadCapacity === 0) {
        // If driver has no load capacity, use total weight as kg
        $('#kg').val(totalWeight.toFixed(2));
        $('#kg').trigger('input');
    }
}

// Load USD to IQD exchange rate from API
function loadUsdRate() {
    // API configuration
    const apiUrl = 'https://dinarapi.hediworks.site/api/get-price';
    const apiToken = 'S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
    const id = '8'; // 100 dollar ID
    
    $.ajax({
        url: `${apiUrl}?id=${id}&api_token=${apiToken}`,
        type: 'GET',
        dataType: 'json',
        timeout: 5000, // 5 seconds timeout
        success: function(response) {
            console.log('API Response:', response);
            
            // Check different possible response formats
            let rate = null;
            
            if (response.success && response.data && response.data.price) {
                rate = response.data.price;
            } else if (response.value) {
                rate = response.value;
            } else if (response.price) {
                rate = response.price;
            } else if (response.rate) {
                rate = response.rate;
            }
            
            if (rate && rate > 0) {
                $('#exchange_rate').val(rate);
                console.log('USD rate loaded successfully from API:', rate);
                // Trigger calculations if needed
                if (typeof updateAmountsFor === 'function') {
                    updateAmountsFor('');
                }
            } else {
                console.warn('Invalid rate from API, using default:', response);
                // Use default value 141000
                $('#exchange_rate').val(141000);
            }
        },
        error: function(xhr, status, error) {
            console.warn('Error loading USD rate from API:', error, '- Using default value 141000');
            // Use default value 141000 if API fails
            $('#exchange_rate').val(141000);
        }
    });
}

// Load USD to IQD exchange rate from API for edit modal
function loadEditUsdRate() {
    // API configuration
    const apiUrl = 'https://dinarapi.hediworks.site/api/get-price';
    const apiToken = 'S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
    const id = '8'; // 100 dollar ID
    
    $.ajax({
        url: `${apiUrl}?id=${id}&api_token=${apiToken}`,
        type: 'GET',
        dataType: 'json',
        timeout: 5000, // 5 seconds timeout
        success: function(response) {
            console.log('API Response for Edit:', response);
            
            // Check different possible response formats
            let rate = null;
            
            if (response.success && response.data && response.data.price) {
                rate = response.data.price;
            } else if (response.value) {
                rate = response.value;
            } else if (response.price) {
                rate = response.price;
            } else if (response.rate) {
                rate = response.rate;
            }
            
            if (rate && rate > 0) {
                // Only update if the field is empty or has default value
                const currentValue = $('#edit_exchange_rate').val();
                if (!currentValue || currentValue === '0' || currentValue === '141000') {
                    $('#edit_exchange_rate').val(rate);
                    console.log('USD rate loaded successfully from API for edit:', rate);
                    // Trigger calculations if needed
                    if (typeof updateAmountsFor === 'function') {
                        updateAmountsFor('edit_');
                    }
                }
            } else {
                console.warn('Invalid rate from API for edit, using default:', response);
                // Use default value 141000 only if field is empty
                const currentValue = $('#edit_exchange_rate').val();
                if (!currentValue || currentValue === '0') {
                    $('#edit_exchange_rate').val(141000);
                }
            }
        },
        error: function(xhr, status, error) {
            console.warn('Error loading USD rate from API for edit:', error, '- Using default value 141000');
            // Use default value 141000 if API fails and field is empty
            const currentValue = $('#edit_exchange_rate').val();
            if (!currentValue || currentValue === '0') {
                $('#edit_exchange_rate').val(141000);
            }
        }
    });
}

// Load drivers data when modal opens
$('#addPurchaseModal').on('shown.bs.modal', function() {
    loadDriversData();
    loadUsdRate(); // Load USD rate when modal opens
});

// Load USD rate when edit modal opens (only if field is empty)
$('#editPurchaseModal').on('shown.bs.modal', function() {
    loadEditUsdRate(); // Load USD rate when edit modal opens
});

// Listen for driver selection change
$(document).on('change', '#driver_id', function() {
    // Recalculate kg if total_weight already has a value
    if ($('#total_weight').val()) {
        calculateKgFromTotalWeight();
    }
});

// Listen for total_weight input
$(document).on('input', '#total_weight', function() {
    calculateKgFromTotalWeight();
});

// Load drivers data on page load
$(document).ready(function() {
    loadDriversData();
});
</script>
</body>
</html>
