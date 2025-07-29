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
    . '<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
    . '<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
    . '</div>';
  exit;
}
$customers = $pdo->query("SELECT id, name, mobile1 FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$formulas = $pdo->query("SELECT id, name FROM concrete_formulas")->fetchAll(PDO::FETCH_ASSOC);
$cars = $pdo->query("SELECT id, name FROM cars")->fetchAll(PDO::FETCH_ASSOC);
$employees = $pdo->query("SELECT id, name, role FROM employees")->fetchAll(PDO::FETCH_ASSOC);
$drivers = array_filter($employees, function ($emp) {
  return $emp['role'] === 'شۆفێر'; });
$mixer_cars = array_filter($cars, function ($car) {
  return preg_match('/^m/i', trim($car['name'])); });
$pump_cars = array_filter($cars, function ($car) {
  return preg_match('/^p/i', trim($car['name'])); });

// Define allowed names for pump and mixer
$pump_names = ['بەرزان', 'شاڵاو', 'سەربەست', 'بازیان'];
$mixer_names = ['بەرزان', 'شاڵاو', 'سەربەست', 'بازیان', 'طارق', 'عماد', 'علاوی', 'ئامانج', 'احمد(ابو روەیدا)', 'وشیار', 'هۆژین', 'هاوکار', 'عادل', 'ڕزگار'];

// Filter employees for pump and mixer
$pump_drivers = array_filter($employees, function ($emp) use ($pump_names) {
  return $emp['role'] === 'شۆفێر' && in_array(trim($emp['name']), $pump_names, true);
});
$mixer_drivers = array_filter($employees, function ($emp) {
    $excluded = ['بەرزان', 'شاڵاو', 'سەربەست'];
    return $emp['role'] === 'شۆفێر' && !in_array(trim($emp['name']), $excluded, true);
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
  <link href="../assets/css/comon/cards.css" rel="stylesheet" />
  <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="../assets/css/concrete_receipts_custom.css" rel="stylesheet">
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
        <?php if (hasPermission('view_notes')): ?>
          <a href="notes.php" class="btn" style="background: var(--kelly-green); color:white; font-weight: bold;">
            <i class="fas fa-sticky-note me-1"></i>تێبینیەکان
          </a>
        <?php endif; ?>
        <?php if (hasPermission('view_summery_concrete_receipts')): ?>
          <a href="summery_concrete_receipts.php" class="btn" style="background: var(--seafoam-green); color:white; font-weight: bold;">
            <i class="fas fa-chart-bar me-1"></i>پوختە
          </a>
        <?php endif; ?>
        <?php if (hasPermission('add_customer')): ?>
          <button class="btn" data-bs-toggle="modal" data-bs-target="#addCustomerModal"
            style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردنی کڕیار</button>
        <?php endif; ?>
        <?php if (hasPermission('add_concrete_receipts')): ?>
          <button class="btn" data-bs-toggle="modal" data-bs-target="#addConcreteReceiptModal"
            style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردنی پسوڵە</button>
        <?php endif; ?>
      </div>
    </div>
    <!-- Summary Cards Row -->
    <div class="row mb-3" id="concrete-receipts-summary">
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow  card-gradient-info card-animate-hover">
          <div class="card-body">
            <i class="fas fa-file-alt card-icon"></i>
            <h6 class="card-title">کۆی گشتی پسوڵەکان</h6>
            <div class="fs-4 fw-bold" id="summary_total_receipts">0</div>
            <small class="text-light">ژمارەی پسوڵەکان</small>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow  card-gradient-success card-animate-hover">
          <div class="card-body">
            <i class="fas fa-cube card-icon"></i>
            <h6 class="card-title">کۆی گشتی بڕی مەتر سێجا</h6>
            <div class="fs-4 fw-bold" id="summary_total_meter">0</div>
            <small class="text-light">بڕی کۆنکرێت</small>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow  card-gradient-warning card-animate-hover">
          <div class="card-body">
            <i class="fas fa-users card-icon"></i>
            <h6 class="card-title">کۆی کڕیاران</h6>
            <div class="fs-4 fw-bold" id="summary_total_customers">0</div>
            <small class="text-light">ژمارەی کڕیارەکان</small>
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
        <button type="button" class="btn btn-sm" id="filter_today" data-filter="today" style="background: var(--seafoam-green); color: white; font-weight: bold;">
          <i class="fas fa-calendar-day me-1"></i>ئەمڕۆ
        </button>
        <button type="button" class="btn btn-sm" id="filter_yesterday" data-filter="yesterday" style="background: var(--kelly-green); color: white; font-weight: bold;">
          <i class="fas fa-calendar-minus me-1"></i>دوێنێ
        </button>
        <button type="button" class="btn btn-sm" id="filter_reset" data-filter="reset" style="background: var(--lime-green); color: white; font-weight: bold;">
          <i class="fas fa-redo me-1"></i>ڕیفڕێش
        </button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle text-center" id="concreteReceiptsTable">
        <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
          <tr>
            <th style="width:1px;">#</th>
            <th>ژم.پسووڵە</th>
            <th>کڕیار</th>
            <th>شوێن</th>
            <th>وەرگر</th>
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
  <div class="modal fade" id="addConcreteReceiptModal" tabindex="-1" aria-labelledby="addConcreteReceiptModalLabel"
    aria-hidden="true">
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
                <select class="form-select" id="customer_id" name="customer_id" required style="max-height:220px; overflow-y:auto;">
                  <option value="">هەڵبژێرە</option>
                  <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?><?php if (!empty($c['mobile1'])): ?> (<?= htmlspecialchars($c['mobile1']) ?>)<?php endif; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label for="location" class="form-label">شوێن</label>
                <input type="text" class="form-control" id="location" name="location" required>

                <label for="meter_amount" class="form-label">بڕی مەتر سێجا</label>
                <input type="number" class="form-control" id="meter_amount" name="meter_amount" min="0" max="12"
                  step="0.5" required>
            

                

              </div>

              <div class="col-md-6">
              <label class="form-label" for="receiver_name">ناوی وەرگر</label>
              <input type="text" class="form-control" name="receiver_name" id="receiver_name">
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
                      <select class="form-select" id="mixer_car_id" name="mixer_car_id" required>
                        <option value="">هەڵبژێرە</option>
                        <?php foreach ($mixer_cars as $car): ?>
                          <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="mixer_driver_id" class="form-label">شۆفێری میکسەر</label>
                      <select class="form-select" id="mixer_driver_id" name="mixer_driver_id" required>
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
            <button type="submit" class="btn"
              style="background: var(--seafoam-green); color: white; font-weight: bold;">زیادکردن</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Edit Concrete Receipt Modal -->
  <div class="modal fade" id="editConcreteReceiptModal" tabindex="-1" aria-labelledby="editConcreteReceiptModalLabel"
    aria-hidden="true">
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
                <select class="form-select" id="edit_customer_id" name="customer_id" required>
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
                <label for="edit_receiver_name" class="form-label">وەرگر</label>
                <input type="text" class="form-control" id="edit_receiver_name" name="edit_receiver_name">
              </div>
              <div class="col-md-6">
                <label for="edit_meter_amount" class="form-label">بڕی مەتر سێجا</label>
                <input type="number" class="form-control" id="edit_meter_amount" name="meter_amount" min="0" max="12"
                  step="0.5" required>
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
                      <select class="form-select" id="edit_mixer_car_id" name="mixer_car_id" required>
                        <option value="">هەڵبژێرە</option>
                        <?php foreach ($mixer_cars as $car): ?>
                          <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="edit_mixer_driver_id" class="form-label">شۆفێری میکسەر</label>
                      <select class="form-select" id="edit_mixer_driver_id" name="mixer_driver_id" required>
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
            <button type="submit" class="btn"
              style="background: var(--seafoam-green); color: white; font-weight: bold;">نوێکردنەوە</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Add Customer Modal -->
  <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="addCustomerForm">
          <div class="modal-header">
            <h5 class="modal-title" id="addCustomerModalLabel">زیادکردنی کڕیار</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="customer_name" class="form-label">ناوی کڕیار</label>
              <input type="text" class="form-control" id="customer_name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="customer_phone1" class="form-label">ژمارە تەلەفۆنی یەکەم</label>
              <input type="text" class="form-control" id="customer_phone1" name="mobile1" required>
            </div>
            <div class="mb-3">
              <label for="customer_phone2" class="form-label">ژمارە تەلەفۆنی دووەم (ئیختیاری)</label>
              <input type="text" class="form-control" id="customer_phone2" name="mobile2">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
            <button type="submit" class="btn"
              style="background: #1976d2; color: white; font-weight: bold;">زیادکردن</button>
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
  <script src="../assets/js/concrete_receipts/add_customer.js"></script>
  <script src="../assets/js/concrete_receipts/filter.js"></script>
  <script src="../assets/js/concrete_receipts/add_concerete_receipts.js"></script>
  <script src="../assets/js/concrete_receipts/select_concrete_receipts.js"></script>
  <script src="../assets/js/concrete_receipts/delete_concrete_receipts.js"></script>
  <script src="../assets/js/concrete_receipts/update_concrete_receipts.js"></script>
  <script src="../assets/js/concrete_receipts/concrete_receipts_custom.js"></script>
  <script>
    // Pass permissions to JavaScript
    window.userPermissions = {
      canAdd: <?php echo hasPermission('add_concrete_receipts') ? 'true' : 'false'; ?>,
      canEdit: <?php echo hasPermission('edit_concrete_receipts') ? 'true' : 'false'; ?>,
      canDelete: <?php echo hasPermission('delete_concrete_receipts') ? 'true' : 'false'; ?>,
      canPrint: <?php echo hasPermission('print_concrete_receipts') ? 'true' : 'false'; ?>
    };
    // Auto-open add modal if redirected with ?open_add=1
    document.addEventListener('DOMContentLoaded', function() {
      const params = new URLSearchParams(window.location.search);
      if (params.get('open_add') === '1') {
        const modalEl = document.getElementById('addConcreteReceiptModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        
        // Fill form with data from URL parameters
        if (params.get('customer_id')) {
          document.getElementById('customer_id').value = params.get('customer_id');
        }
        if (params.get('location')) {
          document.getElementById('location').value = params.get('location');
        }
        if (params.get('receiver_name')) {
          document.getElementById('receiver_name').value = params.get('receiver_name');
        }
        // Note: meter_amount is intentionally not filled from notes to allow manual entry
        // if (params.get('meter_amount')) {
        //   document.getElementById('meter_amount').value = params.get('meter_amount');
        // }
        if (params.get('formula_id')) {
          document.getElementById('formulas_id').value = params.get('formula_id');
        }
        if (params.get('mixer_car_id')) {
          document.getElementById('mixer_car_id').value = params.get('mixer_car_id');
        }
        if (params.get('mixer_driver_id')) {
          document.getElementById('mixer_driver_id').value = params.get('mixer_driver_id');
        }
        if (params.get('pump_car_id')) {
          document.getElementById('pump_car_id').value = params.get('pump_car_id');
        }
        if (params.get('pump_driver_id')) {
          document.getElementById('pump_driver_id').value = params.get('pump_driver_id');
        }
        
        // Manually fetch and set the next receipt number
        fetch('../process/concrete_receipts/get_next_receipt_number.php')
          .then(res => res.json())
          .then(res => {
            if (res && res.success && res.next) {
              document.getElementById('receipt_number').value = res.next;
            } else {
              document.getElementById('receipt_number').value = 'A-0001';
            }
          })
          .catch(() => {
            document.getElementById('receipt_number').value = 'A-0001';
          });
      }
    });
  </script>
</body>

</html>