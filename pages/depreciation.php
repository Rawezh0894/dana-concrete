<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_depreciation')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// Get assets
$assets = $pdo->query("SELECT id, asset_code, name FROM assets WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی داخوران</title>
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
        .depreciation-card {
            border-left: 4px solid var(--warning);
            transition: all 0.3s ease;
        }
        .depreciation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">بەڕێوەبردنی داخوران</h2>
        <div class="d-flex gap-2">
            <a href="assets.php" class="btn btn-info" style="color: white; font-weight: bold;">
                <i class="fas fa-tools me-1"></i>بەڕێوەبردنی ئامێرەکان
            </a>
            <?php if (hasPermission('calculate_depreciation')): ?>
            <button class="btn" data-bs-toggle="modal" data-bs-target="#calculateDepreciationModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">
                <i class="fas fa-calculator me-1"></i>ژمێریاری داخوران
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-3">
            <label for="filter_asset">ئامێر:</label>
            <select class="form-select" id="filter_asset">
                <option value="">هەموو ئامێرەکان</option>
                <?php foreach ($assets as $asset): ?>
                    <option value="<?= $asset['id'] ?>"><?= htmlspecialchars($asset['asset_code'] . ' - ' . $asset['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="filter_posted">دۆخ:</label>
            <select class="form-select" id="filter_posted">
                <option value="">هەموو</option>
                <option value="0">نەپۆستکراو</option>
                <option value="1">پۆستکراو</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-secondary" id="clearFilterBtn" type="button">پاککردنەوە</button>
        </div>
    </div>
    
    <!-- AG Grid Container -->
    <div class="table-responsive">
        <div id="depreciationGrid" class="ag-theme-alpine"></div>
    </div>
</div>

<!-- Calculate Depreciation Modal -->
<div class="modal fade" id="calculateDepreciationModal" tabindex="-1" aria-labelledby="calculateDepreciationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="calculateDepreciationForm">
        <div class="modal-header">
          <h5 class="modal-title" id="calculateDepreciationModalLabel">ژمێریاری داخوران</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="dep_asset_id" class="form-label">ئامێر <span class="text-danger">*</span></label>
              <select class="form-select" id="dep_asset_id" name="asset_id" required>
                <option value="">هەڵبژێرە</option>
                <?php foreach ($assets as $asset): ?>
                  <option value="<?= $asset['id'] ?>"><?= htmlspecialchars($asset['asset_code'] . ' - ' . $asset['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label for="period_start" class="form-label">بەرواری دەستپێکردن <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="period_start" name="period_start" required>
            </div>
            <div class="col-md-3 mb-3">
              <label for="period_end" class="form-label">بەرواری کۆتایی <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="period_end" name="period_end" required>
            </div>
          </div>
          <div class="row" id="units_used_container" style="display:none;">
            <div class="col-md-6 mb-3">
              <label for="units_used" class="form-label">یەکەکانی بەکارهاتوو <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="units_used" name="units_used" min="0" step="0.01">
              <small class="text-muted">تەنها بۆ شێوازی بەپێی بەرهەم</small>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12 mb-3">
              <label for="dep_notes" class="form-label">تێبینی</label>
              <textarea class="form-control" id="dep_notes" name="notes" rows="2"></textarea>
            </div>
          </div>
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>تێبینی:</strong> داخوران بە شێوەیەکی خۆکار ژمێریاری دەکرێت بەپێی شێوازی داخورانی ئامێرەکە.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">ژمێریاری</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<!-- AG Grid JS -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js"></script>
<script src="../assets/js/comon/ag_grid_base.js"></script>
<script>
    // Pass permissions to JavaScript
    window.userPermissions = {
      canCalculate: <?php echo hasPermission('calculate_depreciation') ? 'true' : 'false'; ?>,
      canPost: <?php echo hasPermission('post_depreciation') ? 'true' : 'false'; ?>
    };
</script>
<script src="../assets/js/depreciation/depreciation.js"></script>
<script src="../assets/js/depreciation/ag_grid_depreciation.js"></script>
<script src="../assets/js/depreciation/calculate_depreciation.js"></script>

</body>
</html>
