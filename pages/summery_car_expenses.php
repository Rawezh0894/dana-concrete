<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: ../index.php');
  exit;
}

// Check if user has permission to view car expenses summary
if (!hasPermission('view_other_expenses')) {
  echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
    . '<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
    . '<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
    . '</div>';
  exit;
}

// Get filter data
$cars = $pdo->query("SELECT id, name FROM cars ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$employees = $pdo->query("SELECT id, name FROM employees ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$expense_types = [
    'بەکارهێنانی کاڵای کۆگا' => 'بەکارهێنانی کاڵای کۆگا',
    'بەکارهێنانی گاز' => 'بەکارهێنانی گاز',
    'خەرجی تر' => 'خەرجی تر',
    'خواردنگە' => 'خواردنگە',
    'ئۆفیس' => 'ئۆفیس'
];
?>
<!DOCTYPE html>
<html lang="ku">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>پوختەی خەرجیەکانی سەیارەکان</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="../assets/css/login.css" rel="stylesheet">
  <link href="../assets/css/variables.css" rel="stylesheet">
  <link href="../assets/css/nav.css" rel="stylesheet">
  <link href="../assets/css/comon/table.css" rel="stylesheet">
  <link href="../assets/css/comon/style.css" rel="stylesheet">
  <link href="../assets/css/comon/cards.css" rel="stylesheet" />
  <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
  <link href="../assets/css/summery_car_expenses.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body dir="rtl">
  <?php include '../includes/navbar.php'; ?>
  <?php include '../includes/sidebar.php'; ?>
  
  <div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">
        <i class="fas fa-car me-2"></i>پوختەی خەرجیەکانی سەیارەکان
      </h2>
      
      <div class="d-flex gap-2">
        <a href="cars_expenses.php" class="btn btn-secondary">
          <i class="fas fa-arrow-right me-1"></i>گەڕانەوە بۆ خەرجی سەیارەکان
        </a>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section no-print">
      <h5 class="mb-3 text-primary">
        <i class="fas fa-filter me-2"></i>فلتەرەکان
      </h5>
      
      <div class="row g-3">
        <div class="col-md-2">
          <label for="filter_car_id" class="form-label">سەیارە:</label>
          <select class="form-select" id="filter_car_id">
            <option value="">هەموو سەیارەکان</option>
            <?php foreach ($cars as $car): ?>
              <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="col-md-2">
          <label for="filter_employee_id" class="form-label">کارمەند:</label>
          <select class="form-select" id="filter_employee_id">
            <option value="">هەموو کارمەندەکان</option>
            <?php foreach ($employees as $emp): ?>
              <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="col-md-2">
          <label for="filter_expense_type" class="form-label">جۆری خەرجی:</label>
          <select class="form-select" id="filter_expense_type">
            <option value="">هەموو جۆرەکان</option>
            <?php foreach ($expense_types as $key => $value): ?>
              <option value="<?= $key ?>"><?= htmlspecialchars($value) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="col-md-2">
          <label for="filter_payment_type" class="form-label">جۆری پارەدان:</label>
          <select class="form-select" id="filter_payment_type">
            <option value="">هەموو جۆرەکان</option>
            <option value="نەقد">نەقد</option>
            <option value="قەرز">قەرز</option>
          </select>
        </div>
        
        <div class="col-md-2">
          <label for="filter_from_date" class="form-label">لە بەروار:</label>
          <input type="date" class="form-control" id="filter_from_date">
        </div>
        
        <div class="col-md-2">
          <label for="filter_to_date" class="form-label">بۆ بەروار:</label>
          <input type="date" class="form-control" id="filter_to_date">
        </div>
      </div>
      
      <div class="row mt-3">
        <div class="col-12">
          <button type="button" class="btn btn-primary me-2" onclick="applyFilters()">
            <i class="fas fa-search me-1"></i>جێبەجێکردنی فلتەر
          </button>
          <button type="button" class="btn btn-secondary me-2" onclick="clearFilters()">
            <i class="fas fa-eraser me-1"></i>پاککردنەوە
          </button>
          <button type="button" class="btn btn-info" onclick="generateReport()">
            <i class="fas fa-chart-bar me-1"></i>درووستکردنی ڕاپۆرت
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Statistics -->
    <div class="stats-grid" id="summary-stats">
      <div class="card summary-card text-center shadow border-0" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
        <div class="card-body">
          <i class="fas fa-dollar-sign fa-2x mb-2"></i>
          <h6 class="card-title">کۆی گشتی خەرجی بە دۆلار</h6>
          <div class="fs-3 fw-bold" id="total_usd">$0.00</div>
          <small>کۆی خەرجی بە دۆلار</small>
        </div>
      </div>
      
      <div class="card summary-card text-center shadow border-0" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white;">
        <div class="card-body">
          <i class="fas fa-coins fa-2x mb-2"></i>
          <h6 class="card-title">کۆی گشتی خەرجی بە دینار</h6>
          <div class="fs-3 fw-bold" id="total_iqd">0 د.ع</div>
          <small>کۆی خەرجی بە دینار</small>
        </div>
      </div>
      
      <div class="card summary-card text-center shadow border-0" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%); color: white;">
        <div class="card-body">
          <i class="fas fa-car fa-2x mb-2"></i>
          <h6 class="card-title">ژمارەی سەیارەکان</h6>
          <div class="fs-3 fw-bold" id="total_cars">0</div>
          <small>ژمارەی سەیارەکان</small>
        </div>
      </div>
      
      <div class="card summary-card text-center shadow border-0" style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); color: white;">
        <div class="card-body">
          <i class="fas fa-receipt fa-2x mb-2"></i>
          <h6 class="card-title">ژمارەی خەرجیەکان</h6>
          <div class="fs-3 fw-bold" id="total_expenses">0</div>
          <small>ژمارەی خەرجیەکان</small>
        </div>
      </div>
      
      <div class="card summary-card text-center shadow border-0" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white;">
        <div class="card-body">
          <i class="fas fa-gas-pump fa-2x mb-2"></i>
          <h6 class="card-title">کۆی گاز بەکارهاتوو</h6>
          <div class="fs-3 fw-bold" id="total_gas">0 لیتر</div>
          <small>کۆی گاز بەکارهاتوو</small>
        </div>
      </div>
      
      <div class="card summary-card text-center shadow border-0" style="background: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%); color: white;">
        <div class="card-body">
          <i class="fas fa-boxes fa-2x mb-2"></i>
          <h6 class="card-title">کۆی کاڵای بەکارهاتوو</h6>
          <div class="fs-3 fw-bold" id="total_materials">0</div>
          <small>کۆی کاڵای بەکارهاتوو</small>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="row no-print">
      <div class="col-md-6">
        <div class="chart-container">
          <h5 class="mb-3 text-primary">
            <i class="fas fa-chart-pie me-2"></i>دابەشبوونی خەرجیەکان بە جۆر
          </h5>
          <div class="chart-wrapper">
            <canvas id="expenseTypeChart"></canvas>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="chart-container">
          <h5 class="mb-3 text-primary">
            <i class="fas fa-chart-bar me-2"></i>خەرجیەکان بە سەیارە
          </h5>
          <div class="chart-wrapper">
            <canvas id="carExpenseChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Table -->
    <div class="table-container">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 text-primary">
          <i class="fas fa-table me-2"></i>تەفەسڵی خەرجیەکان
        </h5>
        <div class="d-flex gap-2">
          <span class="badge bg-primary" id="total-records">0 خەرجی</span>
        </div>
      </div>
      
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="expensesTable">
          <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
            <tr>
              <th>#</th>
              <th>بەروار</th>
              <th>سەیارە</th>
              <th>کارمەند</th>
              <th>جۆری خەرجی</th>
              <th>مەبەست</th>
              <th>بڕی گاز (لیتر)</th>
              <th>بڕی کاڵا</th>
              <th>بڕی دینار</th>
              <th>بڕی دۆلار</th>
              <th>جۆری پارەدان</th>
              <th>ژمارەی فاکتۆر</th>
              <th>بەرواری درووستکردن</th>
              <th>کردارەکان</th>
            </tr>
          </thead>
          <tbody id="expensesTableBody">
            <!-- Data will be loaded here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Loading Modal -->
  <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center py-4">
          <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <h6 class="text-primary">چاوەڕوان بە...</h6>
          <p class="text-muted mb-0">داتاکان بەردەست دەکرێت</p>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/summery_car_expenses/get_informations.js"></script>
  <script src="../assets/js/summery_car_expenses/filter.js"></script>
  
  <script>
    // Initialize Select2
    $(document).ready(function() {
      $('#filter_car_id, #filter_employee_id, #filter_expense_type, #filter_payment_type').select2({
        theme: 'bootstrap-5',
        width: '100%'
      });
      
      // Set default dates
      const today = new Date();
      const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
      $('#filter_from_date').val(firstDay.toISOString().split('T')[0]);
      $('#filter_to_date').val(today.toISOString().split('T')[0]);
      
      // Load initial data
      loadExpensesData();
    });
    
    function showLoading() {
      $('#loadingModal').modal('show');
    }
    
    function hideLoading() {
      $('#loadingModal').modal('hide');
    }
    

  </script>
</body>
</html>
