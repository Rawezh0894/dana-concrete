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
   
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        /* Multiple choice filter styling */
        .select2-multiple {
            width: 100%;
        }
        
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            min-height: 38px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--seafoam-green);
            color: white;
            border: none;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            margin: 0.125rem;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 0.25rem;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #f8f9fa;
        }
        
        .select2-container--default .select2-search--inline .select2-search__field {
            margin-top: 0.25rem;
        }
        
        /* Filter labels styling */
        .filter-label {
            font-weight: 600;
            color: var(--kelly-green);
            margin-bottom: 0.5rem;
        }
        
        /* Filter counter styling */
        .filter-counter {
            background-color: var(--seafoam-green);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        
        /* Filter row styling */
        .filter-row {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
        }
        
        /* Active filter indicator */
        .filter-active {
            border-color: var(--seafoam-green);
            background-color: #e8f5e8;
        }
        
        /* Filter section title */
        .filter-section-title {
            color: var(--kelly-green);
            font-weight: 600;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 1.1rem;
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
    <div class="row mb-3">
      <div class="col-md-3">
        <label>لە بەروار:</label>
        <input type="date" id="filter_from" class="form-control">
      </div>
      <div class="col-md-3">
        <label>بۆ بەروار:</label>
        <input type="date" id="filter_to" class="form-control">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-secondary" id="clearFilterBtn" type="button">پاککردنەوە</button>
      </div>
    </div>
    
    <!-- Multiple Choice Filters -->
    <div class="filter-row" id="filterRow">
      <div class="filter-section-title">
        <i class="fas fa-filter me-2"></i>فلتەرکردن
      </div>
      <div class="row">
        <div class="col-md-3">
          <label for="filter_company" class="filter-label">
            کۆمپانیا
            <span class="filter-counter" id="companyCounter" style="display: none;">0</span>
          </label>
          <select class="form-select select2-multiple" id="filter_company" multiple data-placeholder="هەموو کۆمپانیاکان">
            <?php foreach ($companies as $comp): ?>
              <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_location" class="filter-label">
            شوێن
            <span class="filter-counter" id="locationCounter" style="display: none;">0</span>
          </label>
          <select class="form-select select2-multiple" id="filter_location" multiple data-placeholder="هەموو شوێنەکان">
            <?php foreach ($locations as $loc): ?>
              <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_driver" class="filter-label">
            شۆفێر
            <span class="filter-counter" id="driverCounter" style="display: none;">0</span>
          </label>
          <select class="form-select select2-multiple" id="filter_driver" multiple data-placeholder="هەموو شۆفێرەکان">
            <?php foreach ($drivers as $drv): ?>
              <option value="<?= $drv['id'] ?>"><?= htmlspecialchars($drv['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_material" class="filter-label">
            مەواد
            <span class="filter-counter" id="materialCounter" style="display: none;">0</span>
          </label>
          <select class="form-select select2-multiple" id="filter_material" multiple data-placeholder="هەموو مەوادەکان">
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
<script>
// Initialize multiple choice filters
$(document).ready(function() {
    // Initialize select2 for multiple choice filters
    $('.select2-multiple').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder');
        },
        allowClear: true,
        language: 'ku'
    });
    
    // Handle filter changes
    $('.select2-multiple').on('change', function() {
        applyFilters();
    });
    
    // Handle date filter changes
    $('#filter_from, #filter_to').on('change', function() {
        applyFilters();
    });
    
    // Clear all filters
    $('#clearFilterBtn').on('click', function() {
        clearAllFilters();
    });
});

// Function to apply all filters
function applyFilters() {
    const filters = {
        from: $('#filter_from').val(),
        to: $('#filter_to').val(),
        companies: $('#filter_company').val() || [],
        locations: $('#filter_location').val() || [],
        drivers: $('#filter_driver').val() || [],
        materials: $('#filter_material').val() || []
    };
    
    // Update filter counters
    updateFilterCounters(filters);
    
    // Update filter row styling
    updateFilterRowStyling(filters);
    
    // Store filters in localStorage for persistence
    localStorage.setItem('purchaseFilters', JSON.stringify(filters));
    
    // Reload purchase data with filters
    if (typeof loadPurchaseData === 'function') {
        loadPurchaseData(filters);
    }
}

// Function to update filter counters
function updateFilterCounters(filters) {
    // Update company counter
    const companyCount = filters.companies.length;
    const companyCounter = $('#companyCounter');
    if (companyCount > 0) {
        companyCounter.text(companyCount).show();
    } else {
        companyCounter.hide();
    }
    
    // Update location counter
    const locationCount = filters.locations.length;
    const locationCounter = $('#locationCounter');
    if (locationCount > 0) {
        locationCounter.text(locationCount).show();
    } else {
        locationCounter.hide();
    }
    
    // Update driver counter
    const driverCount = filters.drivers.length;
    const driverCounter = $('#driverCounter');
    if (driverCount > 0) {
        driverCounter.text(driverCount).show();
    } else {
        driverCounter.hide();
    }
    
    // Update material counter
    const materialCount = filters.materials.length;
    const materialCounter = $('#materialCounter');
    if (materialCount > 0) {
        materialCounter.text(materialCount).show();
    } else {
        materialCounter.hide();
    }
}

// Function to update filter row styling
function updateFilterRowStyling(filters) {
    const filterRow = $('#filterRow');
    const hasActiveFilters = filters.from || filters.to || 
                           filters.companies.length > 0 || 
                           filters.locations.length > 0 || 
                           filters.drivers.length > 0 || 
                           filters.materials.length > 0;
    
    if (hasActiveFilters) {
        filterRow.addClass('filter-active');
        showActiveFiltersSummary(filters);
    } else {
        filterRow.removeClass('filter-active');
        hideActiveFiltersSummary();
    }
}

// Function to show active filters summary
function showActiveFiltersSummary(filters) {
    let summary = [];
    
    if (filters.from && filters.to) {
        summary.push(`بەروار: ${filters.from} - ${filters.to}`);
    } else if (filters.from) {
        summary.push(`لە بەروار: ${filters.from}`);
    } else if (filters.to) {
        summary.push(`بۆ بەروار: ${filters.to}`);
    }
    
    if (filters.companies.length > 0) {
        const companyNames = filters.companies.map(id => {
            const option = $(`#filter_company option[value="${id}"]`);
            return option.text();
        });
        summary.push(`کۆمپانیا: ${companyNames.join(', ')}`);
    }
    
    if (filters.locations.length > 0) {
        const locationNames = filters.locations.map(id => {
            const option = $(`#filter_location option[value="${id}"]`);
            return option.text();
        });
        summary.push(`شوێن: ${locationNames.join(', ')}`);
    }
    
    if (filters.drivers.length > 0) {
        const driverNames = filters.drivers.map(id => {
            const option = $(`#filter_driver option[value="${id}"]`);
            return option.text();
        });
        summary.push(`شۆفێر: ${driverNames.join(', ')}`);
    }
    
    if (filters.materials.length > 0) {
        const materialNames = filters.materials.map(id => {
            const option = $(`#filter_material option[value="${id}"]`);
            return option.text();
        });
        summary.push(`مەواد: ${materialNames.join(', ')}`);
    }
    
    // Show summary below the filter row
    let summaryHtml = '<div class="alert alert-info mt-2 mb-3" id="activeFiltersSummary">';
    summaryHtml += '<i class="fas fa-info-circle me-2"></i><strong>فلتەرە چالاکەکان:</strong> ';
    summaryHtml += summary.join(' | ');
    summaryHtml += '</div>';
    
    // Remove existing summary if any
    $('#activeFiltersSummary').remove();
    
    // Add new summary after filter row
    $('#filterRow').after(summaryHtml);
}

// Function to hide active filters summary
function hideActiveFiltersSummary() {
    $('#activeFiltersSummary').remove();
}

// Function to clear all filters
function clearAllFilters() {
    // Clear date filters
    $('#filter_from').val('');
    $('#filter_to').val('');
    
    // Clear multiple choice filters
    $('.select2-multiple').val(null).trigger('change');
    
    // Clear localStorage
    localStorage.removeItem('purchaseFilters');
    
    // Reload data without filters
    if (typeof loadPurchaseData === 'function') {
        loadPurchaseData({});
    }
}

// Function to load saved filters on page load
function loadSavedFilters() {
    const savedFilters = localStorage.getItem('purchaseFilters');
    if (savedFilters) {
        try {
            const filters = JSON.parse(savedFilters);
            
            // Apply saved filters to form
            if (filters.from) $('#filter_from').val(filters.from);
            if (filters.to) $('#filter_to').val(filters.to);
            if (filters.companies && filters.companies.length > 0) {
                $('#filter_company').val(filters.companies).trigger('change');
            }
            if (filters.locations && filters.locations.length > 0) {
                $('#filter_location').val(filters.locations).trigger('change');
            }
            if (filters.drivers && filters.drivers.length > 0) {
                $('#filter_driver').val(filters.drivers).trigger('change');
            }
            if (filters.materials && filters.materials.length > 0) {
                $('#filter_material').val(filters.materials).trigger('change');
            }
            
            // Apply filters to data
            applyFilters();
        } catch (e) {
            console.error('Error loading saved filters:', e);
            localStorage.removeItem('purchaseFilters');
        }
    }
}

// Load saved filters when page loads
$(document).ready(function() {
    loadSavedFilters();
});

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
</script>
</body>
</html>
