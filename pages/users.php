<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_users')) {
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
    <title>بەکارهێنەران</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .nav-tabs .nav-link.active {
            background: var(--seafoam-green);
            color: #fff !important;
            font-weight: bold;
        }
        .nav-tabs .nav-link {
            color: var(--seafoam-green);
            font-weight: bold;
        }
        .perm-table th, .perm-table td {
            vertical-align: middle;
        }
    </style>
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <ul class="nav nav-tabs mb-4" id="userTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#usersTabPane" type="button" role="tab" aria-controls="usersTabPane" aria-selected="true">
                بەکارهێنەران
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="perms-tab" data-bs-toggle="tab" data-bs-target="#permsTabPane" type="button" role="tab" aria-controls="permsTabPane" aria-selected="false">
                دەسەڵاتەکان
            </button>
        </li>
    </ul>
    <div class="tab-content" id="userTabsContent">
        <!-- Users Tab -->
        <div class="tab-pane fade show active" id="usersTabPane" role="tabpanel" aria-labelledby="users-tab">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">بەکارهێنەران</h2>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی بەکارهێنەر</button>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4" id="summary-cards">
                <div class="col-md-4 mb-3">
                    <div class="card text-center shadow  card-gradient-info card-animate-hover">
                        <div class="card-body">
                            <i class="fas fa-users card-icon"></i>
                            <h6 class="card-title">کۆی بەکارهێنەران</h6>
                            <div class="fs-4 fw-bold" id="total-users">0</div>
                            <small class="text-light">ژمارەی هەموو بەکارهێنەران</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center shadow  card-gradient-success card-animate-hover">
                        <div class="card-body">
                            <i class="fas fa-user-shield card-icon"></i>
                            <h6 class="card-title">ئەدمینەکان</h6>
                            <div class="fs-4 fw-bold" id="total-admins">0</div>
                            <small class="text-light">ژمارەی ئەدمینەکان</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                        <div class="card-body">
                            <i class="fas fa-user-tie card-icon"></i>
                            <h6 class="card-title">بەڕێوەبەران</h6>
                            <div class="fs-4 fw-bold" id="total-managers">0</div>
                            <small class="text-light">ژمارەی بەڕێوەبەران</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center" id="usersTable">
                    <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                        <tr>
                            <th>#</th>
                            <th>ناوی بەکارهێنەر</th>
                            <th>دەسەڵات</th>
                            <th>کردارەکان</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Users will be loaded here by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Permissions Tab -->
        <div class="tab-pane fade" id="permsTabPane" role="tabpanel" aria-labelledby="perms-tab">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">دەسەڵاتەکان</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered perm-table align-middle text-center" id="permissionsTable">
                    <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                        <tr>
                            <th>ناوی دەسەڵات</th>
                            <th>ڕوونکردنەوە</th>
                            <th>Admin</th>
                            <th>User</th>
                            <th>Accountant</th>
                            <th>Manager</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Permissions will be loaded here by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addUserForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addUserModalLabel">زیادکردنی بەکارهێنەر</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="addUsername" class="form-label">ناوی بەکارهێنەر</label>
            <input type="text" class="form-control" id="addUsername" name="username" required>
          </div>
          <div class="mb-3">
            <label for="addPassword" class="form-label">وشەی نهێنی</label>
            <input type="password" class="form-control" id="addPassword" name="password" required>
          </div>
          <div class="mb-3">
            <label for="addRole" class="form-label">دەسەڵات</label>
            <select class="form-select" id="addRole" name="role" required>
              <option value="user">بەکارهێنەر</option>
              <option value="admin">ئەدمین</option>
              <option value="accountant">موحاسیب</option>
              <option value="manager">بەڕێوەبەر</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success" style="background: var(--lime-green); font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit User Modal (to be filled by JS) -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editUserForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editUserModalLabel">دەستکاری بەکارهێنەر</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editUserId" name="id">
          <div class="mb-3">
            <label for="editUsername" class="form-label">ناوی بەکارهێنەر</label>
            <input type="text" class="form-control" id="editUsername" name="username" required>
          </div>
          <div class="mb-3">
            <label for="editPassword" class="form-label">وشەی نهێنی (نوێکردنەوە)</label>
            <input type="password" class="form-control" id="editPassword" name="password" placeholder="ئەگەر بەتاڵ بێت وشەی نهێنی گۆڕنەکراوە">
          </div>
          <div class="mb-3">
            <label for="editRole" class="form-label">دەسەڵات</label>
            <select class="form-select" id="editRole" name="role" required>
              <option value="user">بەکارهێنەر</option>
              <option value="admin">ئەدمین</option>
              <option value="accountant">موحاسیب</option>
              <option value="manager">بەڕێوەبەر</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background: var(--seafoam-green); color:white;">نوێکردنەوە</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/user/add_user.js"></script>
<script src="../assets/js/user/select_user.js"></script>
<script src="../assets/js/user/update_user.js"></script>
<script src="../assets/js/user/delete_user.js"></script>
<script src="../assets/js/permissions/permissions.js"></script>
</body>
</html>
