<?php
session_start();
require_once '../config/db_conected.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
// require_once '../config/permissions.php';
// if (!hasPermission('view_materials')) {
//     echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
//         .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
//         .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
//         .'</div>';
//     exit;
// }
// Fetch bins for dropdown
$bins = $pdo->query('SELECT id, name FROM bins_silos ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گۆڕانکارییەکانی ستۆک</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- jQuery (پێش هەموو شت) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">گۆڕانکارییەکانی ستۆک</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAdjustmentModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی گۆڕانکاری</button>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="adjustmentsTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>بنکە</th>
                    <th>بڕ</th>
                    <th>هۆکار</th>
                    <th>نرخی دۆلار</th>
                    <th>نرخی دینار</th>
                    <th>بەکارهێنەر</th>
                    <th>بەروار</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Adjustments will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Adjustment Modal -->
<div class="modal fade" id="addAdjustmentModal" tabindex="-1" aria-labelledby="addAdjustmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addAdjustmentForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addAdjustmentModalLabel">زیادکردنی گۆڕانکاری ستۆک</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="bin_id" class="form-label">بنکە</label>
            <select class="form-select" id="bin_id" name="bin_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach($bins as $bin): ?>
                <option value="<?= $bin['id'] ?>"><?= htmlspecialchars($bin['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="adjustment" class="form-label">بڕ بە کیلۆ</label>
            <input type="number" class="form-control" id="adjustment" name="adjustment" step="0.01" required placeholder="بڕی گۆڕانکاری بنووسە">
          </div>
          <div class="mb-3">
            <label for="reason" class="form-label">هۆکار</label>
            <input type="text" class="form-control" id="reason" name="reason" required placeholder="هۆکار">
          </div>
          <div class="mb-3">
            <label class="form-label">نرخی گۆڕانکاری</label>
            <div class="input-group mb-2">
              <span class="input-group-text">دۆلار</span>
              <input type="number" class="form-control" id="price_usd" name="price_usd" step="0.01" placeholder="نرخی دۆلار">
            </div>
            <div class="input-group">
              <span class="input-group-text">دینار</span>
              <input type="number" class="form-control" id="price_iqd" name="price_iqd" step="0.01" placeholder="نرخی دینار">
            </div>
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
<!-- Edit/Delete modals can be added similarly if needed -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/stock_adjustments/select.js"></script>
<script src="../assets/js/stock_adjustments/add.js"></script>
<script src="../assets/js/stock_adjustments/delete.js"></script>
</body>
</html>
