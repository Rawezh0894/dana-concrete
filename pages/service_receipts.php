<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  redirectToLogin();
  exit;
}

// Check if user has permission to view service receipts
// Assuming you will add these permissions to your DB
if (!hasPermission('view_service_receipts')) { 
  // Fallback or show error. 
  // For now let's show the standard access denied message.
  echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
    . '<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
    . '<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
    . '</div>';
  exit;
}

$customers = $pdo->query("SELECT id, name, mobile1, mobile2 FROM customers")->fetchAll(PDO::FETCH_ASSOC);

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

$cars = $pdo->query("SELECT id, name FROM cars")->fetchAll(PDO::FETCH_ASSOC);
// Get only active employees with their roles
$employees = $pdo->query("SELECT id, name, role, COALESCE(status, 'active') as status FROM employees WHERE COALESCE(status, 'active') = 'active'")->fetchAll(PDO::FETCH_ASSOC);

// Filter lists for Pump and Mixer
$mixer_cars = array_filter($cars, function ($car) {
  return preg_match('/^m/i', trim($car['name'])); 
});
$pump_cars = array_filter($cars, function ($car) {
  return preg_match('/^p/i', trim($car['name'])); 
});

// Filter employees logic (reused from concrete_receipts)
$mixer_drivers = array_filter($employees, function ($emp) {
    $role = $emp['role'] ?? '';
    return (strpos($role, 'شۆفێری میکسەر') !== false || strpos($role, 'شۆفێری پەمپ') !== false || strpos($role, 'جۆکەر') !== false);
});

$pump_drivers = array_filter($employees, function ($emp) {
    $role = $emp['role'] ?? '';
    return (strpos($role, 'شۆفێری پەمپ') !== false || strpos($role, 'مساعید پەمپ') !== false || strpos($role, 'جۆکەر') !== false);
});
?>
<!DOCTYPE html>
<html lang="ku">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>بەڕێوەبردنی داهاتی خزمەتگوزاری</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="../assets/css/login.css" rel="stylesheet">
  <link href="../assets/css/variables.css" rel="stylesheet">
  <link href="../assets/css/nav.css" rel="stylesheet">
  <link href="../assets/css/comon/table.css" rel="stylesheet">
  <link href="../assets/css/comon/style.css" rel="stylesheet">
  <link href="../assets/css/comon/cards.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- Use concrete receipts custom CSS for now as base -->
  <link href="../assets/css/concrete_receipts_custom.css" rel="stylesheet">
  
  <!-- AG Grid CSS -->
  <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css" rel="stylesheet">
  <link href="../assets/css/comon/ag_grid.css" rel="stylesheet">
  <!-- Reuse or create new css -->
  <link href="../assets/css/concrete_receipts/ag_grid_concrete_receipts.css" rel="stylesheet">
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
  <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>

