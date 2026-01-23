<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_bins_silos')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
if (!hasPermission('view_bins_silos')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
// Note: add_bins_silos permission is checked in the UI, not here
// Users with only view_bins_silos permission can still access the page
$bins = $pdo->query("SELECT * FROM bins_silos")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بین/سایلۆکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
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
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">بین/سایلۆکان</h2>
        <?php if (hasPermission('add_material')): ?>
        <button class="btn" data-bs-toggle="modal" data-bs-target="#addBinModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردن</button>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="binsTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>ناو</th>
                    <th>جۆر</th>
                    <th>جۆری مەواد</th>
                    <th>بڕ</th>
                    <th>کۆی نرخ</th>
                    <th>نرخی مامناوەند</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($bins) === 0): ?>
                <tr>
                    <td colspan="8">هیچ داتایەک نییە</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($bins as $i => $bin): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($bin['name']) ?></td>
                        <td><?= htmlspecialchars($bin['type']) ?></td>
                        <td><?= htmlspecialchars($bin['material_type']) ?></td>
                        <td><?= htmlspecialchars($bin['amount']) ?></td>
                        <td><?= htmlspecialchars($bin['total_value']) ?></td>
                        <td><?= htmlspecialchars($bin['average_price']) ?></td>
                        <td>
                            <?php if (hasPermission('edit_material')): ?>
                            <button class="btn btn-sm btn-primary edit-btn" data-id="<?= $bin['id'] ?>" data-name="<?= htmlspecialchars($bin['name']) ?>" data-type="<?= htmlspecialchars($bin['type']) ?>" data-material_type="<?= htmlspecialchars($bin['material_type']) ?>" data-amount="<?= htmlspecialchars($bin['amount']) ?>" data-total_value="<?= htmlspecialchars($bin['total_value']) ?>" data-average_price="<?= htmlspecialchars($bin['average_price']) ?>" aria-label="نوێکردنەوە"><i class="bi bi-pencil"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Add Bin Modal -->
<div class="modal fade" id="addBinModal" tabindex="-1" aria-labelledby="addBinModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addBinForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addBinModalLabel">زیادکردنی بین/سایلۆ</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="name" class="form-label">ناو</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="type" class="form-label">جۆر</label>
            <select class="form-select" id="type" name="type" required>
              <option value="">-- هەڵبژێرە --</option>
              <option value="چاو">چاو</option>
              <option value="سایلۆ">سایلۆ</option>
              <option value="تەنکی">تەنکی</option>
              <option value="عەمبار">عەمبار</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="material_type" class="form-label">جۆری مەواد</label>
            <input type="text" class="form-control" id="material_type" name="material_type" required>
          </div>
          <div class="mb-3">
            <label for="amount" class="form-label">بڕ</label>
            <input type="number" class="form-control" id="amount" name="amount" min="0" step="0.01" required>
          </div>
          <div class="mb-3">
            <label for="average_price" class="form-label">نرخی مامناوەند</label>
            <input type="number" class="form-control" id="average_price" name="average_price" min="0" step="0.0000001" required>
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
<!-- Edit Bin Modal -->
<div class="modal fade" id="editBinModal" tabindex="-1" aria-labelledby="editBinModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editBinForm">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editBinModalLabel">نوێکردنەوەی بین/سایلۆ</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_name" class="form-label">ناو</label>
            <input type="text" class="form-control" id="edit_name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="edit_type" class="form-label">جۆر</label>
            <select class="form-select" id="edit_type" name="type" required>
              <option value="">-- هەڵبژێرە --</option>
              <option value="چاو">چاو</option>
              <option value="سایلۆ">سایلۆ</option>
              <option value="تەنکی">تەنکی</option>
              <option value="عەمبار">عەمبار</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_material_type" class="form-label">جۆری مەواد</label>
            <input type="text" class="form-control" id="edit_material_type" name="material_type" required>
          </div>
          <div class="mb-3">
            <label for="edit_amount" class="form-label">بڕ</label>
            <input type="number" class="form-control" id="edit_amount" name="amount" min="0" step="0.01" required>
          </div>
          <div class="mb-3">
            <label for="edit_average_price" class="form-label">نرخی مامناوەند</label>
            <input type="number" class="form-control" id="edit_average_price" name="average_price" min="0" step="0.0000001" required>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/table-controler.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/bins_silos/add.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/bins_silos/select.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/bins_silos/update.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script nonce="<?php echo $csp_nonce; ?>">
// Fill edit modal with data
$(document).on('click', '.edit-btn', function() {
    $('#edit_id').val($(this).data('id'));
    $('#edit_name').val($(this).data('name'));
    $('#edit_type').val($(this).data('type'));
    $('#edit_material_type').val($(this).data('material_type'));
    $('#edit_amount').val($(this).data('amount'));
    $('#edit_average_price').val($(this).data('average_price'));
    $('#editBinModal').modal('show');
});
</script>
</body>
</html>
