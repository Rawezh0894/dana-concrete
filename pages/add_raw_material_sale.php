<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_sale')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

$customers = $pdo->query("SELECT id, name FROM customers")->fetchAll(PDO::FETCH_ASSOC);

// Get recipients: both from recipients table and customers with is_recipient = 1
$recipients_from_table = $pdo->query("SELECT id, name, phone1, phone2 FROM recipients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$recipients_from_customers = $pdo->query("SELECT id, name, mobile1 AS phone1, mobile2 AS phone2 FROM customers WHERE is_recipient = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Combine both and remove duplicates by name
$recipients = [];
$recipient_names = [];
foreach ($recipients_from_table as $r) {
    $recipients[] = $r;
    $recipient_names[] = strtolower(trim($r['name']));
}
foreach ($recipients_from_customers as $r) {
    if (!in_array(strtolower(trim($r['name'])), $recipient_names)) {
        $recipients[] = $r;
        $recipient_names[] = strtolower(trim($r['name']));
    }
}
// Sort by name
usort($recipients, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// Material types
$material_types = [
    'black_sand' => 'لمی ڕەش',
    'brown_sand' => 'لمی کەسارە',
    'gravel' => 'چەو',
    'cement' => 'چیمەنتۆ',
    'medicine' => 'دەرمان',
    'gas' => 'گاز'
];
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فرۆشتی ماددە خامەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.min.css" rel="stylesheet">
    
    <style>
        .export-btn {
            background: var(--warning) !important;
            border-color: var(--warning) !important;
            color: #212529 !important;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background: #e0a800 !important;
            border-color: #e0a800 !important;
            transform: translateY(-1px);
            color: #212529 !important;
        }
        
        .table thead {
            background: var(--kelly-green);
        }
        
        .table thead th {
            background-color: var(--kelly-green) !important;
            color: var(--seafoam-green) !important;
        }
        
        .page-actions-wrapper {
            width: 100%;
            margin-bottom: 1.5rem;
        }
        
        .page-action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .page-action-buttons .btn {
            min-width: 160px;
            font-weight: bold;
            white-space: nowrap;
        }
    </style>

    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="page-actions-wrapper">
        <div class="page-action-buttons">
            <button class="btn export-btn" onclick="exportRawMaterialSalesToExcel()" title="ئیکسپۆرتی هەموو زانیارییەکانی فرۆشتن بۆ Excel">
                <i class="fas fa-file-excel me-1"></i>ئیکسپۆرتی Excel
            </button>
            <?php if (hasPermission('add_sale')): ?>
            <button class="btn" data-bs-toggle="modal" data-bs-target="#addRawMaterialSaleModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردنی فرۆشتی ماددە خامەکان</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-3">
        <label>لە بەروار:</label>
        <input type="date" id="filter_from" class="form-control">
      </div>
      <div class="col-md-3">
        <label>بۆ بەروار:</label>
        <input type="date" id="filter_to" class="form-control">
      </div>
      <div class="col-md-3">
        <label for="filter_customer">کڕیار:</label>
        <select class="form-select" id="filter_customer">
          <option value="">هەموو کڕیارەکان</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label for="filter_material_type">جۆری ماددە:</label>
        <select class="form-select" id="filter_material_type">
          <option value="">هەموو جۆرەکان</option>
          <?php foreach ($material_types as $key => $name): ?>
            <option value="<?= $key ?>"><?= htmlspecialchars($name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end gap-2">
        <button class="btn btn-secondary" id="clearFilterBtn" type="button">پاککردنەوە</button>
      </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="rawMaterialSaleTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>کڕیار</th>
                    <th>وەرگر</th>
                    <th>شوێن</th>
                    <th>ژمارەی پسوڵە</th>
                    <th>جۆری ماددە</th>
                    <th>بەروار</th>
                    <th>بڕ</th>
                    <th>یەکە</th>
                    <th>نرخی یەکە</th>
                    <th>کۆی نرخ</th>
                    <th>جۆری پارەدان</th>
                    <th>پارەی دراو بە دینار</th>
                    <th>پارەی دراو بە دۆلار</th>
                    <th>پارەی ماوە</th>
                    <th>نرخی ١٠٠ دۆلار</th>
                    <th>تێبینی</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="17" class="text-muted">چاوەڕوان بە...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Raw Material Sale Modal -->
<div class="modal fade" id="addRawMaterialSaleModal" tabindex="-1" aria-labelledby="addRawMaterialSaleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addRawMaterialSaleForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addRawMaterialSaleModalLabel">زیادکردنی فرۆشتی ماددە خامەکان</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="rm_customer_id" class="form-label select2-filter">کڕیار</label>
              <select class="form-select" id="rm_customer_id" name="customer_id">
                <option value="">هەڵبژێرە</option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="rm_recipient" class="form-label">وەرگر</label>
              <select class="form-select" id="rm_recipient" name="recipient_id" data-placeholder="وەرگرێک هەڵبژێرە">
                <option value="">وەرگرێک هەڵبژێرە</option>
                <?php foreach ($recipients as $recipient): 
                    $phoneList = array_filter([
                        !empty($recipient['phone1']) ? $recipient['phone1'] : '',
                        !empty($recipient['phone2']) ? $recipient['phone2'] : ''
                    ]);
                    $searchMeta = trim($recipient['name'] . ' ' . implode(' ', $phoneList));
                ?>
                    <option 
                        value="<?= (int)$recipient['id'] ?>"
                        data-name="<?= htmlspecialchars($recipient['name']) ?>"
                        data-search="<?= htmlspecialchars($searchMeta) ?>"
                    >
                        <?= htmlspecialchars($recipient['name']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="rm_location" class="form-label">شوێن</label>
              <input type="text" class="form-control" id="rm_location" name="location" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="rm_invoice_number" class="form-label">ژمارەی پسوڵە</label>
              <input type="text" class="form-control" id="rm_invoice_number" name="invoice_number" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="rm_material_type" class="form-label">جۆری ماددە</label>
              <select class="form-select" id="rm_material_type" name="material_type" required>
                <option value="">هەڵبژێرە</option>
                <?php foreach ($material_types as $key => $name): ?>
                  <option value="<?= $key ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="rm_order_date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="rm_order_date" name="order_date" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="rm_quantity" class="form-label">بڕ</label>
              <input type="number" class="form-control" id="rm_quantity" name="quantity" min="0" step="0.01" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="rm_unit" class="form-label">یەکە</label>
              <input type="text" class="form-control" id="rm_unit" name="unit" value="کیلۆگرام" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="rm_price_per_unit" class="form-label">نرخی یەکە</label>
              <input type="number" class="form-control" id="rm_price_per_unit" name="price_per_unit" min="0" step="0.01" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="rm_total_price" class="form-label">کۆی نرخ</label>
              <input type="number" class="form-control" id="rm_total_price" name="total_price" min="0" step="0.01" required readonly>
            </div>
            <div class="col-md-4 mb-3">
              <label for="rm_payment_type" class="form-label">جۆری پارەدان</label>
              <select class="form-select" id="rm_payment_type" name="payment_type" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="نەقد">نەقد</option>
                <option value="قەرز">قەرز</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="rm_discount" class="form-label">داشکاندن</label>
              <input type="number" class="form-control" id="rm_discount" name="discount" min="0" step="0.01" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="rm_amount_paid_iq" class="form-label">پارەی دراو بە دینار</label>
              <input type="number" class="form-control" id="rm_amount_paid_iq" name="amount_paid_iq" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-4 mb-3">
              <label for="rm_amount_paid_usd" class="form-label">پارەی دراو بە دۆلار</label>
              <input type="number" class="form-control" id="rm_amount_paid_usd" name="amount_paid_usd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-4 mb-3">
              <label for="rm_remaining_amount" class="form-label">پارەی ماوە</label>
              <input type="number" class="form-control" id="rm_remaining_amount" name="remaining_amount" min="0" step="0.01" value="0" readonly>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="rm_dolar_rate" class="form-label">نرخی ١٠٠ دۆلار</label>
              <div class="input-group">
                <input type="number" class="form-control" id="rm_dolar_rate" name="dolar_rate" min="0" step="0.01" value="150000">
                <button type="button" class="btn btn-outline-secondary" id="refreshDollarRateRM" title="نوێکردنەوەی نرخی دۆلار">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </div>
            </div>
            <div class="col-md-8 mb-3">
              <label for="rm_notes" class="form-label">تێبینی</label>
              <textarea class="form-control" id="rm_notes" name="notes" rows="2"></textarea>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
<script src="../assets/js/comon/select2_script.js"></script>
<script>
    window.userPermissions = {
      canAdd: <?php echo hasPermission('add_sale') ? 'true' : 'false'; ?>,
      canEdit: <?php echo hasPermission('update_sale') ? 'true' : 'false'; ?>,
      canDelete: <?php echo hasPermission('delete_sale') ? 'true' : 'false'; ?>
    };
    
    const materialTypes = <?php echo json_encode($material_types); ?>;
</script>
<script src="../assets/js/raw_material_sales/raw_material_sales.js"></script>

</body>
</html>