<body dir="rtl">
  <?php include '../includes/navbar.php'; ?>
  <?php include '../includes/sidebar.php'; ?>

  <div class="container-fluid py-5">
    <div class="page-actions-wrapper">
      <div class="page-action-buttons">
        <!-- Add Summery button if needed -->
        
        <?php if (hasPermission('add_service_receipts')): ?>
          <button class="btn" data-bs-toggle="modal" data-bs-target="#addServiceReceiptModal"
            style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردنی پسوڵە</button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-3" id="service-receipts-summary">
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow card-gradient-info card-animate-hover">
          <div class="card-body">
            <i class="fas fa-file-invoice-dollar card-icon"></i>
            <h6 class="card-title">کۆی گشتی پسوڵەکان</h6>
            <div class="fs-4 fw-bold" id="summary_total_receipts">0</div>
            <small class="text-light">ژمارەی پسوڵەکان</small>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow card-gradient-success card-animate-hover">
          <div class="card-body">
            <i class="fas fa-truck-moving card-icon"></i>
            <h6 class="card-title">کۆی گشتی مەتر سێجا</h6>
            <div class="fs-4 fw-bold" id="summary_total_meter">0</div>
            <small class="text-light">بڕی خزمەتگوزاری</small>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow card-gradient-warning card-animate-hover">
          <div class="card-body">
            <i class="fas fa-hand-holding-usd card-icon"></i>
            <h6 class="card-title">کۆی پارە</h6>
            <div class="fs-4 fw-bold" id="summary_total_price">0</div>
            <small class="text-light">بەهای خزمەتگوزاری</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Search Row -->
    <div class="row g-2 mb-3">
      <div class="col-md-12">
        <div class="input-group">
          <span class="input-group-text" style="background: var(--kelly-green); color: var(--seafoam-green); font-weight: bold;">
            <i class="fas fa-search"></i> گەڕان
          </span>
          <input type="text" class="form-control" id="quickSearchInput" placeholder="گەڕان لە هەموو ستونەکاندا...">
          <button class="btn btn-secondary" type="button" id="clearQuickSearch" title="پاککردنەوەی گەڕان">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Filter Row -->
    <div class="row g-2 mb-3">
      <div class="col-md-4">
        <select class="form-select" id="filter_customer_id" data-placeholder="کڕیار: هەموو">
          <option value=""></option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filter_date_from" placeholder="لە بەرواری">
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filter_date_to" placeholder="بۆ بەرواری">
      </div>
      <div class="col-md-4 d-flex gap-2">
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
      <div id="serviceReceiptsGrid" class="ag-grid-container ag-theme-alpine" style="height: 600px;"></div>
    </div>
  </div>

  <!-- Add Service Receipt Modal -->
  <div class="modal fade" id="addServiceReceiptModal" tabindex="-1" aria-labelledby="addServiceReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="addServiceReceiptForm">
          <div class="modal-header">
            <h5 class="modal-title" id="addServiceReceiptModalLabel">زیادکردنی پسوڵەی خزمەتگوزاری</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label for="receipt_number" class="form-label">ژمارەی پسوڵە</label>
                <input type="text" class="form-control" id="receipt_number" name="receipt_number" required>
              </div>
              <div class="col-md-4">
                <label for="customer_id" class="form-label">ناوی کڕیار (کۆمپانیا)</label>
                <select class="form-select" id="customer_id" name="customer_id" required>
                  <option value="">هەڵبژێرە</option>
                  <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label for="location" class="form-label">شوێن</label>
                <input type="text" class="form-control" id="location" name="location">
              </div>
              
              <div class="col-md-4">
                <label for="meter_amount" class="form-label">بڕی مەتر سێجا</label>
                <input type="number" class="form-control calc-input" id="meter_amount" name="meter_amount" min="0" step="0.01" required>
              </div>
              <div class="col-md-4">
                <label for="price_per_meter" class="form-label">نرخی مەتر (USD)</label>
                <input type="number" class="form-control calc-input" id="price_per_meter" name="price_per_meter" min="0" step="0.01" value="0">
              </div>
              <div class="col-md-4">
                <label class="form-label">کۆی گشتی نرخ (USD)</label>
                <input type="text" class="form-control bg-light fw-bold text-primary" id="display_total_price" readonly value="0.00">
              </div>
              <div class="col-md-4">
                <label for="receiver_name" class="form-label">وەرگر</label>
                <select class="form-select" id="receiver_name" name="receiver_name">
                  <option value="">هەڵبژێرە</option>
                  <?php foreach ($recipients as $recipient): ?>
                    <option value="<?= htmlspecialchars($recipient['name']) ?>"><?= htmlspecialchars($recipient['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-3">
                <label for="payment_type" class="form-label">جۆری پارەدان</label>
                <select class="form-select" id="payment_type" name="payment_type">
                  <option value="credit">قەرز</option>
                  <option value="cash">نەقد</option>
                </select>
              </div>
              <div class="col-md-3 cash-fields d-none">
                <label for="paid_usd" class="form-label">دراو (USD)</label>
                <input type="number" class="form-control calc-input" id="paid_usd" name="paid_usd" step="0.01" value="0.00">
              </div>
              <div class="col-md-3 cash-fields d-none">
                <label for="paid_iqd" class="form-label">دراو (IQD)</label>
                <input type="number" class="form-control calc-input" id="paid_iqd" name="paid_iqd" step="250" value="0">
              </div>
              <div class="col-md-3 cash-fields d-none">
                <label for="exchange_rate" class="form-label">نرخی سەرف</label>
                <input type="number" class="form-control calc-input" id="exchange_rate" name="exchange_rate" value="150000">
              </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                   <div class="alert alert-secondary d-flex justify-content-between align-items-center py-2 mb-0">
                       <span class="fw-bold">بڕی ماوە (Balance):</span>
                       <span id="display_remaining_balance" class="fs-5 fw-bold text-danger">0.00 $</span>
                   </div>
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
            
            <div class="mb-3 mt-3">
                <label for="notes" class="form-label">تێبینی</label>
                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
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

  <!-- Edit Service Receipt Modal -->
  <div class="modal fade" id="editServiceReceiptModal" tabindex="-1" aria-labelledby="editServiceReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="editServiceReceiptForm">
          <input type="hidden" id="edit_receipt_id" name="id">
          <div class="modal-header">
            <h5 class="modal-title" id="editServiceReceiptModalLabel">نوێکردنەوەی پسوڵەی خزمەتگوزاری</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="edit_receipt_number" class="form-label">ژمارەی پسوڵە</label>
                <input type="text" class="form-control" id="edit_receipt_number" name="receipt_number" required>
              </div>
              <div class="col-md-6">
                <label for="edit_created_at" class="form-label">بەروار و کات</label>
                <input type="datetime-local" class="form-control" id="edit_created_at" name="created_at" required>
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
                <input type="text" class="form-control" id="edit_location" name="location">
              </div>
              <div class="col-md-6">
                <label for="edit_receiver_name" class="form-label">وەرگر</label>
                <select class="form-select" id="edit_receiver_name" name="receiver_name">
                  <option value="">هەڵبژێرە</option>
                  <?php foreach ($recipients as $recipient): ?>
                    <option value="<?= htmlspecialchars($recipient['name']) ?>"><?= htmlspecialchars($recipient['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label for="edit_meter_amount" class="form-label">بڕی مەتر سێجا</label>
                <input type="number" class="form-control edit-calc-input" id="edit_meter_amount" name="meter_amount" min="0" step="0.01" required>
              </div>
              <div class="col-md-6">
                <label for="edit_price_per_meter" class="form-label">نرخ (USD)</label>
                <input type="number" class="form-control edit-calc-input" id="edit_price_per_meter" name="price_per_meter" min="0" step="0.01">
              </div>
              <div class="col-md-6">
                <label class="form-label">کۆی گشتی نرخ (USD)</label>
                <input type="text" class="form-control bg-light fw-bold text-primary" id="edit_display_total_price" readonly value="0.00">
              </div>
              <div class="col-md-6">
                <label for="edit_payment_type" class="form-label">جۆری پارەدان</label>
                <select class="form-select" id="edit_payment_type" name="payment_type">
                  <option value="credit">قەرز</option>
                  <option value="cash">نەقد</option>
                </select>
              </div>
              <div class="col-md-4 edit-cash-fields">
                <label for="edit_paid_usd" class="form-label">دراو (USD)</label>
                <input type="number" class="form-control edit-calc-input" id="edit_paid_usd" name="paid_usd" step="0.01">
              </div>
              <div class="col-md-4 edit-cash-fields">
                <label for="edit_paid_iqd" class="form-label">دراو (IQD)</label>
                <input type="number" class="form-control edit-calc-input" id="edit_paid_iqd" name="paid_iqd" step="250">
              </div>
              <div class="col-md-4 edit-cash-fields">
                <label for="edit_exchange_rate" class="form-label">نرخی سەرف</label>
                <input type="number" class="form-control edit-calc-input" id="edit_exchange_rate" name="exchange_rate">
              </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                   <div class="alert alert-secondary d-flex justify-content-between align-items-center py-2 mb-0">
                       <span class="fw-bold">بڕی ماوە (Balance):</span>
                       <span id="edit_display_remaining_balance" class="fs-5 fw-bold text-danger">0.00 $</span>
                   </div>
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
            
            <div class="mb-3 mt-3">
                <label for="edit_notes" class="form-label">تێبینی</label>
                <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
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
  <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
  <script src="../assets/js/comon/ag_grid_base.js" nonce="<?php echo $csp_nonce; ?>"></script>
  <script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
  <script src="../assets/js/comon/select2_script.js" nonce="<?php echo $csp_nonce; ?>"></script>
  <!-- Need to create these -->
  <script src="../assets/js/service_receipts/ag_grid_service_receipts.js" nonce="<?php echo $csp_nonce; ?>"></script>
  <script src="../assets/js/service_receipts/add_service_receipts.js" nonce="<?php echo $csp_nonce; ?>"></script>
  <script src="../assets/js/service_receipts/update_service_receipts.js" nonce="<?php echo $csp_nonce; ?>"></script>
  <script src="../assets/js/service_receipts/delete_service_receipts.js" nonce="<?php echo $csp_nonce; ?>"></script>
  <script src="../assets/js/service_receipts/print_service_receipts.js" nonce="<?php echo $csp_nonce; ?>"></script>
  
  <script nonce="<?php echo $csp_nonce; ?>">
    // Pass permissions to JavaScript
    window.userPermissions = {
      canAdd: <?php echo hasPermission('add_service_receipts') ? 'true' : 'false'; ?>,
      canEdit: <?php echo hasPermission('edit_service_receipts') ? 'true' : 'false'; ?>,
      canDelete: <?php echo hasPermission('delete_service_receipts') ? 'true' : 'false'; ?>,
      canPrint: <?php echo hasPermission('print_service_receipts') ? 'true' : 'false'; ?>
    };
  </script>
</body>
</html>
