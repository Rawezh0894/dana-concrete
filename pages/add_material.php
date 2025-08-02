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
$materials = $pdo->query("SELECT id, name, current_quantity as quantity, currency_type, purchase_price_usd, purchase_price_iqd, unit_type, pieces_per_carton, bags_per_barrel, liters_per_bag, liters_per_barrel, price_per_piece, price_per_liter, price_per_bag FROM inventory_materials ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
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
                    <th>نرخی یەکە</th>
                    <th>نرخی دەبە</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($materials) === 0): ?>
                <tr>
                    <td colspan="9">هیچ داتایەک نییە</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($materials as $i => $mat): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($mat['name']) ?></td>
                        <td>
                            <?php
                            $unitTypeText = '';
                            switch($mat['unit_type']) {
                                case 'carton':
                                    $unitTypeText = 'کارتۆن (' . $mat['pieces_per_carton'] . ' دانە)';
                                    break;
                                case 'piece':
                                    $unitTypeText = 'دانە';
                                    break;
                                case 'barrel':
                                    $unitTypeText = 'بەرمیل (' . $mat['bags_per_barrel'] . ' دەبە × ' . $mat['liters_per_bag'] . ' لیتر)';
                                    break;
                                case 'bag':
                                    $unitTypeText = 'دەبە (' . $mat['liters_per_bag'] . ' لیتر)';
                                    break;
                                case 'liter':
                                    $unitTypeText = 'لیتر';
                                    break;
                            }
                            echo htmlspecialchars($unitTypeText);
                            ?>
                        </td>
                        <td><?= htmlspecialchars($mat['quantity']) ?></td>
                        <td><?= htmlspecialchars($mat['currency_type']) ?></td>
                        <td><?= htmlspecialchars($mat['purchase_price_usd']) ?></td>
                        <td><?= htmlspecialchars($mat['purchase_price_iqd']) ?></td>
                        <td>
                            <?php
                            $unitPrice = '';
                            if ($mat['unit_type'] == 'carton' && $mat['price_per_piece']) {
                                $unitPrice = $mat['price_per_piece'] . ' دۆلار/دانە';
                            } elseif ($mat['unit_type'] == 'barrel') {
                                if ($mat['price_per_liter']) {
                                    $unitPrice = $mat['price_per_liter'] . ' دۆلار/لیتر';
                                }
                            } elseif ($mat['unit_type'] == 'bag' && $mat['price_per_liter']) {
                                $unitPrice = $mat['price_per_liter'] . ' دۆلار/لیتر';
                            } elseif ($mat['unit_type'] == 'piece' || $mat['unit_type'] == 'liter') {
                                $unitPrice = $mat['purchase_price_usd'] . ' دۆلار/یەکە';
                            }
                            echo htmlspecialchars($unitPrice);
                            ?>
                        </td>
                        <td>
                            <?php
                            $bagPrice = '';
                            if ($mat['unit_type'] == 'barrel' && $mat['price_per_bag']) {
                                $bagPrice = $mat['price_per_bag'] . ' دۆلار/دەبە';
                            } elseif ($mat['unit_type'] == 'bag' && $mat['price_per_bag']) {
                                $bagPrice = $mat['price_per_bag'] . ' دۆلار/دەبە';
                            }
                            echo htmlspecialchars($bagPrice);
                            ?>
                        </td>
                        <td>
                            <?php if (hasPermission('edit_material')): ?>
                            <button class="btn btn-sm btn-primary edit-btn" 
                                data-id="<?= $mat['id'] ?>" 
                                data-name="<?= htmlspecialchars($mat['name']) ?>" 
                                data-quantity="<?= htmlspecialchars($mat['quantity']) ?>" 
                                data-currency_type="<?= htmlspecialchars($mat['currency_type']) ?>" 
                                data-purchase_price_usd="<?= htmlspecialchars($mat['purchase_price_usd']) ?>" 
                                data-purchase_price_iqd="<?= htmlspecialchars($mat['purchase_price_iqd']) ?>"
                                data-unit_type="<?= htmlspecialchars($mat['unit_type']) ?>"
                                data-pieces_per_carton="<?= htmlspecialchars($mat['pieces_per_carton']) ?>"
                                data-bags_per_barrel="<?= htmlspecialchars($mat['bags_per_barrel']) ?>"
                                data-liters_per_bag="<?= htmlspecialchars($mat['liters_per_bag']) ?>"
                                data-liters_per_barrel="<?= htmlspecialchars($mat['liters_per_barrel']) ?>"
                                data-price_per_piece="<?= htmlspecialchars($mat['price_per_piece']) ?>"
                                data-price_per_liter="<?= htmlspecialchars($mat['price_per_liter']) ?>"
                                data-price_per_bag="<?= htmlspecialchars($mat['price_per_bag']) ?>"
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
          <div class="mb-3">
            <label for="name" class="form-label">ناوی کاڵا</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          
          <div class="mb-3">
            <label for="unit_type" class="form-label">جۆری یەکە</label>
            <select class="form-select" id="unit_type" name="unit_type" required>
              <option value="" selected disabled>-- هەڵبژێرە --</option>
              <option value="carton">کارتۆن</option>
              <option value="piece">دانە</option>
              <option value="barrel">بەرمیل</option>
              <option value="bag">دەبە</option>
              <option value="liter">لیتر</option>
            </select>
          </div>

          <!-- Carton specific fields -->
          <div class="mb-3 unit-field" id="carton_fields" style="display:none;">
            <label for="pieces_per_carton" class="form-label">ژمارەی دانە لە کارتۆن</label>
            <input type="number" class="form-control" id="pieces_per_carton" name="pieces_per_carton" min="1" step="1">
          </div>

          <!-- Barrel specific fields -->
          <div class="mb-3 unit-field" id="barrel_fields" style="display:none;">
            <div class="row">
              <div class="col-md-6">
                <label for="bags_per_barrel" class="form-label">ژمارەی دەبە لە بەرمیل</label>
                <input type="number" class="form-control" id="bags_per_barrel" name="bags_per_barrel" min="1" step="1">
              </div>
              <div class="col-md-6">
                <label for="liters_per_bag" class="form-label">ژمارەی لیتر لە دەبە</label>
                <input type="number" class="form-control" id="liters_per_bag" name="liters_per_bag" min="0.01" step="0.01">
              </div>
            </div>
          </div>

          <!-- Bag specific fields -->
          <div class="mb-3 unit-field" id="bag_fields" style="display:none;">
            <label for="liters_per_bag_single" class="form-label">ژمارەی لیتر لە دەبە</label>
            <input type="number" class="form-control" id="liters_per_bag_single" name="liters_per_bag_single" min="0.01" step="0.01">
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
            <input type="number" class="form-control" id="purchase_price_usd" name="purchase_price_usd" min="0" step="0.01">
          </div>
          
          <div class="mb-3" id="price_iqd_group" style="display:none;">
            <label for="purchase_price_iqd" class="form-label">نرخی کڕین بە دینار</label>
            <input type="number" class="form-control" id="purchase_price_iqd" name="purchase_price_iqd" min="0" step="0.01">
          </div>

          <!-- Calculated unit prices display -->
          <div class="mb-3" id="unit_price_display" style="display:none;">
            <div class="alert alert-info">
              <strong>نرخی یەکە:</strong>
              <div id="unit_price_text"></div>
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
          <div class="mb-3">
            <label for="edit_name" class="form-label">ناوی کاڵا</label>
            <input type="text" class="form-control" id="edit_name" name="name" required>
          </div>
          
          <div class="mb-3">
            <label for="edit_unit_type" class="form-label">جۆری یەکە</label>
            <select class="form-select" id="edit_unit_type" name="unit_type" required>
              <option value="" selected disabled>-- هەڵبژێرە --</option>
              <option value="carton">کارتۆن</option>
              <option value="piece">دانە</option>
              <option value="barrel">بەرمیل</option>
              <option value="bag">دەبە</option>
              <option value="liter">لیتر</option>
            </select>
          </div>

          <!-- Edit Carton specific fields -->
          <div class="mb-3 edit-unit-field" id="edit_carton_fields" style="display:none;">
            <label for="edit_pieces_per_carton" class="form-label">ژمارەی دانە لە کارتۆن</label>
            <input type="number" class="form-control" id="edit_pieces_per_carton" name="pieces_per_carton" min="1" step="1">
          </div>

          <!-- Edit Barrel specific fields -->
          <div class="mb-3 edit-unit-field" id="edit_barrel_fields" style="display:none;">
            <div class="row">
              <div class="col-md-6">
                <label for="edit_bags_per_barrel" class="form-label">ژمارەی دەبە لە بەرمیل</label>
                <input type="number" class="form-control" id="edit_bags_per_barrel" name="bags_per_barrel" min="1" step="1">
              </div>
              <div class="col-md-6">
                <label for="edit_liters_per_bag" class="form-label">ژمارەی لیتر لە دەبە</label>
                <input type="number" class="form-control" id="edit_liters_per_bag" name="liters_per_bag" min="0.01" step="0.01">
              </div>
            </div>
          </div>

          <!-- Edit Bag specific fields -->
          <div class="mb-3 edit-unit-field" id="edit_bag_fields" style="display:none;">
            <label for="edit_liters_per_bag_single" class="form-label">ژمارەی لیتر لە دەبە</label>
            <input type="number" class="form-control" id="edit_liters_per_bag_single" name="liters_per_bag_single" min="0.01" step="0.01">
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
            <input type="number" class="form-control" id="edit_purchase_price_usd" name="purchase_price_usd" min="0" step="0.01">
          </div>
          
          <div class="mb-3" id="edit_price_iqd_group" style="display:none;">
            <label for="edit_purchase_price_iqd" class="form-label">نرخی کڕین بە دینار</label>
            <input type="number" class="form-control" id="edit_purchase_price_iqd" name="purchase_price_iqd" min="0" step="0.01">
          </div>

          <!-- Edit Calculated unit prices display -->
          <div class="mb-3" id="edit_unit_price_display" style="display:none;">
            <div class="alert alert-info">
              <strong>نرخی یەکە:</strong>
              <div id="edit_unit_price_text"></div>
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
  // Unit type change handler for add modal
  $('#unit_type').on('change', function() {
    var unitType = $(this).val();
    $('.unit-field').hide();
    
    switch(unitType) {
      case 'carton':
        $('#carton_fields').show();
        break;
      case 'barrel':
        $('#barrel_fields').show();
        break;
      case 'bag':
        $('#bag_fields').show();
        break;
    }
    
    calculateUnitPrice();
  });

  // Unit type change handler for edit modal
  $('#edit_unit_type').on('change', function() {
    var unitType = $(this).val();
    $('.edit-unit-field').hide();
    
    switch(unitType) {
      case 'carton':
        $('#edit_carton_fields').show();
        break;
      case 'barrel':
        $('#edit_barrel_fields').show();
        break;
      case 'bag':
        $('#edit_bag_fields').show();
        break;
    }
    
    calculateEditUnitPrice();
  });

  // Currency type change handlers
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
    calculateUnitPrice();
  }
  
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
    calculateEditUnitPrice();
  }

  $('#currency_type').on('change', togglePriceFields);
  $('#edit_currency_type').on('change', toggleEditPriceFields);

  // Calculate unit price for add modal
  function calculateUnitPrice() {
    var unitType = $('#unit_type').val();
    var currencyType = $('#currency_type').val();
    var price = 0;
    var unitText = '';

    if (currencyType === 'دۆلار') {
      price = parseFloat($('#purchase_price_usd').val()) || 0;
    } else if (currencyType === 'دینار') {
      price = parseFloat($('#purchase_price_iqd').val()) || 0;
    }

    if (price > 0) {
      switch(unitType) {
        case 'carton':
          var piecesPerCarton = parseInt($('#pieces_per_carton').val()) || 1;
          var piecePrice = price / piecesPerCarton;
          unitText = piecePrice.toFixed(2) + ' ' + currencyType + '/دانە';
          break;
        case 'barrel':
          var bagsPerBarrel = parseInt($('#bags_per_barrel').val()) || 1;
          var litersPerBag = parseFloat($('#liters_per_bag').val()) || 1;
          var totalLiters = bagsPerBarrel * litersPerBag;
          var literPrice = price / totalLiters;
          unitText = literPrice.toFixed(2) + ' ' + currencyType + '/لیتر';
          break;
        case 'bag':
          var litersPerBag = parseFloat($('#liters_per_bag_single').val()) || 1;
          var literPrice = price / litersPerBag;
          unitText = literPrice.toFixed(2) + ' ' + currencyType + '/لیتر';
          break;
        case 'piece':
        case 'liter':
          unitText = price.toFixed(2) + ' ' + currencyType + '/یەکە';
          break;
      }
    }

    if (unitText) {
      $('#unit_price_text').text(unitText);
      $('#unit_price_display').show();
    } else {
      $('#unit_price_display').hide();
    }
  }

  // Calculate unit price for edit modal
  function calculateEditUnitPrice() {
    var unitType = $('#edit_unit_type').val();
    var currencyType = $('#edit_currency_type').val();
    var price = 0;
    var unitText = '';

    if (currencyType === 'دۆلار') {
      price = parseFloat($('#edit_purchase_price_usd').val()) || 0;
    } else if (currencyType === 'دینار') {
      price = parseFloat($('#edit_purchase_price_iqd').val()) || 0;
    }

    if (price > 0) {
      switch(unitType) {
        case 'carton':
          var piecesPerCarton = parseInt($('#edit_pieces_per_carton').val()) || 1;
          var piecePrice = price / piecesPerCarton;
          unitText = piecePrice.toFixed(2) + ' ' + currencyType + '/دانە';
          break;
        case 'barrel':
          var bagsPerBarrel = parseInt($('#edit_bags_per_barrel').val()) || 1;
          var litersPerBag = parseFloat($('#edit_liters_per_bag').val()) || 1;
          var totalLiters = bagsPerBarrel * litersPerBag;
          var literPrice = price / totalLiters;
          unitText = literPrice.toFixed(2) + ' ' + currencyType + '/لیتر';
          break;
        case 'bag':
          var litersPerBag = parseFloat($('#edit_liters_per_bag_single').val()) || 1;
          var literPrice = price / litersPerBag;
          unitText = literPrice.toFixed(2) + ' ' + currencyType + '/لیتر';
          break;
        case 'piece':
        case 'liter':
          unitText = price.toFixed(2) + ' ' + currencyType + '/یەکە';
          break;
      }
    }

    if (unitText) {
      $('#edit_unit_price_text').text(unitText);
      $('#edit_unit_price_display').show();
    } else {
      $('#edit_unit_price_display').hide();
    }
  }

  // Bind calculation events
  $('#purchase_price_usd, #purchase_price_iqd, #pieces_per_carton, #bags_per_barrel, #liters_per_bag, #liters_per_bag_single').on('input', calculateUnitPrice);
  $('#edit_purchase_price_usd, #edit_purchase_price_iqd, #edit_pieces_per_carton, #edit_bags_per_barrel, #edit_liters_per_bag, #edit_liters_per_bag_single').on('input', calculateEditUnitPrice);

  // When opening edit modal, populate fields and update price fields visibility
  $(document).on('click', '.edit-btn', function() {
    setTimeout(function() {
      toggleEditPriceFields();
      calculateEditUnitPrice();
    }, 100);
  });

  // Initialize
  togglePriceFields();
  toggleEditPriceFields();
});
</script>
</body>
</html>
