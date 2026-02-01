<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داخورانی ئامێرەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- AG Grid CSS -->
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css" rel="stylesheet">
    <link href="../assets/css/comon/ag_grid.css" rel="stylesheet">

    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>داخورانی ئامێرەکان (Straight-Line Method)</h3>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addDepreciationModal" style="font-weight: bold;">
            <i class="fas fa-plus me-2"></i>زیادکردنی داخوران
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="row w-100 mt-3 g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #dc3545, #f8d7da); color: #721c24;">
                <div class="card-body">
                    <h6 class="card-title">کۆی داخوران بە دینار</h6>
                    <div id="totalDepreciationIQD" class="fs-4 fw-bold">0 د.ع</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #ffc107, #fff3cd); color: #856404;">
                <div class="card-body">
                    <h6 class="card-title">کۆی داخوران بە دۆلار</h6>
                    <div id="totalDepreciationUSD" class="fs-4 fw-bold">$0</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div id="depreciationGrid" class="ag-grid-container ag-theme-alpine" style="height: 600px; width: 100%;"></div>
        </div>
    </div>
</div>

<!-- Add Depreciation Modal -->
<div class="modal fade" id="addDepreciationModal" tabindex="-1" aria-labelledby="addDepreciationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addDepreciationForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addDepreciationModalLabel">زیادکردنی داخوران</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="date" class="form-label">بەروار</label>
            <input type="date" class="form-control" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
          </div>
          
          <div class="mb-3">
            <label for="amount_iqd" class="form-label">بڕ بە دینار</label>
            <div class="input-group">
                <input type="number" step="0.01" class="form-control" id="amount_iqd" name="amount_iqd" required placeholder="0.00">
                <span class="input-group-text">د.ع</span>
            </div>
          </div>

          <div class="mb-3">
            <label for="amount_usd" class="form-label">بڕ بە دۆلار</label>
            <div class="input-group">
                <input type="number" step="0.01" class="form-control" id="amount_usd" name="amount_usd" required placeholder="0.00">
                <span class="input-group-text">$</span>
            </div>
          </div>

          <div class="mb-3">
            <label for="note" class="form-label">تێبینی</label>
            <textarea class="form-control" id="note" name="note" rows="3" placeholder="وردەکاری داخوران بنووسە..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-danger" style="font-weight: bold;">تۆمارکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Depreciation Modal -->
<div class="modal fade" id="editDepreciationModal" tabindex="-1" aria-labelledby="editDepreciationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editDepreciationForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editDepreciationModalLabel">دەستکاری داخوران</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_id" name="id">
          <div class="mb-3">
            <label for="edit_date" class="form-label">بەروار</label>
            <input type="date" class="form-control" id="edit_date" name="date" required>
          </div>
          
          <div class="mb-3">
            <label for="edit_amount_iqd" class="form-label">بڕ بە دینار</label>
            <div class="input-group">
                <input type="number" step="0.01" class="form-control" id="edit_amount_iqd" name="amount_iqd" required>
                <span class="input-group-text">د.ع</span>
            </div>
          </div>

          <div class="mb-3">
            <label for="edit_amount_usd" class="form-label">بڕ بە دۆلار</label>
            <div class="input-group">
                <input type="number" step="0.01" class="form-control" id="edit_amount_usd" name="amount_usd" required>
                <span class="input-group-text">$</span>
            </div>
          </div>

          <div class="mb-3">
            <label for="edit_note" class="form-label">تێبینی</label>
            <textarea class="form-control" id="edit_note" name="note" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-primary" style="font-weight: bold;">پاشکەوتکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- AG Grid JS -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js"></script>

<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/asset_depreciation/add_depreciation.js"></script>
<script src="../assets/js/asset_depreciation/update_depreciation.js"></script>
<script src="../assets/js/asset_depreciation/delete_depreciation.js"></script>
<script src="../assets/js/asset_depreciation/asset_depreciation.js"></script>

</body>
</html>
