<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}
if (!hasPermission('view_materials')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
if (!hasPermission('view_materials')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
// Note: add_material permission is checked in the UI, not here
// Users with only view_materials permission can still access the page

// Get summary statistics
$totalMaterials = $pdo->query("SELECT COUNT(*) as count FROM list_materials")->fetch(PDO::FETCH_ASSOC)['count'];

$lowStockMaterials = $pdo->query("SELECT COUNT(*) as count FROM list_materials WHERE quantity <= 10")->fetch(PDO::FETCH_ASSOC)['count'];

// Get most used materials (from other expenses)
$mostUsedMaterials = $pdo->query("
    SELECT m.name, COUNT(*) as usage_count 
    FROM other_expenses oe 
    JOIN list_materials m ON oe.material_id = m.id 
    WHERE oe.expense_type = 'بەکارهێنانی کاڵای کۆگا'
    GROUP BY oe.material_id, m.name 
    ORDER BY usage_count DESC 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زیادکردنی کاڵا</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>

    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">کۆگا (کەل و پەل)</h2>
        <?php if (hasPermission('add_material')): ?>
        <button class="btn" data-bs-toggle="modal" data-bs-target="#addMaterialModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردنی کاڵا</button>
        <?php endif; ?>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-gradient-success card-animate-hover card-rounded card-shadow-medium text-center">
                <div class="card-body card-padding-lg">
                    <div class="card-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="card-value-lg" id="totalMaterialsCount"><?= $totalMaterials ?></div>
                    <div class="card-title-md">کۆی بەرهەمەکان</div>
                    <div class="card-desc-md">هەموو کاڵاکان</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-gradient-warning card-animate-hover card-rounded card-shadow-medium text-center">
                <div class="card-body card-padding-lg">
                    <div class="card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-value-lg" id="lowStockCount"><?= $lowStockMaterials ?></div>
                    <div class="card-title-md">کاڵا کەم بووەکان</div>
                    <div class="card-desc-md">کەمتر لە ١٠</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-gradient-info card-animate-hover card-rounded card-shadow-medium text-center">
                <div class="card-body card-padding-lg">
                    <div class="card-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="card-value-lg" id="mostUsedMaterial">
                        <?= $mostUsedMaterials ? $mostUsedMaterials['usage_count'] : '0' ?>
                    </div>
                    <div class="card-title-md">زۆرترین بەکارهاتوو</div>
                    <div class="card-desc-md" id="mostUsedMaterialName">
                        <?= $mostUsedMaterials ? $mostUsedMaterials['name'] : 'هیچ' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="materialTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>ناوی کاڵا</th>
                    <th>جۆری یەکە</th>
                    <th>بڕی بەردەست</th>
                    <th>جۆری دراو</th>
                    <th>نرخی کڕین بە دۆلار</th>
                    <th>نرخی کڕین بە دینار</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded by JavaScript -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Material Modal -->
<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addMaterialForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addMaterialModalLabel">زیادکردنی کاڵا</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="name" class="form-label">ناوی کاڵا</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="unit_type" class="form-label">جۆری یەکە</label>
              <select class="form-select" id="unit_type" name="unit_type" required>
                <option value="" selected disabled>-- هەڵبژێرە --</option>
                <option value="کارتۆن">کارتۆن</option>
                <option value="دانە">دانە</option>
                <option value="بەرمیل">بەرمیل</option>
                <option value="دەبە">دەبە</option>
                <option value="لیتر">لیتر</option>
              </select>
            </div>
          </div>
          
          <!-- Conversion Fields (shown/hidden based on unit type) -->
          <div id="conversion_fields" style="display:none;">
            <div class="row">
              <div class="col-md-6 mb-3" id="pieces_per_carton_group" style="display:none;">
                <label for="pieces_per_carton" class="form-label">ژمارەی دانە لە کارتۆن</label>
                <input type="number" class="form-control" id="pieces_per_carton" name="pieces_per_carton" min="1" value="1">
              </div>
              <div class="col-md-6 mb-3" id="buckets_per_barrel_group" style="display:none;">
                <label for="buckets_per_barrel" class="form-label">ژمارەی دەبە لە بەرمیل</label>
                <input type="number" class="form-control" id="buckets_per_barrel" name="buckets_per_barrel" min="1" value="1">
              </div>
              <div class="col-md-6 mb-3" id="liters_per_bucket_group" style="display:none;">
                <label for="liters_per_bucket" class="form-label">ژمارەی لیتر لە دەبە</label>
                <input type="number" class="form-control" id="liters_per_bucket" name="liters_per_bucket" min="0.01" step="0.01" value="1">
              </div>
              <div class="col-md-6 mb-3" id="liters_per_barrel_group" style="display:none;">
                <label for="liters_per_barrel" class="form-label">کۆی لیتر لە بەرمیل</label>
                <input type="number" class="form-control" id="liters_per_barrel" name="liters_per_barrel" min="0.01" step="0.01" value="1">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="quantity" class="form-label">بڕی بەردەست</label>
              <input type="number" class="form-control" id="quantity" name="quantity" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="currency_type" class="form-label">جۆری دراو</label>
              <select class="form-select" id="currency_type" name="currency_type" required>
                <option value="" selected disabled>-- هەڵبژێرە --</option>
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3" id="price_usd_group" style="display:none;">
              <label for="purchase_price_usd" class="form-label">نرخی کڕین بە دۆلار</label>
              <input type="number" class="form-control" id="purchase_price_usd" name="purchase_price_usd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3" id="price_iqd_group" style="display:none;">
              <label for="purchase_price_iqd" class="form-label">نرخی کڕین بە دینار</label>
              <input type="number" class="form-control" id="purchase_price_iqd" name="purchase_price_iqd" min="0" step="0.01" value="0">
            </div>
          </div>

          <!-- Calculated Prices Display -->
          <div id="calculated_prices" style="display:none;">
            <div class="row">
              <div class="col-12">
                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">نرخەکانی ژمێردراو</h6>
              </div>
              <div class="col-md-4 mb-3" id="price_per_piece_group" style="display:none;">
                <label class="form-label">نرخی دانە بە دۆلار</label>
                <input type="text" class="form-control" id="price_per_piece_usd" readonly>
              </div>
              <div class="col-md-4 mb-3" id="price_per_bucket_group" style="display:none;">
                <label class="form-label">نرخی دەبە بە دۆلار</label>
                <input type="text" class="form-control" id="price_per_bucket_usd" readonly>
              </div>
              <div class="col-md-4 mb-3" id="price_per_liter_group" style="display:none;">
                <label class="form-label">نرخی لیتر بە دۆلار</label>
                <input type="text" class="form-control" id="price_per_liter_usd" readonly>
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
<!-- Edit Material Modal -->
<div class="modal fade" id="editMaterialModal" tabindex="-1" aria-labelledby="editMaterialModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editMaterialForm">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editMaterialModalLabel">نوێکردنەوەی کاڵا</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_name" class="form-label">ناوی کاڵا</label>
              <input type="text" class="form-control" id="edit_name" name="name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_unit_type" class="form-label">جۆری یەکە</label>
              <select class="form-select" id="edit_unit_type" name="unit_type" required>
                <option value="" selected disabled>-- هەڵبژێرە --</option>
                <option value="کارتۆن">کارتۆن</option>
                <option value="دانە">دانە</option>
                <option value="بەرمیل">بەرمیل</option>
                <option value="دەبە">دەبە</option>
                <option value="لیتر">لیتر</option>
              </select>
            </div>
          </div>
          
          <!-- Conversion Fields for Edit -->
          <div id="edit_conversion_fields" style="display:none;">
            <div class="row">
              <div class="col-md-6 mb-3" id="edit_pieces_per_carton_group" style="display:none;">
                <label for="edit_pieces_per_carton" class="form-label">ژمارەی دانە لە کارتۆن</label>
                <input type="number" class="form-control" id="edit_pieces_per_carton" name="pieces_per_carton" min="1" value="1">
              </div>
              <div class="col-md-6 mb-3" id="edit_buckets_per_barrel_group" style="display:none;">
                <label for="edit_buckets_per_barrel" class="form-label">ژمارەی دەبە لە بەرمیل</label>
                <input type="number" class="form-control" id="edit_buckets_per_barrel" name="buckets_per_barrel" min="1" value="1">
              </div>
              <div class="col-md-6 mb-3" id="edit_liters_per_bucket_group" style="display:none;">
                <label for="edit_liters_per_bucket" class="form-label">ژمارەی لیتر لە دەبە</label>
                <input type="number" class="form-control" id="edit_liters_per_bucket" name="liters_per_bucket" min="0.01" step="0.01" value="1">
              </div>
              <div class="col-md-6 mb-3" id="edit_liters_per_barrel_group" style="display:none;">
                <label for="edit_liters_per_barrel" class="form-label">کۆی لیتر لە بەرمیل</label>
                <input type="number" class="form-control" id="edit_liters_per_barrel" name="liters_per_barrel" min="0.01" step="0.01" value="1">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_quantity" class="form-label">بڕی بەردەست</label>
              <input type="number" class="form-control" id="edit_quantity" name="quantity" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_currency_type" class="form-label">جۆری دراو</label>
              <select class="form-select" id="edit_currency_type" name="currency_type" required>
                <option value="" selected disabled>-- هەڵبژێرە --</option>
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3" id="edit_price_usd_group" style="display:none;">
              <label for="edit_purchase_price_usd" class="form-label">نرخی کڕین بە دۆلار</label>
              <input type="number" class="form-control" id="edit_purchase_price_usd" name="purchase_price_usd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3" id="edit_price_iqd_group" style="display:none;">
              <label for="edit_purchase_price_iqd" class="form-label">نرخی کڕین بە دینار</label>
              <input type="number" class="form-control" id="edit_purchase_price_iqd" name="purchase_price_iqd" min="0" step="0.01" value="0">
            </div>
          </div>

          <!-- Calculated Prices Display for Edit -->
          <div id="edit_calculated_prices" style="display:none;">
            <div class="row">
              <div class="col-12">
                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">نرخەکانی ژمێردراو</h6>
              </div>
              <div class="col-md-4 mb-3" id="edit_price_per_piece_group" style="display:none;">
                <label class="form-label">نرخی دانە بە دۆلار</label>
                <input type="text" class="form-control" id="edit_price_per_piece_usd" readonly>
              </div>
              <div class="col-md-4 mb-3" id="edit_price_per_bucket_group" style="display:none;">
                <label class="form-label">نرخی دەبە بە دۆلار</label>
                <input type="text" class="form-control" id="edit_price_per_bucket_usd" readonly>
              </div>
              <div class="col-md-4 mb-3" id="edit_price_per_liter_group" style="display:none;">
                <label class="form-label">نرخی لیتر بە دۆلار</label>
                <input type="text" class="form-control" id="edit_price_per_liter_usd" readonly>
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

<!-- Sell Material Modal -->
<div class="modal fade" id="sellMaterialModal" tabindex="-1" aria-labelledby="sellMaterialModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="sellMaterialForm">
        <input type="hidden" name="material_id" id="sell_material_id">
        <div class="modal-header">
          <h5 class="modal-title" id="sellMaterialModalLabel">فرۆشتنی کاڵا</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info" id="sell_material_info">
            <!-- Material info will be displayed here -->
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="buyer_type" class="form-label">جۆری کڕیار</label>
              <select class="form-select" id="buyer_type" name="buyer_type" required>
                <option value="" selected disabled>-- هەڵبژێرە --</option>
                <option value="customer">کڕیار</option>
                <option value="company">کۆمپانیا</option>
                <option value="outsider">کەسی دەرەوە</option>
              </select>
            </div>
            
            <div class="col-md-6 mb-3" id="customer_select_group" style="display:none;">
              <label for="sell_customer_id" class="form-label">ناوی کڕیار</label>
              <select class="form-select" id="sell_customer_id" name="customer_id">
                <option value="">-- هەڵبژێرە --</option>
                <!-- Options populated by JS -->
              </select>
            </div>
            
            <div class="col-md-6 mb-3" id="company_select_group" style="display:none;">
              <label for="sell_company_id" class="form-label">ناوی کۆمپانیا</label>
              <select class="form-select" id="sell_company_id" name="company_id">
                <option value="">-- هەڵبژێرە --</option>
                <!-- Options populated by JS -->
              </select>
            </div>
            
            <div class="col-md-6 mb-3" id="outsider_name_group" style="display:none;">
              <label for="outsider_name" class="form-label">ناوی کڕیار (دەرەوە)</label>
              <input type="text" class="form-control" id="outsider_name" name="outsider_name">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="sell_unit_type" class="form-label">یەکەی فرۆشتن</label>
              <select class="form-select" id="sell_unit_type" name="unit_type" required>
                <!-- Options populated based on material -->
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="sell_quantity" class="form-label">بڕی فرۆشتن</label>
              <input type="number" class="form-control" id="sell_quantity" name="quantity" min="0.01" step="0.01" required>
              <small class="text-danger" id="stock_error" style="display:none;">بڕی پێویست بەردەست نییە!</small>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="sell_currency" class="form-label">جۆری دراو</label>
              <select class="form-select" id="sell_currency" name="currency" required>
                <option value="USD">دۆلار</option>
                <option value="IQD">دینار</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="sell_price_per_unit" class="form-label">نرخی تاک</label>
              <input type="number" class="form-control" id="sell_price_per_unit" name="price" min="0" step="0.01" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="sell_total_price" class="form-label">کۆی گشتی نرخ</label>
              <input type="number" class="form-control" id="sell_total_price" name="total_price" readonly>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="sell_date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="sell_date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="sell_note" class="form-label">تێبینی</label>
              <textarea class="form-control" id="sell_note" name="note" rows="1"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success">فرۆشتن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/table-controler.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script nonce="<?php echo $csp_nonce; ?>">
// Set permissions for JavaScript
window.hasEditPermission = <?= hasPermission('edit_material') ? 'true' : 'false' ?>;
window.hasDeletePermission = <?= hasPermission('delete_material') ? 'true' : 'false' ?>;
</script>
<script src="../assets/js/add_material/add.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/add_material/delete.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/add_material/update.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/add_material/sell.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/add_material/summary_cards.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/add_material/table_loader.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script nonce="<?php echo $csp_nonce; ?>">
$(function() {
  function togglePriceFields() {
    var val = $('#currency_type').val();
    if (val === 'دینار') {
      $('#price_usd_group').hide();
      $('#price_iqd_group').show();
      $('#purchase_price_usd').val('');
    } else if (val === 'دۆلار') {
      $('#price_iqd_group').hide();
      $('#price_usd_group').show();
      $('#purchase_price_iqd').val('');
    } else {
      $('#price_usd_group').hide();
      $('#price_iqd_group').hide();
      $('#purchase_price_usd').val('');
      $('#purchase_price_iqd').val('');
    }
  }

  function toggleUnitFields() {
    var unitType = $('#unit_type').val();
    $('#conversion_fields').hide();
    $('#pieces_per_carton_group').hide();
    $('#buckets_per_barrel_group').hide();
    $('#liters_per_bucket_group').hide();
    $('#liters_per_barrel_group').hide();
    
    if (unitType === 'کارتۆن') {
      $('#conversion_fields').show();
      $('#pieces_per_carton_group').show();
    } else if (unitType === 'بەرمیل') {
      $('#conversion_fields').show();
      $('#buckets_per_barrel_group').show();
      $('#liters_per_bucket_group').show();
      $('#liters_per_barrel_group').show();
      // Calculate total liters when barrel is selected
      calculateTotalLitersForBarrel();
    } else if (unitType === 'دەبە') {
      $('#conversion_fields').show();
      $('#liters_per_bucket_group').show();
    }
    
    calculatePrices();
  }

  function calculatePrices() {
    var unitType = $('#unit_type').val();
    var priceUsd = parseFloat($('#purchase_price_usd').val()) || 0;
    var priceIqd = parseFloat($('#purchase_price_iqd').val()) || 0;
    
    $('#calculated_prices').hide();
    $('#price_per_piece_group').hide();
    $('#price_per_bucket_group').hide();
    $('#price_per_liter_group').hide();
    
    if (unitType === 'کارتۆن' && priceUsd > 0) {
      var piecesPerCarton = parseInt($('#pieces_per_carton').val()) || 1;
      var pricePerPiece = priceUsd / piecesPerCarton;
      $('#price_per_piece_usd').val(pricePerPiece.toFixed(2));
      $('#calculated_prices').show();
      $('#price_per_piece_group').show();
    } else if (unitType === 'بەرمیل' && priceUsd > 0) {
      var bucketsPerBarrel = parseInt($('#buckets_per_barrel').val()) || 1;
      var litersPerBucket = parseFloat($('#liters_per_bucket').val()) || 1;
      var pricePerBucket = priceUsd / bucketsPerBarrel;
      var pricePerLiter = priceUsd / (bucketsPerBarrel * litersPerBucket);
      
      $('#price_per_bucket_usd').val(pricePerBucket.toFixed(2));
      $('#price_per_liter_usd').val(pricePerLiter.toFixed(2));
      $('#calculated_prices').show();
      $('#price_per_bucket_group').show();
      $('#price_per_liter_group').show();
    } else if (unitType === 'دەبە' && priceUsd > 0) {
      var litersPerBucket = parseFloat($('#liters_per_bucket').val()) || 1;
      var pricePerLiter = priceUsd / litersPerBucket;
      $('#price_per_liter_usd').val(pricePerLiter.toFixed(2));
      $('#calculated_prices').show();
      $('#price_per_liter_group').show();
    }
  }

  // Function to calculate total liters for barrel
  function calculateTotalLitersForBarrel() {
    var bucketsPerBarrel = parseInt($('#buckets_per_barrel').val()) || 1;
    var litersPerBucket = parseFloat($('#liters_per_bucket').val()) || 1;
    var totalLiters = bucketsPerBarrel * litersPerBucket;
    $('#liters_per_barrel').val(totalLiters.toFixed(2));
    calculatePrices();
  }

  // Event listeners for add modal
  $('#currency_type').on('change', togglePriceFields);
  $('#unit_type').on('change', toggleUnitFields);
  $('#purchase_price_usd, #purchase_price_iqd, #pieces_per_carton').on('input', calculatePrices);
  $('#buckets_per_barrel, #liters_per_bucket').on('input', calculateTotalLitersForBarrel);
  $('#liters_per_barrel').on('input', calculatePrices);
  togglePriceFields();
  toggleUnitFields();

  // Edit modal functions
  function toggleEditPriceFields() {
    var val = $('#edit_currency_type').val();
    if (val === 'دینار') {
      $('#edit_price_usd_group').hide();
      $('#edit_price_iqd_group').show();
      $('#edit_purchase_price_usd').val('');
    } else if (val === 'دۆلار') {
      $('#edit_price_iqd_group').hide();
      $('#edit_price_usd_group').show();
      $('#edit_purchase_price_iqd').val('');
    } else {
      $('#edit_price_usd_group').hide();
      $('#edit_price_iqd_group').hide();
      $('#edit_purchase_price_usd').val('');
      $('#edit_purchase_price_iqd').val('');
    }
  }

  function toggleEditUnitFields() {
    var unitType = $('#edit_unit_type').val();
    $('#edit_conversion_fields').hide();
    $('#edit_pieces_per_carton_group').hide();
    $('#edit_buckets_per_barrel_group').hide();
    $('#edit_liters_per_bucket_group').hide();
    $('#edit_liters_per_barrel_group').hide();
    
    if (unitType === 'کارتۆن') {
      $('#edit_conversion_fields').show();
      $('#edit_pieces_per_carton_group').show();
    } else if (unitType === 'بەرمیل') {
      $('#edit_conversion_fields').show();
      $('#edit_buckets_per_barrel_group').show();
      $('#edit_liters_per_bucket_group').show();
      $('#edit_liters_per_barrel_group').show();
      // Calculate total liters when barrel is selected
      calculateEditTotalLitersForBarrel();
    } else if (unitType === 'دەبە') {
      $('#edit_conversion_fields').show();
      $('#edit_liters_per_bucket_group').show();
    }
    
    calculateEditPrices();
  }

  function calculateEditPrices() {
    var unitType = $('#edit_unit_type').val();
    var priceUsd = parseFloat($('#edit_purchase_price_usd').val()) || 0;
    var priceIqd = parseFloat($('#edit_purchase_price_iqd').val()) || 0;
    
    $('#edit_calculated_prices').hide();
    $('#edit_price_per_piece_group').hide();
    $('#edit_price_per_bucket_group').hide();
    $('#edit_price_per_liter_group').hide();
    
    if (unitType === 'کارتۆن' && priceUsd > 0) {
      var piecesPerCarton = parseInt($('#edit_pieces_per_carton').val()) || 1;
      var pricePerPiece = priceUsd / piecesPerCarton;
      $('#edit_price_per_piece_usd').val(pricePerPiece.toFixed(2));
      $('#edit_calculated_prices').show();
      $('#edit_price_per_piece_group').show();
    } else if (unitType === 'بەرمیل' && priceUsd > 0) {
      var bucketsPerBarrel = parseInt($('#edit_buckets_per_barrel').val()) || 1;
      var litersPerBucket = parseFloat($('#edit_liters_per_bucket').val()) || 1;
      var pricePerBucket = priceUsd / bucketsPerBarrel;
      var pricePerLiter = priceUsd / (bucketsPerBarrel * litersPerBucket);
      
      $('#edit_price_per_bucket_usd').val(pricePerBucket.toFixed(2));
      $('#edit_price_per_liter_usd').val(pricePerLiter.toFixed(2));
      $('#edit_calculated_prices').show();
      $('#edit_price_per_bucket_group').show();
      $('#edit_price_per_liter_group').show();
    } else if (unitType === 'دەبە' && priceUsd > 0) {
      var litersPerBucket = parseFloat($('#edit_liters_per_bucket').val()) || 1;
      var pricePerLiter = priceUsd / litersPerBucket;
      $('#edit_price_per_liter_usd').val(pricePerLiter.toFixed(2));
      $('#edit_calculated_prices').show();
      $('#edit_price_per_liter_group').show();
    }
  }

  // Function to calculate total liters for barrel in edit modal
  function calculateEditTotalLitersForBarrel() {
    var bucketsPerBarrel = parseInt($('#edit_buckets_per_barrel').val()) || 1;
    var litersPerBucket = parseFloat($('#edit_liters_per_bucket').val()) || 1;
    var totalLiters = bucketsPerBarrel * litersPerBucket;
    $('#edit_liters_per_barrel').val(totalLiters.toFixed(2));
    calculateEditPrices();
  }

  // Event listeners for edit modal
  $('#edit_currency_type').on('change', toggleEditPriceFields);
  $('#edit_unit_type').on('change', toggleEditUnitFields);
  $('#edit_purchase_price_usd, #edit_purchase_price_iqd, #edit_pieces_per_carton').on('input', calculateEditPrices);
  $('#edit_buckets_per_barrel, #edit_liters_per_bucket').on('input', calculateEditTotalLitersForBarrel);
  $('#edit_liters_per_barrel').on('input', calculateEditPrices);

  // When opening edit modal, update fields visibility
  $(document).on('click', '.edit-btn', function() {
    setTimeout(function() {
      toggleEditPriceFields();
      toggleEditUnitFields();
    }, 100);
  });
});
</script>
</body>
</html>
