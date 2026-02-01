<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

// Check permission (optional, uncomment if you implement permission system for this)
// if (!hasPermission('view_other_income')) {
//     echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
//         .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
//         .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
//         .'</div>';
//     exit;
// }

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
    <title>داهاتی تر</title>
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
    <style>
        /* Override global min-width from ag_grid.css */
        #otherIncomeGrid .ag-cell, 
        #otherIncomeGrid .ag-header-cell {
            min-width: 50px !important;
        }
        .ag-theme-alpine {
            --ag-column-min-width: 50px !important;
        }
    </style>
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>داهاتی تر</h3>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addIncomeModal" style="background: var(--seafoam-green); font-weight: bold;">
            <i class="fas fa-plus me-2"></i>زیادکردنی داهاتی تر
        </button>
    </div>

    <!-- Stats Cards (Optional) -->
    <div class="row w-100 mt-3 g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                <div class="card-body">
                    <h6 class="card-title">کۆی داهات بە دینار</h6>
                    <div id="totalIncomeIQD" class="fs-4 fw-bold">0 د.ع</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #ffc107, #ffca2c); color: #333;">
                <div class="card-body">
                    <h6 class="card-title">کۆی داهات بە دۆلار</h6>
                    <div id="totalIncomeUSD" class="fs-4 fw-bold">$0</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div id="otherIncomeGrid" class="ag-grid-container ag-theme-alpine" style="height: 600px; width: 100%;"></div>
        </div>
    </div>
</div>

<!-- Add Income Modal -->
<div class="modal fade" id="addIncomeModal" tabindex="-1" aria-labelledby="addIncomeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addIncomeForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addIncomeModalLabel">زیادکردنی داهاتی تر</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="description" class="form-label">وەسف (تێبینی)</label>
            <textarea class="form-control" id="description" name="description" rows="3" required placeholder="وردەکاری داهات بنووسە..."></textarea>
          </div>
          
          <div class="mb-3">
            <label for="currency" class="form-label">جۆری دراو</label>
            <select class="form-select" id="currency" name="currency" required>
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="amount" class="form-label">بڕ</label>
            <div class="input-group">
                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required placeholder="0.00">
                <span class="input-group-text" id="currency-addon">د.ع</span>
            </div>
          </div>

          <div class="mb-3">
            <label for="date" class="form-label">بەروار</label>
            <input type="date" class="form-control" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success" style="background: var(--lime-green); font-weight: bold;">تۆمارکردن</button>
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
<script src="../assets/js/other_income/add_income.js"></script>
<script src="../assets/js/other_income/other_income.js"></script>

<script>
    // Simple script to update currency label
    document.getElementById('currency').addEventListener('change', function() {
        const addon = document.getElementById('currency-addon');
        if (this.value === 'دینار') {
            addon.textContent = 'د.ع';
        } else {
            addon.textContent = '$';
        }
    });
</script>

</body>
</html>
