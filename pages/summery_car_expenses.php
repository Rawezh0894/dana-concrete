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
if (!hasPermission('view_car_expenses_summary')) {
  echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
    . '<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
    . '<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
    . '</div>';
  exit;
}

$cars = $pdo->query("SELECT id, name FROM cars")->fetchAll(PDO::FETCH_ASSOC);
$employees = $pdo->query("SELECT id, name FROM employees")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>پوختەی خەرجی سەیارەکان</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="../assets/css/login.css" rel="stylesheet">
  <link href="../assets/css/variables.css" rel="stylesheet">
  <link href="../assets/css/nav.css" rel="stylesheet">
  <link href="../assets/css/comon/table.css" rel="stylesheet">
  <link href="../assets/css/comon/style.css" rel="stylesheet">
  <link href="../assets/css/comon/cards.css" rel="stylesheet" />
  <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="../assets/css/summery_car_expenses.css" rel="stylesheet">

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  
</head>

<body dir="rtl">
  <?php include '../includes/navbar.php'; ?>
  <?php include '../includes/sidebar.php'; ?>
  
  <div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
  
      <div class="d-flex gap-2">
        <a href="other_expenses.php" class="btn btn-secondary">
          <i class="fas fa-arrow-right me-1"></i>گەڕانەوە بۆ خەرجی سەیارەکان
        </a>
      </div>
    </div>

    <!-- Filter Row -->
    <div class="row g-2 mb-3 no-print">
      <div class="col-md-3">
        <select class="form-select" id="filter_car_id">
          <option value="">سەیارە: هەموو</option>
          <?php foreach ($cars as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select" id="filter_employee_id">
          <option value="">کارمەند: هەموو</option>
          <?php foreach ($employees as $e): ?>
            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filter_date_from" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" placeholder="لە بەرواری">
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filter_date_to" value="<?= date('Y-m-d') ?>" placeholder="بۆ بەرواری">
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="button" class="btn btn-sm btn-primary filter-btn" id="filter_today" data-filter="today">
          <i class="fas fa-calendar-day me-1"></i>ئەمڕۆ
        </button>
        <button type="button" class="btn btn-sm btn-warning filter-btn" id="filter_yesterday" data-filter="yesterday">
          <i class="fas fa-calendar-minus me-1"></i>دوێنێ
        </button>
        <button type="button" class="btn btn-sm btn-secondary filter-btn" id="filter_reset" data-filter="reset">
          <i class="fas fa-redo me-1"></i>ڕیفڕێش
        </button>
      </div>
    </div>
    
    <!-- Debug Info -->
    <div class="alert alert-info no-print" role="alert">
      <i class="fas fa-info-circle me-2"></i>
      <strong>تێبینی:</strong> بۆ دەست گەیشتن بە داتای خەرجی سەیارەکان، دەبێت بەرواری هەڵبژێریت. بەردەستبوونی داتا بەندە بە بەرواری تۆمارکردنی خەرجیەکان.
      <div class="mt-2">
        <button type="button" class="btn btn-sm btn-outline-info" id="test_all_data">
          <i class="fas fa-bug me-1"></i>تاقیکردنەوەی هەموو داتا
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="show_debug_info">
          <i class="fas fa-code me-1"></i>زانیاری دیباگ
        </button>
      </div>
    </div>
    
    <!-- Debug Summary -->
    <div class="alert alert-secondary no-print" id="debug_summary" style="display: none;" role="alert">
      <h6><i class="fas fa-code me-2"></i>زانیاری دیباگ</h6>
      <div id="debug_content">
        <!-- Debug information will be loaded here -->
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4 no-print" id="summary-cards">
      <div class="col-md-3 mb-3">
        <div class="card text-center shadow card-gradient-info card-animate-hover">
          <div class="card-body">
            <i class="fas fa-car card-icon"></i>
            <h6 class="card-title">کۆی گشتی سەیارەکان</h6>
            <div class="fs-4 fw-bold" id="total_cars">0</div>
            <small class="text-light">ژمارەی سەیارەکان</small>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-center shadow card-gradient-success card-animate-hover">
          <div class="card-body">
            <i class="fas fa-gas-pump card-icon"></i>
            <h6 class="card-title">کۆی گشتی خەرجی گاز</h6>
            <div class="fs-4 fw-bold" id="total_gas_expenses">0 د.ع</div>
            <small class="text-light">کۆی خەرجی گاز</small>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-center shadow card-gradient-warning card-animate-hover">
          <div class="card-body">
            <i class="fas fa-boxes card-icon"></i>
            <h6 class="card-title">کۆی گشتی خەرجی کاڵا</h6>
            <div class="fs-4 fw-bold" id="total_material_expenses">0 د.ع</div>
            <small class="text-light">کۆی خەرجی کاڵا</small>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-center shadow card-gradient-danger card-animate-hover">
          <div class="card-body">
            <i class="fas fa-dollar-sign card-icon"></i>
            <h6 class="card-title">کۆی گشتی خەرجی</h6>
            <div class="fs-4 fw-bold" id="total_expenses">0 د.ع</div>
            <small class="text-light">کۆی هەموو خەرجیەکان</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Car Summary Table -->
    <div class="card no-print">
      <div class="card-header">
        <h5 class="mb-0">پوختەی خەرجی سەیارەکان</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover" id="carSummaryTable">
            <thead style="background: var(--kelly-green); color: white;">
              <tr>
                <th>#</th>
                <th>ناوی سەیارە</th>
                <th>ژمارەی خەرجیەکان</th>
                <th>کۆی خەرجی گاز</th>
                <th>کۆی خەرجی کاڵا</th>
                <th>کۆی گشتی خەرجی</th>
                <th>دۆخی پارەدان</th>
                <th>کردارەکان</th>
              </tr>
            </thead>
            <tbody>
              <!-- Data will be loaded here -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Car Details Modal -->
    <div class="modal fade" id="carDetailsModal" tabindex="-1">
      <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">وردەکاری خەرجی سەیارە</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div id="carDetailsContent">
              <!-- Car details will be loaded here -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/swalAlert.js"></script>
  <script src="../assets/js/comon/select2_script.js"></script>
  <script src="../assets/js/comon/table-controler.js"></script>
  <script src="../assets/js/summery_car_expenses/filter.js"></script>
  <script src="../assets/js/summery_car_expenses/get_informations.js"></script>
  <script>
    // Pass permissions to JavaScript
    window.userPermissions = {
      canViewCarExpenses: <?php echo hasPermission('view_car_expenses_summary') ? 'true' : 'false'; ?>,
      canEditCarExpenses: <?php echo hasPermission('edit_car_expenses') ? 'true' : 'false'; ?>
    };
    

  </script>
</body>

</html>
