<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
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
    
    <!-- SheetJS for Excel processing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
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
        
        /* Excel Import Modal Styling */
        .filter-icon {
            cursor: pointer;
            color: var(--seafoam-green);
            margin-left: 8px;
            transition: color 0.3s ease;
        }
        
        .filter-icon:hover {
            color: var(--kelly-green);
        }
        
        .filter-icon.active {
            color: var(--accent-orange);
        }
        
        .filter-dropdown {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 300px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .filter-dropdown-header {
            background: var(--kelly-green);
            color: white;
            padding: 12px 16px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .close-filter-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 18px;
        }
        
        .filter-dropdown-content {
            padding: 16px;
        }
        
        .filter-actions {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .filter-search {
            margin-bottom: 16px;
        }
        
        .filter-options {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .filter-option {
            display: flex;
            align-items: center;
            padding: 8px 0;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .filter-option:hover {
            background-color: #f8f9fa;
        }
        
        .filter-option input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .clear-all-filters-btn {
            margin-bottom: 16px;
        }
        
        .filter-info {
            font-size: 12px;
            color: #6c757d;
            margin-left: 8px;
        }
        
        /* Excel Import Specific Styles */
        .excel-import-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .template-download-section {
            background: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .import-progress {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .import-results {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        
        .progress-bar {
            height: 20px;
            border-radius: 10px;
        }
        
        .error-list {
            max-height: 200px;
            overflow-y: auto;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 12px;
        }
    </style>
    <!-- jQuery (پێش هەموو شت) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">کڕین</h2>
        <div class="d-flex gap-2">
            <button class="btn" data-bs-toggle="modal" data-bs-target="#driversManagementModal" style="background: var(--kelly-green); color:white; font-weight: bold;">
                <i class="fas fa-users me-1"></i>وردەکاری شۆفێرەکان
            </button>
            <button class="btn" data-bs-toggle="modal" data-bs-target="#excelImportModal" style="background: var(--accent-orange); color:white; font-weight: bold;">
                <i class="fas fa-file-excel me-1"></i>ئیمپۆرت کردن لە Excel
            </button>
            <?php if (hasPermission('add_purchase')): ?>
            <button class="btn" data-bs-toggle="modal" data-bs-target="#addPurchaseModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردنی کڕین</button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4" id="purchaseSummaryCards">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-danger card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-money-bill-wave card-icon"></i>
                    <h6 class="card-title">کۆی قەرزی ئێمە</h6>
                    <div class="fs-4 fw-bold" id="total-debt">$0</div>
                    <small class="text-light">کۆی قەرزی کۆمپانیاکان</small>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-building card-icon"></i>
                    <h6 class="card-title">کۆی ژمارەی کۆمپانیاکان</h6>
                    <div class="fs-4 fw-bold" id="total-companies">0</div>
                    <small class="text-light">ژمارەی هەموو کۆمپانیاکان</small>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-hand-holding-usd card-icon"></i>
                    <h6 class="card-title">کۆمپانیاکانی قەرزدار</h6>
                    <div class="fs-4 fw-bold" id="indebted-companies">0</div>
                    <small class="text-light">کۆمپانیاکانی قەرزدار</small>
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
    
    <!-- Additional Filters Row -->
    <div class="filter-section">
      <div class="row">
        <div class="col-md-3">
          <label for="filter_company">کۆمپانیا:</label>
          <select class="form-select" id="filter_company">
            <option value="">هەموو کۆمپانیاکان</option>
            <?php foreach ($companies as $comp): ?>
              <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_location">شوێن:</label>
          <select class="form-select" id="filter_location">
            <option value="">هەموو شوێنەکان</option>
            <?php foreach ($locations as $loc): ?>
              <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_driver">شۆفێر:</label>
          <select class="form-select" id="filter_driver">
            <option value="">هەموو شۆفێرەکان</option>
            <?php foreach ($drivers as $drv): ?>
              <option value="<?= $drv['id'] ?>"><?= htmlspecialchars($drv['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_material">مەواد:</label>
          <select class="form-select" id="filter_material">
            <option value="">هەموو مەوادەکان</option>
            <?php foreach ($materials as $mat): ?>
              <option value="<?= $mat['id'] ?>"><?= htmlspecialchars($mat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="purchaseTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>کۆمپانیا</th>
                    <th>شوێن</th>
                    <th>شۆفێر</th>
                    <th>ژمارەی پسوڵە</th>
                    <th>مەواد</th>
                    <th>بەروار</th>
                    <th>جۆری پارەدان</th>
                    <th>جۆری دراو</th>
                    <th>کیلۆگرام</th>
                    <th>نرخی یەک کیلۆ بە دۆلار</th>
                    <th>نرخی یەک کیلۆ بە دینار</th>
                    <th>نرخ</th>
                    <th>بڕی پارە بە دینار</th>
                    <th>نرخی 100 دۆلار بە دینار</th>
                    <th>پارەی دراو بە دۆلار</th>
                    <th>پارەی دراو بە دینار</th>
                    <th>پارەی ماوە بە دۆلار</th>
                    <th>پارەی ماوە بە دینار</th>
                    <th>چاو/سایلۆ</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Purchases will be loaded here by JS -->
            </tbody>
        </table>
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
              <select class="form-select" id="company_id" name="company_id" required>
                <option value="">کۆمپانیا</option>
                <?php foreach ($companies as $comp): ?>
                  <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="driver_id" class="form-label">شۆفێر</label>
              <div class="input-group">
                <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#addDriverModal" style="background: var(--seafoam-green); color: white; font-weight: bold;">+</button>
                <select class="form-select select2" id="driver_id" name="driver_id" required>
                  <option value="">شۆفێرەکان</option>
                  <?php foreach ($drivers as $drv): ?>
                    <option value="<?= $drv['id'] ?>"><?= htmlspecialchars($drv['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
      
          </div>
          <div class="row">
          <div class="col-md-6 mb-3">
              <label for="location_id" class="form-label">شوێن</label>
              <div class="input-group">
                <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#addLocationModal" style="background: var(--seafoam-green); color: white; font-weight: bold;">+</button>
                <select class="form-select select2" id="location_id" name="location_id" required>
                  <option value="">شوێن</option>
                  <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                  <?php endforeach; ?>
                </select>
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
              <select class="form-select" id="material_id" name="material_id" required>
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
              <label for="kg" class="form-label">چەند کیلۆ</label>
              <input type="number" class="form-control" id="kg" name="kg" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
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
              <input type="number" class="form-control" id="exchange_rate" name="exchange_rate" min="0" step="1" required>
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
        <div class="modal-header"><h5 class="modal-title">زیادکردنی شوێن</h5></div>
        <div class="modal-body">
          <input type="text" class="form-control" name="name" placeholder="ناوی شوێن" required>
        </div>
        <div class="modal-footer">
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
            <form id="addDriverForm">
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
        <div class="card">
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
      </div>
    </div>
  </div>
</div>

<!-- Edit Driver Modal -->
<div class="modal fade" id="editDriverModal" tabindex="-1" aria-labelledby="editDriverModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editDriverForm">
        <input type="hidden" id="edit_driver_id" name="id">
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
        <div class="modal-header"><h5 class="modal-title">زیادکردنی شۆفێر</h5></div>
        <div class="modal-body">
          <input type="text" class="form-control" name="name" placeholder="ناوی شۆفێر" required>
        </div>
        <div class="modal-footer">
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
              <select class="form-select" id="edit_company_id" name="company_id" required>
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
              <select class="form-select" id="edit_material_id" name="material_id" required>
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
              <input type="number" class="form-control" id="edit_exchange_rate" name="exchange_rate" min="0" step="1" required>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/comon/select2_script.js"></script>
<script>
    // Pass permissions to JavaScript
    window.userPermissions = {
      canAdd: <?php echo hasPermission('add_purchase') ? 'true' : 'false'; ?>,
      canEdit: <?php echo hasPermission('edit_purchase') ? 'true' : 'false'; ?>,
      canDelete: <?php echo hasPermission('delete_purchase') ? 'true' : 'false'; ?>
    };
</script>
<script src="../assets/js/purchase/add_purchase.js"></script>
<script src="../assets/js/purchase/select_purchase.js"></script>
<script src="../assets/js/purchase/summary.js"></script>
<script src="../assets/js/location_driver/driver.js"></script>
<script src="../assets/js/location_driver/location.js"></script>
<script src="../assets/js/purchase/delete_purchase.js"></script>
<script src="../assets/js/purchase/purchase.js"></script>
<script src="../assets/js/purchase/update_purchase.js"></script>
<script src="../assets/js/drivers/drivers_management.js"></script>

<!-- Excel Import Modal -->
<div class="modal fade" id="excelImportModal" tabindex="-1" aria-labelledby="excelImportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="excelImportModalLabel">
          <i class="fas fa-file-excel me-2"></i>ئیمپۆرت کردن لە Excel
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Template Download Section -->
        <div class="card mb-4 template-download-section">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="fas fa-download me-2"></i>داگرتنی نموونەی Excel
            </h6>
          </div>
          <div class="card-body">
            <p class="text-muted mb-3">سەرەتا نموونەی Excel داگرە و زانیارییەکان لەو شێوەیەدا دابنێ</p>
            <button type="button" class="btn btn-success" id="downloadTemplateBtn">
              <i class="fas fa-download me-2"></i>داگرتنی نموونە
            </button>
            <div class="mt-3">
              <h6>ستوونەکان:</h6>
              <ul class="list-unstyled">
                <li><strong>کۆمپانیا:</strong> ناوی کۆمپانیا</li>
                <li><strong>شوێن:</strong> ناوی شوێن</li>
                <li><strong>شۆفێر:</strong> ناوی شۆفێر</li>
                <li><strong>ژمارەی پسوڵە:</strong> ژمارەی پسوڵە</li>
                <li><strong>مەواد:</strong> ناوی مەواد</li>
                <li><strong>بەروار:</strong> بەروار (YYYY-MM-DD)</li>
                <li><strong>جۆری پارەدان:</strong> نەقد یان قەرز</li>
                <li><strong>جۆری دراو:</strong> دینار یان دۆلار</li>
                <li><strong>کیلۆگرام:</strong> بڕی کیلۆگرام</li>
                <li><strong>نرخی یەک کیلۆ بە دۆلار:</strong> نرخ بە دۆلار</li>
                <li><strong>نرخی یەک کیلۆ بە دینار:</strong> نرخ بە دینار</li>
                <li><strong>نرخ:</strong> کۆی نرخ</li>
                <li><strong>بڕی پارە بە دینار:</strong> بڕی پارە بە دینار</li>
                <li><strong>نرخی 100 دۆلار بە دینار:</strong> نرخی 100 دۆلار</li>
                <li><strong>پارەی دراو بە دۆلار:</strong> پارەی دراو بە دۆلار</li>
                <li><strong>پارەی دراو بە دینار:</strong> پارەی دراو بە دینار</li>
                <li><strong>چاو/سایلۆ:</strong> ناوی چاو یان سایلۆ</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- File Upload Section -->
        <div class="card excel-import-section">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="fas fa-upload me-2"></i>هەڵگرتنی فایل
            </h6>
          </div>
          <div class="card-body">
            <form id="excelImportForm" enctype="multipart/form-data">
              <div class="mb-3">
                <label for="excelFile" class="form-label">هەڵبژاردنی فایلی Excel:</label>
                <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".xlsx,.xls" required>
                <div class="form-text">تەنها فایلەکانی Excel (.xlsx, .xls) قبوڵ دەکرێت</div>
              </div>
              
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="skipFirstRow" checked>
                  <label class="form-check-label" for="skipFirstRow">
                    پەڕەی یەکەم بەکاردەهێنرێت (ناونیشانەکان)
                  </label>
                </div>
              </div>
              
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="validateData" checked>
                  <label class="form-check-label" for="validateData">
                    پشتڕاستکردنەوەی زانیارییەکان پێش ئیمپۆرت
                  </label>
                </div>
              </div>
              
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="importBtn">
                  <i class="fas fa-upload me-2"></i>ئیمپۆرت کردن
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  <i class="fas fa-times me-2"></i>داخستن
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Import Progress Section -->
        <div id="importProgress" class="import-progress mt-4" style="display: none;">
          <div class="progress mb-3">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
          </div>
          <div class="text-center">
            <span id="progressText">0%</span>
          </div>
        </div>

        <!-- Import Results Section -->
        <div id="importResults" class="import-results mt-4" style="display: none;">
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">ئەنجامی ئیمپۆرت</h6>
            </div>
            <div class="card-body">
              <div id="importSummary"></div>
              <div id="importErrors" class="mt-3" style="display: none;">
                <h6 class="text-danger">هەڵەکان:</h6>
                <div id="errorList" class="error-list"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Add modal: dynamic price per kg fields
$(function() {
    function handleAddTypeChange() {
        togglePricePerKgInputsFor('#type', '#pricePerKgIqdGroup', '#pricePerKgUsdGroup');
    }
    $('#type').on('change', handleAddTypeChange);
    handleAddTypeChange();
});
$('#kg, #price_per_kg_iqd, #price_per_kg_usd, #type, #price, #amount_iqd, #paid_usd, #paid_iqd, #exchange_rate').on('input change', function() {
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
$('#edit_kg, #edit_price_per_kg_iqd, #edit_price_per_kg_usd, #edit_type, #edit_price, #edit_amount_iqd, #edit_paid_usd, #edit_paid_iqd, #edit_exchange_rate').on('input change', function() {
    updateAmountsFor('edit_');
});
$('#editPurchaseModal').on('shown.bs.modal', function() {
    updateAmountsFor('edit_');
});

// Filter functionality for company, location, driver, and material
$(document).ready(function() {
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
        applyFilters();
    });
    
    // Function to apply all filters
    function applyFilters() {
        const companyId = $('#filter_company').val();
        const locationId = $('#filter_location').val();
        const driverId = $('#filter_driver').val();
        const materialId = $('#filter_material').val();
        const fromDate = $('#filter_from').val();
        const toDate = $('#filter_to').val();
        
        // Build filter parameters
        const params = new URLSearchParams();
        if (companyId) params.append('company_id', companyId);
        if (locationId) params.append('location_id', locationId);
        if (driverId) params.append('driver_id', driverId);
        if (materialId) params.append('material_id', materialId);
        if (fromDate) params.append('from', fromDate);
        if (toDate) params.append('to', toDate);
        
        // Call the existing loadPurchases function with filters
        if (typeof loadPurchases === 'function') {
            loadPurchases(params.toString());
        }
        
        // Also update summary cards if the function exists
        if (typeof loadPurchaseSummary === 'function') {
            loadPurchaseSummary(params.toString());
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
    
    // Handle column filter changes
    document.addEventListener('tableFiltersChanged', function(event) {
        const tableSelector = event.detail.tableSelector;
        if (tableSelector === '#purchaseTable') {
            // Refresh the table data while maintaining column filters
            setTimeout(() => {
                if (typeof refreshTableWithFilters === 'function') {
                    refreshTableWithFilters();
                }
            }, 100);
        }
    });
});

// Excel Import Functionality
$(document).ready(function() {
    // Download template
    $('#downloadTemplateBtn').on('click', function() {
        downloadExcelTemplate();
    });
    
    // Handle form submission
    $('#excelImportForm').on('submit', function(e) {
        e.preventDefault();
        importExcelFile();
    });
    
    // Download Excel template
    function downloadExcelTemplate() {
        const templateData = [
            ['کۆمپانیا', 'شوێن', 'شۆفێر', 'ژمارەی پسوڵە', 'مەواد', 'بەروار', 'جۆری پارەدان', 'جۆری دراو', 'کیلۆگرام', 'نرخی یەک کیلۆ بە دۆلار', 'نرخی یەک کیلۆ بە دینار', 'نرخ', 'بڕی پارە بە دینار', 'نرخی 100 دۆلار بە دینار', 'پارەی دراو بە دۆلار', 'پارەی دراو بە دینار', 'چاو/سایلۆ'],
            ['کۆمپانیای نموونە', 'شوێنی نموونە', 'شۆفێری نموونە', 'INV-001', 'مەوادی نموونە', '2024-01-15', 'قەرز', 'دۆلار', '1000', '50.00', '0', '50000', '0', '150000', '25000', '0', 'چاوی نموونە']
        ];
        
        // Create workbook and worksheet
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(templateData);
        
        // Set column widths
        const colWidths = [
            { wch: 15 }, { wch: 15 }, { wch: 15 }, { wch: 15 }, { wch: 15 },
            { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 20 },
            { wch: 20 }, { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
            { wch: 20 }, { wch: 15 }
        ];
        ws['!cols'] = colWidths;
        
        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(wb, ws, 'کڕین');
        
        // Download file
        XLSX.writeFile(wb, 'نموونەی_کڕین.xlsx');
    }
    
    // Import Excel file
    function importExcelFile() {
        const fileInput = document.getElementById('excelFile');
        const file = fileInput.files[0];
        
        if (!file) {
            Swal.fire('هەڵە', 'تکایە فایلێک هەڵبژێرە', 'error');
            return;
        }
        
        // Validate file type
        const allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel'
        ];
        
        if (!allowedTypes.includes(file.type)) {
            Swal.fire('هەڵە', 'تەنها فایلەکانی Excel قبوڵ دەکرێت', 'error');
            return;
        }
        
        // Show progress
        showImportProgress();
        
        // Read file
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const worksheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                
                // Process data
                processExcelData(jsonData);
            } catch (error) {
                console.error('Error reading Excel file:', error);
                hideImportProgress();
                Swal.fire('هەڵە', 'هەڵەیەک لە خوێندنەوەی فایلەکە هەیە', 'error');
            }
        };
        
        reader.readAsArrayBuffer(file);
    }
    
    // Process Excel data
    function processExcelData(data) {
        const skipFirstRow = $('#skipFirstRow').is(':checked');
        const validateData = $('#validateData').is(':checked');
        
        let startIndex = skipFirstRow ? 1 : 0;
        let processedData = [];
        let errors = [];
        
        // Process each row
        for (let i = startIndex; i < data.length; i++) {
            const row = data[i];
            if (row && row.some(cell => cell !== null && cell !== undefined && cell !== '')) {
                try {
                    const processedRow = processRow(row, i + 1, validateData);
                    if (processedRow.success) {
                        processedData.push(processedRow.data);
                    } else {
                        errors.push(processedRow.error);
                    }
                } catch (error) {
                    errors.push(`هەڵە لە ڕیز ${i + 1}: ${error.message}`);
                }
            }
        }
        
        // Show results
        showImportResults(processedData, errors);
        
        // Import data if no errors or if user wants to continue
        if (errors.length === 0 || confirm('هەندێک هەڵە هەیە، دەتەوێت بەردەوام بیت؟')) {
            importProcessedData(processedData);
        }
    }
    
    // Process individual row
    function processRow(row, rowNumber, validateData) {
        try {
            // Map columns to data
            const mappedData = {
                company_name: row[0] || '',
                location_name: row[1] || '',
                driver_name: row[2] || '',
                invoice_number: row[3] || '',
                material_name: row[4] || '',
                date: row[5] || '',
                payment_type: row[6] || '',
                type: row[7] || '',
                kg: parseFloat(row[8]) || 0,
                price_per_kg_usd: parseFloat(row[9]) || 0,
                price_per_kg_iqd: parseFloat(row[10]) || 0,
                price: parseFloat(row[11]) || 0,
                amount_iqd: parseFloat(row[12]) || 0,
                exchange_rate: parseFloat(row[13]) || 0,
                paid_usd: parseFloat(row[14]) || 0,
                paid_iqd: parseFloat(row[15]) || 0,
                bin_name: row[16] || ''
            };
            
            // Validate data if required
            if (validateData) {
                const validation = validateRowData(mappedData, rowNumber);
                if (!validation.valid) {
                    return { success: false, error: validation.error };
                }
            }
            
            return { success: true, data: mappedData };
        } catch (error) {
            return { success: false, error: `هەڵە لە ڕیز ${rowNumber}: ${error.message}` };
        }
    }
    
    // Validate row data
    function validateRowData(data, rowNumber) {
        const errors = [];
        
        if (!data.company_name) errors.push('ناوی کۆمپانیا پێویستە');
        if (!data.location_name) errors.push('ناوی شوێن پێویستە');
        if (!data.driver_name) errors.push('ناوی شۆفێر پێویستە');
        if (!data.invoice_number) errors.push('ژمارەی پسوڵە پێویستە');
        if (!data.material_name) errors.push('ناوی مەواد پێویستە');
        if (!data.date) errors.push('بەروار پێویستە');
        if (!data.payment_type) errors.push('جۆری پارەدان پێویستە');
        if (!data.type) errors.push('جۆری دراو پێویستە');
        if (data.kg <= 0) errors.push('کیلۆگرام دەبێت لە سفر زیاتر بێت');
        if (data.price <= 0) errors.push('نرخ دەبێت لە سفر زیاتر بێت');
        
        // Validate date format
        if (data.date && !/^\d{4}-\d{2}-\d{2}$/.test(data.date)) {
            errors.push('فۆرماتی بەروار دەبێت YYYY-MM-DD بێت');
        }
        
        if (errors.length > 0) {
            return {
                valid: false,
                error: `ڕیز ${rowNumber}: ${errors.join(', ')}`
            };
        }
        
        return { valid: true };
    }
    
    // Show import progress
    function showImportProgress() {
        $('#importProgress').show();
        $('#importResults').hide();
        updateProgress(0);
    }
    
    // Hide import progress
    function hideImportProgress() {
        $('#importProgress').hide();
    }
    
    // Update progress bar
    function updateProgress(percentage) {
        $('.progress-bar').css('width', percentage + '%');
        $('#progressText').text(percentage + '%');
    }
    
    // Show import results
    function showImportResults(data, errors) {
        hideImportProgress();
        $('#importResults').show();
        
        const summary = `
            <div class="alert alert-info">
                <strong>کۆی ڕیزەکان:</strong> ${data.length}<br>
                <strong>هەڵەکان:</strong> ${errors.length}
            </div>
        `;
        
        $('#importSummary').html(summary);
        
        if (errors.length > 0) {
            $('#importErrors').show();
            const errorList = errors.map(error => `<div class="text-danger">• ${error}</div>`).join('');
            $('#errorList').html(errorList);
        } else {
            $('#importErrors').hide();
        }
    }
    
    // Import processed data
    function importProcessedData(data) {
        if (data.length === 0) {
            Swal.fire('هەڵە', 'هیچ داتایەک بۆ ئیمپۆرت نەدۆزرایەوە', 'error');
            return;
        }
        
        // Show confirmation
        Swal.fire({
            title: 'دڵنیای لە ئیمپۆرت؟',
            text: `${data.length} ڕیز بەردەستە بۆ ئیمپۆرت`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، ئیمپۆرت بکە',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                performImport(data);
            }
        });
    }
    
    // Perform actual import
    function performImport(data) {
        showImportProgress();
        
        // Make API call to backend with JSON data
        fetch('../process/purchase/import_excel.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            hideImportProgress();
            
            if (result.success) {
                // Show success message
                Swal.fire({
                    title: 'سەرکەوتوو!',
                    text: `${result.data.imported_count} ڕیز بە سەرکەوتوویی ئیمپۆرت کرا`,
                    icon: 'success'
                }).then(() => {
                    // Refresh table
                    if (typeof loadPurchases === 'function') {
                        loadPurchases();
                    }
                    if (typeof loadPurchaseSummary === 'function') {
                        loadPurchaseSummary();
                    }
                    
                    // Close modal
                    $('#excelImportModal').modal('hide');
                    
                    // Reset form
                    $('#excelImportForm')[0].reset();
                    $('#importResults').hide();
                });
            } else {
                // Show error message
                Swal.fire('هەڵە', result.error || 'هەڵەیەک لە ئیمپۆرت هەیە', 'error');
            }
        })
        .catch(error => {
            hideImportProgress();
            console.error('Import error:', error);
            Swal.fire('هەڵە', 'هەڵەیەک لە ئیمپۆرت هەیە', 'error');
        });
    }
});
</script>
</body>
</html>
