<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_assets')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// Get asset categories
$categories = $pdo->query("SELECT id, name FROM asset_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی ئامێرەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <!-- AG Grid CSS -->
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css" rel="stylesheet">
    <link href="../assets/css/comon/ag_grid.css" rel="stylesheet">
    
    <style>
        .asset-card {
            border-left: 4px solid var(--seafoam-green);
            transition: all 0.3s ease;
        }
        .asset-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .depreciation-badge {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">بەڕێوەبردنی ئامێرەکان</h2>
        <div class="d-flex gap-2">
            <a href="depreciation.php" class="btn btn-warning" style="color: white; font-weight: bold;">
                <i class="fas fa-chart-line me-1"></i>بەڕێوەبردنی داخوران
            </a>
            <?php if (hasPermission('add_assets')): ?>
            <button class="btn" data-bs-toggle="modal" data-bs-target="#addAssetModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">
                <i class="fas fa-plus me-1"></i>زیادکردنی ئامێر
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-tools card-icon"></i>
                    <h6 class="card-title">کۆی ئامێرەکان</h6>
                    <div class="fs-4 fw-bold" id="total-assets">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-dollar-sign card-icon"></i>
                    <h6 class="card-title">کۆی نرخی ئامێرەکان</h6>
                    <div class="fs-4 fw-bold" id="total-value">$0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-chart-line-down card-icon"></i>
                    <h6 class="card-title">کۆی داخوران</h6>
                    <div class="fs-4 fw-bold" id="total-depreciation">$0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow card-gradient-danger card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-book card-icon"></i>
                    <h6 class="card-title">کۆی نرخی کتێب</h6>
                    <div class="fs-4 fw-bold" id="total-book-value">$0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-3">
            <label for="filter_category">جۆر:</label>
            <select class="form-select" id="filter_category">
                <option value="">هەموو جۆرەکان</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="filter_status">دۆخ:</label>
            <select class="form-select" id="filter_status">
                <option value="">هەموو دۆخەکان</option>
                <option value="active">چالاک</option>
                <option value="inactive">ناچالاک</option>
                <option value="disposed">فڕێدراو</option>
                <option value="under_maintenance">لە چاککردندا</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-secondary" id="clearFilterBtn" type="button">پاککردنەوە</button>
        </div>
    </div>
    
    <!-- AG Grid Container -->
    <div class="table-responsive">
        <div id="assetsGrid" class="ag-theme-alpine"></div>
    </div>
</div>

<!-- Add Asset Modal -->
<div class="modal fade" id="addAssetModal" tabindex="-1" aria-labelledby="addAssetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addAssetForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addAssetModalLabel">زیادکردنی ئامێر</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="asset_code" class="form-label">کۆدی ئامێر <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="asset_code" name="asset_code" required>
              <small class="text-muted">بۆ نموونە: PUMP-001, MIXER-001</small>
            </div>
            <div class="col-md-6 mb-3">
              <label for="asset_name" class="form-label">ناوی ئامێر <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="asset_name" name="asset_name" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="category_id" class="form-label">جۆر <span class="text-danger">*</span></label>
              <select class="form-select" id="category_id" name="category_id" required>
                <option value="">هەڵبژێرە</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>" data-method="<?= $cat['default_depreciation_method'] ?>" data-life="<?= $cat['default_useful_life_years'] ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="serial_number" class="form-label">ژمارەی سیریاڵ</label>
              <input type="text" class="form-control" id="serial_number" name="serial_number">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="purchase_date" class="form-label">بەرواری کڕین <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="purchase_date" name="purchase_date" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="purchase_cost" class="form-label">نرخی کڕین <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="purchase_cost" name="purchase_cost" min="0" step="0.01" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="salvage_value" class="form-label">نرخی کۆتایی</label>
              <input type="number" class="form-control" id="salvage_value" name="salvage_value" min="0" step="0.01" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="depreciation_method" class="form-label">شێوازی داخوران <span class="text-danger">*</span></label>
              <select class="form-select" id="depreciation_method" name="depreciation_method" required>
                <option value="straight_line">ساڵانە بە یەکسان (Straight Line)</option>
                <option value="declining_balance">کەمبوونەوەی بیلانس (Declining Balance)</option>
                <option value="units_of_production">بەپێی بەرهەم (Units of Production)</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="useful_life_years" class="form-label">ماوەی بەکارهێنان (ساڵ) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="useful_life_years" name="useful_life_years" min="1" step="1" required>
            </div>
            <div class="col-md-4 mb-3" id="depreciation_rate_container" style="display:none;">
              <label for="depreciation_rate" class="form-label">ڕێژەی داخوران (%)</label>
              <input type="number" class="form-control" id="depreciation_rate" name="depreciation_rate" min="0" max="100" step="0.01">
              <small class="text-muted">بۆ declining balance</small>
            </div>
            <div class="col-md-4 mb-3" id="useful_life_units_container" style="display:none;">
              <label for="useful_life_units" class="form-label">ماوەی بەکارهێنان (یەکە)</label>
              <input type="number" class="form-control" id="useful_life_units" name="useful_life_units" min="0" step="0.01">
              <small class="text-muted">بۆ units of production</small>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="location" class="form-label">شوێن</label>
              <input type="text" class="form-control" id="location" name="location">
            </div>
            <div class="col-md-6 mb-3">
              <label for="supplier" class="form-label">دابینکەر</label>
              <input type="text" class="form-control" id="supplier" name="supplier">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="warranty_expiry" class="form-label">بەرواری بەسەرچوونی گارانتی</label>
              <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry">
            </div>
            <div class="col-md-6 mb-3">
              <label for="status" class="form-label">دۆخ</label>
              <select class="form-select" id="status" name="status">
                <option value="active">چالاک</option>
                <option value="inactive">ناچالاک</option>
                <option value="under_maintenance">لە چاککردندا</option>
              </select>
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

<!-- Edit Asset Modal -->
<div class="modal fade" id="editAssetModal" tabindex="-1" aria-labelledby="editAssetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editAssetForm">
        <input type="hidden" id="edit_asset_id" name="asset_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editAssetModalLabel">دەستکاری ئامێر</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_asset_code" class="form-label">کۆدی ئامێر <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit_asset_code" name="asset_code" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_asset_name" class="form-label">ناوی ئامێر <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit_asset_name" name="asset_name" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_category_id" class="form-label">جۆر <span class="text-danger">*</span></label>
              <select class="form-select" id="edit_category_id" name="category_id" required>
                <option value="">هەڵبژێرە</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_serial_number" class="form-label">ژمارەی سیریاڵ</label>
              <input type="text" class="form-control" id="edit_serial_number" name="serial_number">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="edit_purchase_date" class="form-label">بەرواری کڕین <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="edit_purchase_date" name="purchase_date" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_purchase_cost" class="form-label">نرخی کڕین <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="edit_purchase_cost" name="purchase_cost" min="0" step="0.01" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_salvage_value" class="form-label">نرخی کۆتایی</label>
              <input type="number" class="form-control" id="edit_salvage_value" name="salvage_value" min="0" step="0.01">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_location" class="form-label">شوێن</label>
              <input type="text" class="form-control" id="edit_location" name="location">
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_supplier" class="form-label">دابینکەر</label>
              <input type="text" class="form-control" id="edit_supplier" name="supplier">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_warranty_expiry" class="form-label">بەرواری بەسەرچوونی گارانتی</label>
              <input type="date" class="form-control" id="edit_warranty_expiry" name="warranty_expiry">
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_status" class="form-label">دۆخ</label>
              <select class="form-select" id="edit_status" name="status">
                <option value="active">چالاک</option>
                <option value="inactive">ناچالاک</option>
                <option value="disposed">فڕێدراو</option>
                <option value="under_maintenance">لە چاککردندا</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12 mb-3">
              <label for="edit_notes" class="form-label">تێبینی</label>
              <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
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
<!-- AG Grid JS -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js"></script>
<script src="../assets/js/comon/ag_grid_base.js"></script>
<script>
    // Pass permissions to JavaScript
    window.userPermissions = {
      canAdd: <?php echo hasPermission('add_assets') ? 'true' : 'false'; ?>,
      canEdit: <?php echo hasPermission('update_assets') ? 'true' : 'false'; ?>,
      canDelete: <?php echo hasPermission('delete_assets') ? 'true' : 'false'; ?>
    };
</script>
<script src="../assets/js/assets/assets.js"></script>
<script src="../assets/js/assets/ag_grid_assets.js"></script>
<script src="../assets/js/assets/add_asset.js"></script>
<script src="../assets/js/assets/update_asset.js"></script>
<script src="../assets/js/assets/delete_asset.js"></script>
<script src="../assets/js/assets/summary_cards.js"></script>

</body>
</html>
