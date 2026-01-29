<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!hasPermission('view_person_other_expenses')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
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
    <title>پڕۆفایلی کەسانی خەرجی تر</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">کەسانی خەرجی تر</h2>
        <div class="d-flex gap-2 align-items-center">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="filterDebtOnly" style="cursor: pointer;">
                <label class="form-check-label" for="filterDebtOnly" style="cursor: pointer; user-select: none;">
                    <i class="fas fa-filter me-1"></i>تەنها کەسانی قەرزدار
                </label>
            </div>
            <button class="btn btn-warning" onclick="exportDebtorsToExcel()" style="font-weight: bold;" title="ئیکسپۆرتی کەسانی قەرزدار بۆ Excel">
                <i class="fas fa-file-excel me-1"></i>ئیکسپۆرتی Excel
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPersonModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی کەس</button>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card summary-card card-gradient-success card-animate-hover card-shadow-medium card-rounded">
                <div class="card-body text-center">
                    <div class="card-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h5 class="card-title">کۆی قەرزی ئێمە بە دۆلار</h5>
                    <h3 class="card-value" id="totalDebtUSD">$0.00</h3>
                    <div class="card-details">
                        <small>خەرجی تر: <span id="otherExpensesUSD">$0.00</span></small><br>
                        <small>کڕینی کاڵا: <span id="purchaseMaterialsUSD">$0.00</span></small><br>
                        <small>قەرزی سەرەتایی: <span id="personsOpeningUSD">$0.00</span></small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card summary-card card-gradient-warning card-animate-hover card-shadow-medium card-rounded">
                <div class="card-body text-center">
                    <div class="card-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h5 class="card-title">کۆی قەرزی ئێمە بە دینار</h5>
                    <h3 class="card-value" id="totalDebtIQD">0 دینار</h3>
                    <div class="card-details">
                        <small>خەرجی تر: <span id="otherExpensesIQD">0 دینار</span></small><br>
                        <small>کڕینی کاڵا: <span id="purchaseMaterialsIQD">0 دینار</span></small><br>
                        <small>قەرزی سەرەتایی: <span id="personsOpeningIQD">0 دینار</span></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="personTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>ناوی کەس</th>
                    <!--<th>خەرجی بە دۆلار</th>-->
                    <!--<th>خەرجی بە دینار</th>-->
                    <th>قەرزی سەرەتایی (دۆلار)</th>
                    <th>قەرزی سەرەتایی (دینار)</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Persons will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Person Modal -->
<div class="modal fade" id="addPersonModal" tabindex="-1" aria-labelledby="addPersonModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addPersonForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addPersonModalLabel">زیادکردنی کەس</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="person_name" class="form-label">ناوی کەس</label>
            <input type="text" class="form-control" id="person_name" name="person_name" required>
          </div>
          <div class="mb-3">
            <label for="person_expense_usd" class="form-label d-none">خەرجی بە دۆلار</label>
            <input type="number" step="0.01" class="form-control d-none" id="person_expense_usd" name="expense_usd" value="0" readonly>
          </div>
          <div class="mb-3">
            <label for="person_expense_iqd" class="form-label d-none">خەرجی بە دینار</label>
            <input type="number" step="0.01" class="form-control d-none" id="person_expense_iqd" name="expense_iqd" value="0" readonly>
          </div>
          <div class="mb-3">
            <label for="person_opening_debt_usd" class="form-label">قەرزی سەرەتایی بە دۆلار</label>
            <input type="number" step="0.01" class="form-control" id="person_opening_debt_usd" name="opening_debt_usd" value="0">
          </div>
          <div class="mb-3">
            <label for="person_opening_debt_iqd" class="form-label">قەرزی سەرەتایی بە دینار</label>
            <input type="number" step="0.01" class="form-control" id="person_opening_debt_iqd" name="opening_debt_iqd" value="0">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success" style="background: var(--seafoam-green); font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/table-controler.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/person_other_expenses/select_person.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/person_other_expenses/add_person.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/person_other_expenses/update_person.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/person_other_expenses/delete_person.js" nonce="<?php echo $csp_nonce; ?>"></script>
</body>
</html>
