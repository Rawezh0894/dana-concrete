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
$materials = $pdo->query("SELECT id, name, unit_type, quantity, currency_type, purchase_price_usd, purchase_price_iqd, 
                         pieces_per_carton, buckets_per_barrel, liters_per_bucket, liters_per_barrel,
                         price_per_piece_usd, price_per_piece_iqd, price_per_bucket_usd, price_per_bucket_iqd,
                         price_per_liter_usd, price_per_liter_iqd 
                         FROM list_materials")->fetchAll(PDO::FETCH_ASSOC);
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
                    <th>جۆری یەکە</th>
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
                    <td colspan="8">هیچ داتایەک نییە</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($materials as $i => $mat): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($mat['name']) ?></td>
                        <td><?= htmlspecialchars($mat['unit_type']) ?></td>
                        <td><?= htmlspecialchars($mat['quantity']) ?></td>
                        <td><?= htmlspecialchars($mat['currency_type']) ?></td>
                        <td><?= htmlspecialchars($mat['purchase_price_usd']) ?></td>
                        <td><?= htmlspecialchars($mat['purchase_price_iqd']) ?></td>
                        <td>
                            <?php if (hasPermission('edit_material')): ?>
                            <button class="btn btn-sm btn-primary edit-btn" 
                                    data-id="<?= $mat['id'] ?>" 
                                    data-name="<?= htmlspecialchars($mat['name']) ?>" 
                                    data-unit_type="<?= htmlspecialchars($mat['unit_type']) ?>"
                                    data-quantity="<?= htmlspecialchars($mat['quantity']) ?>" 
                                    data-currency_type="<?= htmlspecialchars($mat['currency_type']) ?>" 
                                    data-purchase_price_usd="<?= htmlspecialchars($mat['purchase_price_usd']) ?>" 
                                    data-purchase_price_iqd="<?= htmlspecialchars($mat['purchase_price_iqd']) ?>"
                                    data-pieces_per_carton="<?= htmlspecialchars($mat['pieces_per_carton']) ?>"
                                    data-buckets_per_barrel="<?= htmlspecialchars($mat['buckets_per_barrel']) ?>"
                                    data-liters_per_bucket="<?= htmlspecialchars($mat['liters_per_bucket']) ?>"
                                    data-liters_per_barrel="<?= htmlspecialchars($mat['liters_per_barrel']) ?>"
                                    aria-label="نوێکردنەوە"><i class="bi bi-pencil"></i></button>
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
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addMaterialForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addMaterialModalLabel">زیادکردنی کاڵا</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="name" class="form-label">ناوی کاڵا</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="unit_type" class="form-label">جۆری یەکە</label>
              <select class="form-select" id="unit_type" name="unit_type" required>
                <option value="" selected disabled>-- هەڵبژێرە --</option>
                <option value="کارتۆن">کارتۆن</option>
                <option value="دانە">دانە</option>
                <option value="بەرمیل">بەرمیل</option>
                <option value="دەبە">دەبە</option>
                <option value="لیتر">لیتر</option>
              </select>
            </div>
          </div>
          
          <!-- Conversion Fields (shown/hidden based on unit type) -->
          <div id="conversion_fields" style="display:none;">
            <div class="row">
              <div class="col-md-6 mb-3" id="pieces_per_carton_group" style="display:none;">
                <label for="pieces_per_carton" class="form-label">ژمارەی دانە لە کارتۆن</label>
                <input type="number" class="form-control" id="pieces_per_carton" name="pieces_per_carton" min="1" value="1">
              </div>
              <div class="col-md-6 mb-3" id="buckets_per_barrel_group" style="display:none;">
                <label for="buckets_per_barrel" class="form-label">ژمارەی دەبە لە بەرمیل</label>
                <input type="number" class="form-control" id="buckets_per_barrel" name="buckets_per_barrel" min="1" value="1">
              </div>
              <div class="col-md-6 mb-3" id="liters_per_bucket_group" style="display:none;">
                <label for="liters_per_bucket" class="form-label">ژمارەی لیتر لە دەبە</label>
                <input type="number" class="form-control" id="liters_per_bucket" name="liters_per_bucket" min="0.01" step="0.01" value="1">
              </div>
              <div class="col-md-6 mb-3" id="liters_per_barrel_group" style="display:none;">
                <label for="liters_per_barrel" class="form-label">کۆی لیتر لە بەرمیل</label>
                <input type="number" class="form-control" id="liters_per_barrel" name="liters_per_barrel" min="0.01" step="0.01" value="1">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="quantity" class="form-label">بڕی بەردەست</label>
              <input type="number" class="form-control" id="quantity" name="quantity" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="currency_type" class="form-label">جۆری دراو</label>
              <select class="form-select" id="currency_type" name="currency_type" required>
                <option value="" selected disabled>-- هەڵبژێرە --</option>
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3" id="price_usd_group" style="display:none;">
              <label for="purchase_price_usd" class="form-label">نرخی کڕین بە دۆلار</label>
              <input type="number" class="form-control" id="purchase_price_usd" name="purchase_price_usd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3" id="price_iqd_group" style="display:none;">
              <label for="purchase_price_iqd" class="form-label">نرخی کڕین بە دینار</label>
              <input type="number" class="form-control" id="purchase_price_iqd" name="purchase_price_iqd" min="0" step="0.01" value="0">
            </div>
          </div>

          <!-- Calculated Prices Display -->
          <div id="calculated_prices" style="display:none;">
            <div class="row">
              <div class="col-12">
                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">نرخەکانی ژمێردراو</h6>
              </div>
              <div class="col-md-4 mb-3" id="price_per_piece_group" style="display:none;">
                <label class="form-label">نرخی دانە بە دۆلار</label>
                <input type="text" class="form-control" id="price_per_piece_usd" readonly>
              </div>
              <div class="col-md-4 mb-3" id="price_per_bucket_group" style="display:none;">
                <label class="form-label">نرخی دەبە بە دۆلار</label>
                <input type="text" class="form-control" id="price_per_bucket_usd" readonly>
              </div>
              <div class="col-md-4 mb-3" id="price_per_liter_group" style="display:none;">
                <label class="form-label">نرخی لیتر بە دۆلار</label>
                <input type="text" class="form-control" id="price_per_liter_usd" readonly>
              </div>
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
<!-- Edit Material Modal -->
<div class="modal fade" id="editMaterialModal" tabindex="-1" aria-labelledby="editMaterialModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editMaterialForm">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editMaterialModalLabel">نوێکردنەوەی کاڵا</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_name" class="form-label">ناوی کاڵا</label>
              <input type="text" class="form-control" id="edit_name" name="name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_unit_type" class="form-label">جۆری یەکە</label>
              <select class="form-select" id="edit_unit_type" name="unit_type" required>
                <option value="" selected disabled>-- هەڵبژێرە --</option>
                <option value="کارتۆن">کارتۆن</option>
                <option value="دانە">دانە</option>
                <option value="بەرمیل">بەرمیل</option>
                <option value="دەبە">دەبە</option>
                <option value="لیتر">لیتر</option>
              </select>
            </div>
          </div>
          
          <!-- Conversion Fields for Edit -->
          <div id="edit_conversion_fields" style="display:none;">
            <div class="row">
              <div class="col-md-6 mb-3" id="edit_pieces_per_carton_group" style="display:none;">
                <label for="edit_pieces_per_carton" class="form-label">ژمارەی دانە لە کارتۆن</label>
                <input type="number" class="form-control" id="edit_pieces_per_carton" name="pieces_per_carton" min="1" value="1">
              </div>
              <div class="col-md-6 mb-3" id="edit_buckets_per_barrel_group" style="display:none;">
                <label for="edit_buckets_per_barrel" class="form-label">ژمارەی دەبە لە بەرمیل</label>
                <input type="number" class="form-control" id="edit_buckets_per_barrel" name="buckets_per_barrel" min="1" value="1">
              </div>
              <div class="col-md-6 mb-3" id="edit_liters_per_bucket_group" style="display:none;">
                <label for="edit_liters_per_bucket" class="form-label">ژمارەی لیتر لە دەبە</label>
                <input type="number" class="form-control" id="edit_liters_per_bucket" name="liters_per_bucket" min="0.01" step="0.01" value="1">
              </div>
              <div class="col-md-6 mb-3" id="edit_liters_per_barrel_group" style="display:none;">
                <label for="edit_liters_per_barrel" class="form-label">کۆی لیتر لە بەرمیل</label>
                <input type="number" class="form-control" id="edit_liters_per_barrel" name="liters_per_barrel" min="0.01" step="0.01" value="1">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_quantity" class="form-label">بڕی بەردەست</label>
              <input type="number" class="form-control" id="edit_quantity" name="quantity" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_currency_type" class="form-label">جۆری دراو</label>
              <select class="form-select" id="edit_currency_type" name="currency_type" required>
                <option value="" selected disabled>-- هەڵبژێرە --</option>
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3" id="edit_price_usd_group" style="display:none;">
              <label for="edit_purchase_price_usd" class="form-label">نرخی کڕین بە دۆلار</label>
              <input type="number" class="form-control" id="edit_purchase_price_usd" name="purchase_price_usd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3" id="edit_price_iqd_group" style="display:none;">
              <label for="edit_purchase_price_iqd" class="form-label">نرخی کڕین بە دینار</label>
              <input type="number" class="form-control" id="edit_purchase_price_iqd" name="purchase_price_iqd" min="0" step="0.01" value="0">
            </div>
          </div>

          <!-- Calculated Prices Display for Edit -->
          <div id="edit_calculated_prices" style="display:none;">
            <div class="row">
              <div class="col-12">
                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">نرخەکانی ژمێردراو</h6>
              </div>
              <div class="col-md-4 mb-3" id="edit_price_per_piece_group" style="display:none;">
                <label class="form-label">نرخی دانە بە دۆلار</label>
                <input type="text" class="form-control" id="edit_price_per_piece_usd" readonly>
              </div>
              <div class="col-md-4 mb-3" id="edit_price_per_bucket_group" style="display:none;">
                <label class="form-label">نرخی دەبە بە دۆلار</label>
                <input type="text" class="form-control" id="edit_price_per_bucket_usd" readonly>
              </div>
              <div class="col-md-4 mb-3" id="edit_price_per_liter_group" style="display:none;">
                <label class="form-label">نرخی لیتر بە دۆلار</label>
                <input type="text" class="form-control" id="edit_price_per_liter_usd" readonly>
              </div>
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

  function toggleUnitFields() {
    var unitType = $('#unit_type').val();
    $('#conversion_fields').hide();
    $('#pieces_per_carton_group').hide();
    $('#buckets_per_barrel_group').hide();
    $('#liters_per_bucket_group').hide();
    $('#liters_per_barrel_group').hide();
    
    if (unitType === 'کارتۆن') {
      $('#conversion_fields').show();
      $('#pieces_per_carton_group').show();
    } else if (unitType === 'بەرمیل') {
      $('#conversion_fields').show();
      $('#buckets_per_barrel_group').show();
      $('#liters_per_bucket_group').show();
      $('#liters_per_barrel_group').show();
    } else if (unitType === 'دەبە') {
      $('#conversion_fields').show();
      $('#liters_per_bucket_group').show();
    }
    
    calculatePrices();
  }

  function calculatePrices() {
    var unitType = $('#unit_type').val();
    var priceUsd = parseFloat($('#purchase_price_usd').val()) || 0;
    var priceIqd = parseFloat($('#purchase_price_iqd').val()) || 0;
    
    $('#calculated_prices').hide();
    $('#price_per_piece_group').hide();
    $('#price_per_bucket_group').hide();
    $('#price_per_liter_group').hide();
    
    if (unitType === 'کارتۆن' && priceUsd > 0) {
      var piecesPerCarton = parseInt($('#pieces_per_carton').val()) || 1;
      var pricePerPiece = priceUsd / piecesPerCarton;
      $('#price_per_piece_usd').val(pricePerPiece.toFixed(2));
      $('#calculated_prices').show();
      $('#price_per_piece_group').show();
    } else if (unitType === 'بەرمیل' && priceUsd > 0) {
      var bucketsPerBarrel = parseInt($('#buckets_per_barrel').val()) || 1;
      var litersPerBucket = parseFloat($('#liters_per_bucket').val()) || 1;
      var pricePerBucket = priceUsd / bucketsPerBarrel;
      var pricePerLiter = priceUsd / (bucketsPerBarrel * litersPerBucket);
      
      $('#price_per_bucket_usd').val(pricePerBucket.toFixed(2));
      $('#price_per_liter_usd').val(pricePerLiter.toFixed(2));
      $('#calculated_prices').show();
      $('#price_per_bucket_group').show();
      $('#price_per_liter_group').show();
    } else if (unitType === 'دەبە' && priceUsd > 0) {
      var litersPerBucket = parseFloat($('#liters_per_bucket').val()) || 1;
      var pricePerLiter = priceUsd / litersPerBucket;
      $('#price_per_liter_usd').val(pricePerLiter.toFixed(2));
      $('#calculated_prices').show();
      $('#price_per_liter_group').show();
    }
  }

  // Event listeners for add modal
  $('#currency_type').on('change', togglePriceFields);
  $('#unit_type').on('change', toggleUnitFields);
  $('#purchase_price_usd, #purchase_price_iqd, #pieces_per_carton, #buckets_per_barrel, #liters_per_bucket, #liters_per_barrel').on('input', calculatePrices);
  togglePriceFields();
  toggleUnitFields();

  // Edit modal functions
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

  function toggleEditUnitFields() {
    var unitType = $('#edit_unit_type').val();
    $('#edit_conversion_fields').hide();
    $('#edit_pieces_per_carton_group').hide();
    $('#edit_buckets_per_barrel_group').hide();
    $('#edit_liters_per_bucket_group').hide();
    $('#edit_liters_per_barrel_group').hide();
    
    if (unitType === 'کارتۆن') {
      $('#edit_conversion_fields').show();
      $('#edit_pieces_per_carton_group').show();
    } else if (unitType === 'بەرمیل') {
      $('#edit_conversion_fields').show();
      $('#edit_buckets_per_barrel_group').show();
      $('#edit_liters_per_bucket_group').show();
      $('#edit_liters_per_barrel_group').show();
    } else if (unitType === 'دەبە') {
      $('#edit_conversion_fields').show();
      $('#edit_liters_per_bucket_group').show();
    }
    
    calculateEditPrices();
  }

  function calculateEditPrices() {
    var unitType = $('#edit_unit_type').val();
    var priceUsd = parseFloat($('#edit_purchase_price_usd').val()) || 0;
    var priceIqd = parseFloat($('#edit_purchase_price_iqd').val()) || 0;
    
    $('#edit_calculated_prices').hide();
    $('#edit_price_per_piece_group').hide();
    $('#edit_price_per_bucket_group').hide();
    $('#edit_price_per_liter_group').hide();
    
    if (unitType === 'کارتۆن' && priceUsd > 0) {
      var piecesPerCarton = parseInt($('#edit_pieces_per_carton').val()) || 1;
      var pricePerPiece = priceUsd / piecesPerCarton;
      $('#edit_price_per_piece_usd').val(pricePerPiece.toFixed(2));
      $('#edit_calculated_prices').show();
      $('#edit_price_per_piece_group').show();
    } else if (unitType === 'بەرمیل' && priceUsd > 0) {
      var bucketsPerBarrel = parseInt($('#edit_buckets_per_barrel').val()) || 1;
      var litersPerBucket = parseFloat($('#edit_liters_per_bucket').val()) || 1;
      var pricePerBucket = priceUsd / bucketsPerBarrel;
      var pricePerLiter = priceUsd / (bucketsPerBarrel * litersPerBucket);
      
      $('#edit_price_per_bucket_usd').val(pricePerBucket.toFixed(2));
      $('#edit_price_per_liter_usd').val(pricePerLiter.toFixed(2));
      $('#edit_calculated_prices').show();
      $('#edit_price_per_bucket_group').show();
      $('#edit_price_per_liter_group').show();
    } else if (unitType === 'دەبە' && priceUsd > 0) {
      var litersPerBucket = parseFloat($('#edit_liters_per_bucket').val()) || 1;
      var pricePerLiter = priceUsd / litersPerBucket;
      $('#edit_price_per_liter_usd').val(pricePerLiter.toFixed(2));
      $('#edit_calculated_prices').show();
      $('#edit_price_per_liter_group').show();
    }
  }

  // Event listeners for edit modal
  $('#edit_currency_type').on('change', toggleEditPriceFields);
  $('#edit_unit_type').on('change', toggleEditUnitFields);
  $('#edit_purchase_price_usd, #edit_purchase_price_iqd, #edit_pieces_per_carton, #edit_buckets_per_barrel, #edit_liters_per_bucket, #edit_liters_per_barrel').on('input', calculateEditPrices);

  // When opening edit modal, update fields visibility
  $(document).on('click', '.edit-btn', function() {
    setTimeout(function() {
      toggleEditPriceFields();
      toggleEditUnitFields();
    }, 100);
  });
});
</script>
</body>
</html>
