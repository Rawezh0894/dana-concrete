<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
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
$materials = $pdo->query("SELECT id, name, quantity, currency_type, purchase_price_usd, purchase_price_iqd FROM list_materials")->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="materialTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>ناوی کاڵا</th>
                    <th>بڕی بەردەست</th>
                    <th>جۆری دراو</th>
                    <th>نرخی کڕین بە دۆلار</th>
                    <th>نرخی کڕین بە دینار</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($materials) === 0): ?>
                <tr>
                    <td colspan="7">هیچ داتایەک نییە</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($materials as $i => $mat): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($mat['name']) ?></td>
                        <td><?= htmlspecialchars($mat['quantity']) ?></td>
                        <td><?= htmlspecialchars($mat['currency_type']) ?></td>
                        <td><?= htmlspecialchars($mat['purchase_price_usd']) ?></td>
                        <td><?= htmlspecialchars($mat['purchase_price_iqd']) ?></td>
                        <td>
                            <?php if (hasPermission('edit_material')): ?>
                            <button class="btn btn-sm btn-primary edit-btn" data-id="<?= $mat['id'] ?>" data-name="<?= htmlspecialchars($mat['name']) ?>" data-quantity="<?= htmlspecialchars($mat['quantity']) ?>" data-currency_type="<?= htmlspecialchars($mat['currency_type']) ?>" data-purchase_price_usd="<?= htmlspecialchars($mat['purchase_price_usd']) ?>" data-purchase_price_iqd="<?= htmlspecialchars($mat['purchase_price_iqd']) ?>" aria-label="نوێکردنەوە"><i class="bi bi-pencil"></i></button>
                            <?php endif; ?>
                            <?php if (hasPermission('delete_material')): ?>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $mat['id'] ?>" aria-label="سڕینەوە"><i class="bi bi-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Add Material Modal -->
<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addMaterialForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addMaterialModalLabel">زیادکردنی کاڵا</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="name" class="form-label">ناوی کاڵا</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="quantity" class="form-label">بڕی بەردەست</label>
            <input type="number" class="form-control" id="quantity" name="quantity" min="0" step="0.01" required>
          </div>
          <div class="mb-3">
            <label for="currency_type" class="form-label">جۆری دراو</label>
            <select class="form-select" id="currency_type" name="currency_type" required>
              <option value="" selected disabled>-- هەڵبژێرە --</option>
              <option value="دینار">دینار</option>
              <option value="دۆلار">دۆلار</option>
            </select>
          </div>
          <div class="mb-3" id="price_usd_group" style="display:none;">
            <label for="purchase_price_usd" class="form-label">نرخی کڕین بە دۆلار</label>
            <input type="number" class="form-control" id="purchase_price_usd" name="purchase_price_usd" min="0" step="0.01" value="0">
          </div>
          <div class="mb-3" id="price_iqd_group" style="display:none;">
            <label for="purchase_price_iqd" class="form-label">نرخی کڕین بە دینار</label>
            <input type="number" class="form-control" id="purchase_price_iqd" name="purchase_price_iqd" min="0" step="0.01" value="0">
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
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editMaterialForm">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editMaterialModalLabel">نوێکردنەوەی کاڵا</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_name" class="form-label">ناوی کاڵا</label>
            <input type="text" class="form-control" id="edit_name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="edit_quantity" class="form-label">بڕی بەردەست</label>
            <input type="number" class="form-control" id="edit_quantity" name="quantity" min="0" step="0.01" required>
          </div>
          <div class="mb-3">
            <label for="edit_currency_type" class="form-label">جۆری دراو</label>
            <select class="form-select" id="edit_currency_type" name="currency_type" required>
              <option value="" selected disabled>-- هەڵبژێرە --</option>
              <option value="دینار">دینار</option>
              <option value="دۆلار">دۆلار</option>
            </select>
          </div>
          <div class="mb-3" id="edit_price_usd_group" style="display:none;">
            <label for="edit_purchase_price_usd" class="form-label">نرخی کڕین بە دۆلار</label>
            <input type="number" class="form-control" id="edit_purchase_price_usd" name="purchase_price_usd" min="0" step="0.01" value="0">
          </div>
          <div class="mb-3" id="edit_price_iqd_group" style="display:none;">
            <label for="edit_purchase_price_iqd" class="form-label">نرخی کڕین بە دینار</label>
            <input type="number" class="form-control" id="edit_purchase_price_iqd" name="purchase_price_iqd" min="0" step="0.01" value="0">
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
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/add_material/add.js"></script>
<script src="../assets/js/add_material/select.js"></script>
<script src="../assets/js/add_material/delete.js"></script>
<script src="../assets/js/add_material/update.js"></script>
<script>
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
  $('#currency_type').on('change', togglePriceFields);
  togglePriceFields();

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
  $('#edit_currency_type').on('change', toggleEditPriceFields);
  toggleEditPriceFields();

  // When opening edit modal, update price fields visibility
  $(document).on('click', '.edit-btn', function() {
    setTimeout(toggleEditPriceFields, 100);
  });
});
</script>
</body>
</html>
