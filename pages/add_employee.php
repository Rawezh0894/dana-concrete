<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_employee')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کارمەندەکان</title>
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
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">کارمەندەکان</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی کارمەند</button>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-users card-icon"></i>
                    <h6 class="card-title">کۆی ژمارەی کارمەندان</h6>
                    <div class="fs-4 fw-bold" id="total_employees">0</div>
                    <small class="text-light">ژمارەی کارمەندان</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow card-gradient-purple card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-balance-scale card-icon"></i>
                    <h6 class="card-title">کۆی باڵانس</h6>
                    <div class="fs-4 fw-bold" id="total_balance">0</div>
                    <small class="text-light">دیناری عێراقی</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-money-bill-wave card-icon"></i>
                    <h6 class="card-title">کۆی مووچەی کارمەندان</h6>
                    <div class="fs-4 fw-bold" id="total_salary">0</div>
                    <small class="text-light">دیناری عێراقی</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-dollar-sign card-icon"></i>
                    <h6 class="card-title">نرخی ١٠٠ دۆلار</h6>
                    <div class="fs-4 fw-bold" id="dollar_rate">0</div>
                    <small class="text-light">دیناری عێراقی</small>
                    <button class="btn btn-sm btn-outline-light mt-2" id="refreshDollarRate" title="نوێکردنەوەی نرخی دۆلار">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="employeeTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>ناوی کارمەند</th>
                    <th>ژمارەی مۆبایل</th>
                    <th>ڕۆڵ</th>
                    <th>موچە (د.ع)</th>
                    <th>باڵانس (د.ع)</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Employees will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addEmployeeForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addEmployeeModalLabel">زیادکردنی کارمەند</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="employee_name" class="form-label">ناوی کارمەند</label>
            <input type="text" class="form-control" id="employee_name" name="name" required placeholder="ناوی کارمەند بنووسە">
          </div>
          <div class="mb-3">
            <label for="employee_mobile" class="form-label">ژمارەی مۆبایل</label>
            <input type="text" class="form-control" id="employee_mobile" name="mobile" required placeholder="ژمارەی مۆبایل بنووسە">
          </div>
          <div class="mb-3">
            <label for="employee_role" class="form-label">ڕۆڵ</label>
            <select class="form-select" id="employee_role" name="role" required>
              <option value="">-- هەلبژێرە --</option>
              <option value="شۆفێر">شۆفێر</option>
              <option value="موحاسیب">موحاسیب</option>
              <option value="وەکیل">وەکیل</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="employee_salary" class="form-label">موچە</label>
            <input type="number" class="form-control" id="employee_salary" name="salary" min="0" step="0.01" required placeholder="موچە بنووسە" value="0">
          </div>
          <div class="mb-3">
            <label for="opening_balance" class="form-label">باڵانسی سەرەتا (د.ع)</label>
            <input type="number" class="form-control" id="opening_balance" name="opening_balance" step="0.01" value="0" placeholder="ئەگەر لەسەر کۆمپانیا ماوە (+) ، ئەگەر قەرزی کۆمپانیایە (-)">
            <small class="text-muted">(+): کۆمپانیا قەرزاری کارمەندە | (-): کارمەند قەرزاری کۆمپانیا</small>
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
<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editEmployeeForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editEmployeeModalLabel">دەستکاری کارمەند</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_employee_id" name="id">
          <div class="mb-3">
            <label for="edit_employee_name" class="form-label">ناوی کارمەند</label>
            <input type="text" class="form-control" id="edit_employee_name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="edit_employee_mobile" class="form-label">ژمارەی مۆبایل</label>
            <input type="text" class="form-control" id="edit_employee_mobile" name="mobile" required>
          </div>
          <div class="mb-3">
            <label for="edit_employee_role" class="form-label">ڕۆڵ</label>
            <select class="form-select" id="edit_employee_role" name="role" required>
              <option value="شۆفێر">شۆفێر</option>
              <option value="موحاسیب">موحاسیب</option>
              <option value="وەکیل">وەکیل</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_employee_salary" class="form-label">موچە</label>
            <input type="number" class="form-control" id="edit_employee_salary" name="salary" min="0" step="0.01" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background:var(--seafoam-green); color:white;">نوێکردنەوە</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/employee/add_employee.js"></script>
<script src="../assets/js/employee/select_employee.js"></script>
<script src="../assets/js/employee/update_employee.js"></script>
<script src="../assets/js/employee/delete_employee.js"></script>
<script src="../assets/js/employee/dollar_rate.js"></script>
</body>
</html>
