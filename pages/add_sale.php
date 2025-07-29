<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">فرۆشتن</h2>
        <?php if (hasPermission('add_sale')): ?>
        <button class="btn" data-bs-toggle="modal" data-bs-target="#addSaleModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردنی فرۆشتن</button>
        <?php endif; ?>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4" id="summary-cards">
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow  card-gradient-danger card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-money-bill-wave card-icon"></i>
                    <h6 class="card-title">کۆی قەرزی کڕیاران</h6>
                    <div class="fs-4 fw-bold" id="total-customer-debt">$0</div>
                    <small class="text-light">کۆی قەرزی هەموو کڕیارەکان</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-user-times card-icon"></i>
                    <h6 class="card-title">کڕیارانی قەرزدار</h6>
                    <div class="fs-4 fw-bold" id="customers-with-debt">0</div>
                    <small class="text-light">ژمارەی کڕیارانی قەرزدار</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow  card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-shopping-cart card-icon"></i>
                    <h6 class="card-title">کۆی فرۆشتنەکان</h6>
                    <div class="fs-4 fw-bold" id="total-sales">0</div>
                    <small class="text-light">ژمارەی هەموو فرۆشتنەکان</small>
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
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center " id="saleTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>کڕیار</th>
                    <th>وەرگر</th>
                    <th>شوێن</th>
                    <th>ژمارەی پسوڵە</th>
                    <th>فۆرمۆلا</th>
                    <th>بەروار</th>
                    <th>جۆری پارەدان</th>
                    <th>بڕ</th>
                    <th>نرخی یەکە</th>
                    <th>کۆی نرخ</th>
                 
                    <th>پارەی دراو بە دینار</th>
                    <th>پارەی دراو بە دۆلار</th>
                    <th>پارەی ماوە</th>
                    <th>نرخی ١٠٠ دۆلار</th>
                    <th>تێبینی</th>
                    <th>داشکاندن</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Sales will be loaded here by JS -->
            </tbody>
        </table>
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
              <input type="text" class="form-control" id="recipient" name="recipient">
            </div>
            <div class="col-md-4 mb-3">
              <label for="location" class="form-label">شوێن</label>
              <input type="text" class="form-control" id="location" name="location">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="invoice_number" class="form-label">ژمارەی پسوڵە</label>
              <input type="text" class="form-control" id="invoice_number" name="invoice_number" required>
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
              <input type="number" class="form-control" id="discount" name="discount" min="0" step="0.0001" value="0">
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
              <input type="text" class="form-control" id="edit_recipient" name="edit_recipient">
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_location" class="form-label">شوێن</label>
              <input type="text" class="form-control" id="edit_location" name="edit_location" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="edit_invoice_number" class="form-label">ژمارەی پسوڵە</label>
              <input type="text" class="form-control" id="edit_invoice_number" name="edit_invoice_number" required>
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
              <input type="number" class="form-control" id="edit_discount" name="edit_discount" min="0" step="0.0001" value="0">
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
      canAdd: <?php echo hasPermission('add_sale') ? 'true' : 'false'; ?>,
      canEdit: <?php echo hasPermission('update_sale') ? 'true' : 'false'; ?>,
      canDelete: <?php echo hasPermission('delete_sale') ? 'true' : 'false'; ?>
    };
</script>
<script src="../assets/js/sale/add_sale.js"></script>
<script src="../assets/js/sale/select_sale.js"></script>
<script src="../assets/js/sale/delete_sale.js"></script>
<script src="../assets/js/sale/update_sale.js"></script>
<script src="../assets/js/sale/sale.js"></script>

</body>
</html>
