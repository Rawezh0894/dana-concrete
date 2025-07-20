<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if user has permission to view concrete receipts
if (!hasPermission('view_concrete_receipts')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
$customers = $pdo->query("SELECT id, name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$formulas = $pdo->query("SELECT id, name FROM concrete_formulas")->fetchAll(PDO::FETCH_ASSOC);
$cars = $pdo->query("SELECT id, name FROM cars")->fetchAll(PDO::FETCH_ASSOC);
$employees = $pdo->query("SELECT id, name, role FROM employees")->fetchAll(PDO::FETCH_ASSOC);
$drivers = array_filter($employees, function($emp) { return $emp['role'] === 'شۆفێر'; });
$mixer_cars = array_filter($cars, function($car) { return preg_match('/^m/i', trim($car['name'])); });
$pump_cars = array_filter($cars, function($car) { return preg_match('/^p/i', trim($car['name'])); });

// Define allowed names for pump and mixer
$pump_names = ['بەرزان', 'شاڵاو', 'سەربەست', 'بازیان'];
$mixer_names = ['بەرزان', 'شاڵاو', 'سەربەست', 'بازیان', 'طارق', 'عماد', 'علاوی', 'ئامانج', 'احمد(ابو روەیدا)', 'وشیار', 'هۆژین', 'هاوکار', 'عادل', 'ڕزگار'];

// Filter employees for pump and mixer
$pump_drivers = array_filter($employees, function($emp) use ($pump_names) {
    return $emp['role'] === 'شۆفێر' && in_array(trim($emp['name']), $pump_names, true);
});
$mixer_drivers = array_filter($employees, function($emp) use ($mixer_names) {
    return $emp['role'] === 'شۆفێر' && in_array(trim($emp['name']), $mixer_names, true);
});
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زیادکردنی پسوڵەی کۆنکرێت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Modern Professional Filter Buttons Design */
        .filter-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 700;
            border: 2px solid transparent;
            border-radius: 25px;
            padding: 12px 24px;
            font-size: 13px;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.25);
            text-transform: none;
            letter-spacing: 0.3px;
            backdrop-filter: blur(10px);
            min-width: 120px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .filter-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transition: left 0.6s ease;
        }
        
        .filter-btn:hover::before {
            left: 100%;
        }
        
        /* Hover effects */
        .filter-btn:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.35);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        /* Active state */
        .filter-btn.active {
            background: linear-gradient(135deg, #4c63d2 0%, #5d3780 100%);
            transform: translateY(0) scale(0.98);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        /* Specific colors for each button */
        #filter_today {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            box-shadow: 0 6px 20px rgba(17, 153, 142, 0.25);
        }
        
        #filter_today:hover {
            background: linear-gradient(135deg, #0f8a7d 0%, #2fd86a 100%);
            box-shadow: 0 12px 30px rgba(17, 153, 142, 0.35);
        }
        
        #filter_today.active {
            background: linear-gradient(135deg, #0d7a6d 0%, #26c85a 100%);
            box-shadow: 0 8px 25px rgba(17, 153, 142, 0.4);
        }
        
        #filter_yesterday {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            box-shadow: 0 6px 20px rgba(255, 154, 158, 0.25);
        }
        
        #filter_yesterday:hover {
            background: linear-gradient(135deg, #ff8a8e 0%, #febfdf 100%);
            box-shadow: 0 12px 30px rgba(255, 154, 158, 0.35);
        }
        
        #filter_yesterday.active {
            background: linear-gradient(135deg, #ff7a7e 0%, #feafcf 100%);
            box-shadow: 0 8px 25px rgba(255, 154, 158, 0.4);
        }
        
        #filter_reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.25);
        }
        
        #filter_reset:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.35);
        }
        
        #filter_reset.active {
            background: linear-gradient(135deg, #4c63d2 0%, #5d3780 100%);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        /* Click animation */
        .filter-btn:active {
            transform: translateY(1px) scale(0.96);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        /* Icon styling */
        .filter-btn i {
            font-size: 14px;
            transition: all 0.3s ease;
            opacity: 0.9;
        }
        
        .filter-btn:hover i {
            transform: rotate(5deg) scale(1.1);
            opacity: 1;
        }
        
        /* Focus state for accessibility */
        .filter-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .col-md-2.d-flex.gap-2 {
                flex-direction: column;
                gap: 15px !important;
            }
            
            .col-md-2.d-flex.gap-2 .filter-btn {
                width: 100%;
                margin-bottom: 10px;
                padding: 15px 24px;
                font-size: 15px;
                border-radius: 30px;
            }
        }
        
        /* Loading state */
        .filter-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .filter-btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 18px;
            height: 18px;
            margin: -9px 0 0 -9px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Button container styling */
        .filter-buttons-container {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">پسوڵەی کۆنکرێت</h2>
        <?php if (hasPermission('add_concrete_receipts')): ?>
        <button class="btn" data-bs-toggle="modal" data-bs-target="#addConcreteReceiptModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردنی پسوڵە</button>
        <?php endif; ?>
    </div>
    <!-- Summary Cards Row -->
    <div class="row mb-3" id="concrete-receipts-summary">
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow">
          <div class="card-body">
            <h5 class="card-title">کۆی گشتی پسوڵەکان</h5>
            <span id="summary_total_receipts" style="font-size:2rem;font-weight:bold;">0</span>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow">
          <div class="card-body">
            <h5 class="card-title">کۆی گشتی بڕی مەتر سێجا</h5>
            <span id="summary_total_meter" style="font-size:2rem;font-weight:bold;">0</span>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow">
          <div class="card-body">
            <h5 class="card-title">کۆی کڕیاران</h5>
            <span id="summary_total_customers" style="font-size:2rem;font-weight:bold;">0</span>
          </div>
        </div>
      </div>
    </div>
    <!-- Filter Row -->
    <div class="row g-2 mb-3 " id="concrete-receipts-filters">
      <div class="col-md-3">
        <select class="form-select" id="filter_customer_id">
          <option value="">کڕیار: هەموو</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <input type="text" class="form-control" id="filter_location" placeholder="شوێن...">
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filter_formulas_id">
          <option value="">ڕێژە: هەموو</option>
          <?php foreach ($formulas as $f): ?>
            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filter_date_from" placeholder="لە بەرواری">
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filter_date_to" placeholder="بۆ بەرواری">
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="button" class="btn btn-sm filter-btn" id="filter_today" data-filter="today">
          <i class="fas fa-calendar-day me-1"></i>ئەمڕۆ
        </button>
        <button type="button" class="btn btn-sm filter-btn" id="filter_yesterday" data-filter="yesterday">
          <i class="fas fa-calendar-minus me-1"></i>دوێنێ
        </button>
        <button type="button" class="btn btn-sm filter-btn" id="filter_reset" data-filter="reset">
          <i class="fas fa-redo me-1"></i>ڕیفڕێش
        </button>
      </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="concreteReceiptsTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>ژمارەی پسوڵە</th>
                    <th>کڕیار</th>
                    <th>شوێن</th>
                    <th>بەروار</th>
                    <th>بڕی مەتر سێجا</th>
                    <th>فۆرمۆلا</th>
                  
                    <th>پەمپ</th>
                    <th>شۆفێری پەمپ</th>
                    <th>میکسەر</th>
                    <th>شۆفێری میکسەر</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Receipts will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Concrete Receipt Modal -->
<div class="modal fade" id="addConcreteReceiptModal" tabindex="-1" aria-labelledby="addConcreteReceiptModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addConcreteReceiptForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addConcreteReceiptModalLabel">زیادکردنی پسوڵەی کۆنکرێت</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="receipt_number" class="form-label">ژمارەی پسوڵە</label>
                <input type="text" class="form-control" id="receipt_number" name="receipt_number" readonly required>
              </div>
              <div class="col-md-6">
                <label for="customer_id" class="form-label">ناوی کڕیار</label>
                <select class="form-select" id="customer_id" name="customer_id">
                  <option value="">هەڵبژێرە</option>
                  <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label for="location" class="form-label">شوێن</label>
                <input type="text" class="form-control" id="location" name="location" required>
              </div>
              <div class="col-md-6">
                <label for="meter_amount" class="form-label">بڕی مەتر سێجا</label>
                <input type="number" class="form-control" id="meter_amount" name="meter_amount" min="0" max="12" step="0.01" required>
              </div>
              <div class="col-md-6">
                <label for="formulas_id" class="form-label">ڕێژە</label>
                <select class="form-select" id="formulas_id" name="formulas_id" required>
                  <option value="">هەڵبژێرە</option>
                  <?php foreach ($formulas as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="row mt-4">
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header bg-light fw-bold">میکسەر</div>
                  <div class="card-body">
                    <div class="mb-3">
                      <label for="mixer_car_id" class="form-label">کۆدی میکسەر</label>
                      <select class="form-select" id="mixer_car_id" name="mixer_car_id">
                        <option value="">هەڵبژێرە</option>
                        <?php foreach ($mixer_cars as $car): ?>
                          <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="mixer_driver_id" class="form-label">شۆفێری میکسەر</label>
                      <select class="form-select" id="mixer_driver_id" name="mixer_driver_id">
                        <option value="">هەڵبژێرە</option>
                        <?php foreach ($mixer_drivers as $emp): ?>
                          <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header bg-light fw-bold">پەمپ</div>
                  <div class="card-body">
                    <div class="mb-3">
                      <label for="pump_car_id" class="form-label">کۆدی پەمپ</label>
                      <select class="form-select" id="pump_car_id" name="pump_car_id">
                        <option value="">هەڵبژێرە</option>
                        <?php foreach ($pump_cars as $car): ?>
                          <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="pump_driver_id" class="form-label">شۆفێری پەمپ</label>
                      <select class="form-select" id="pump_driver_id" name="pump_driver_id">
                        <option value="">هەڵبژێرە</option>
                        <?php foreach ($pump_drivers as $emp): ?>
                          <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
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
<!-- Edit Concrete Receipt Modal -->
<div class="modal fade" id="editConcreteReceiptModal" tabindex="-1" aria-labelledby="editConcreteReceiptModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editConcreteReceiptForm">
        <input type="hidden" id="edit_receipt_id" name="id">
        <div class="modal-header">
          <h5 class="modal-title" id="editConcreteReceiptModalLabel">نوێکردنەوەی پسوڵەی کۆنکرێت</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="edit_receipt_number" class="form-label">ژمارەی پسوڵە</label>
                <input type="text" class="form-control" id="edit_receipt_number" name="receipt_number" required>
              </div>
              <div class="col-md-6">
                <label for="edit_customer_id" class="form-label">ناوی کڕیار</label>
                <select class="form-select" id="edit_customer_id" name="customer_id">
                  <option value="">هەڵبژێرە</option>
                  <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label for="edit_location" class="form-label">شوێن</label>
                <input type="text" class="form-control" id="edit_location" name="location" required>
              </div>
              <div class="col-md-6">
                <label for="edit_meter_amount" class="form-label">بڕی مەتر سێجا</label>
                <input type="number" class="form-control" id="edit_meter_amount" name="meter_amount" min="0" max="12" step="0.01" required>
              </div>
              <div class="col-md-6">
                <label for="edit_formulas_id" class="form-label">ڕێژە</label>
                <select class="form-select" id="edit_formulas_id" name="formulas_id" required>
                  <option value="">هەڵبژێرە</option>
                  <?php foreach ($formulas as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="row mt-4">
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header bg-light fw-bold">میکسەر</div>
                  <div class="card-body">
                    <div class="mb-3">
                      <label for="edit_mixer_car_id" class="form-label">کۆدی میکسەر</label>
                      <select class="form-select" id="edit_mixer_car_id" name="mixer_car_id">
                        <option value="">هەڵبژێرە</option>
                        <?php foreach ($mixer_cars as $car): ?>
                          <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="edit_mixer_driver_id" class="form-label">شۆفێری میکسەر</label>
                      <select class="form-select" id="edit_mixer_driver_id" name="mixer_driver_id">
                        <option value="">هەڵبژێرە</option>
                        <?php foreach ($mixer_drivers as $emp): ?>
                          <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header bg-light fw-bold">پەمپ</div>
                  <div class="card-body">
                    <div class="mb-3">
                      <label for="edit_pump_car_id" class="form-label">کۆدی پەمپ</label>
                      <select class="form-select" id="edit_pump_car_id" name="pump_car_id">
                        <option value="">هەڵبژێرە</option>
                        <?php foreach ($pump_cars as $car): ?>
                          <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="edit_pump_driver_id" class="form-label">شۆفێری پەمپ</label>
                      <select class="form-select" id="edit_pump_driver_id" name="pump_driver_id">
                        <option value="">هەڵبژێرە</option>
                        <?php foreach ($pump_drivers as $emp): ?>
                          <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
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
<script src="../assets/js/concrete_receipts/add_concerete_receipts.js"></script>
<script src="../assets/js/concrete_receipts/select_concrete_receipts.js"></script>
<script src="../assets/js/concrete_receipts/delete_concrete_receipts.js"></script>
<script src="../assets/js/concrete_receipts/update_concrete_receipts.js"></script>
<script>
// Pass permissions to JavaScript
window.userPermissions = {
    canAdd: <?php echo hasPermission('add_concrete_receipts') ? 'true' : 'false'; ?>,
    canEdit: <?php echo hasPermission('edit_concrete_receipts') ? 'true' : 'false'; ?>,
    canDelete: <?php echo hasPermission('delete_concrete_receipts') ? 'true' : 'false'; ?>,
    canPrint: <?php echo hasPermission('print_concrete_receipts') ? 'true' : 'false'; ?>
};
</script>
<script src="../assets/js/concrete_receipts/filter.js"></script>
<script>
// Filter button active state management
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Remove active class after 2 seconds (for reset button)
            if (this.id === 'filter_reset') {
                setTimeout(() => {
                    this.classList.remove('active');
                }, 2000);
            }
        });
    });
    
    // Remove active class when other filters are used
    const filterInputs = document.querySelectorAll('#filter_customer_id, #filter_location, #filter_formulas_id, #filter_date_from, #filter_date_to');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
        });
    });
});
</script>
</body>
</html>
