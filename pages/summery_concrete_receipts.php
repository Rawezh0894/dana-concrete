<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: ../index.php');
  exit;
}

// Check if user has permission to view summery concrete receipts
if (!hasPermission('view_summery_concrete_receipts')) {
  echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
    . '<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
    . '<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
    . '</div>';
  exit;
}

$customers = $pdo->query("SELECT id, name, mobile1 FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$formulas = $pdo->query("SELECT id, name FROM concrete_formulas")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>پوختەی پسووڵەکانی کۆنکرێت</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="../assets/css/login.css" rel="stylesheet">
  <link href="../assets/css/variables.css" rel="stylesheet">
  <link href="../assets/css/nav.css" rel="stylesheet">
  <link href="../assets/css/comon/table.css" rel="stylesheet">
  <link href="../assets/css/comon/style.css" rel="stylesheet">
  <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="../assets/css/summery_concrete_receipts.css" rel="stylesheet">
  <style>
    @media print {
      .no-print {
        display: none !important;
      }
      #printSection {
        display: block !important;
      }
      body {
        margin: 0;
        padding: 20px;
      }
    }
  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  
</head>

<body dir="rtl">
  <?php include '../includes/navbar.php'; ?>
  <?php include '../includes/sidebar.php'; ?>
  
  <div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">پوختەی پسووڵەکانی کۆنکرێت</h2>
      <div class="d-flex gap-2">
        <?php if (hasPermission('view_notes')): ?>
          <a href="notes.php" class="btn" style="background: var(--kelly-green); color:white; font-weight: bold;">
            <i class="fas fa-sticky-note me-1"></i>تێبینیەکان
          </a>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" onclick="printReport()">
          <i class="fas fa-print me-1"></i>چاپکردن
        </button>
        <a href="concrete_receipts.php" class="btn btn-secondary">
          <i class="fas fa-arrow-right me-1"></i>گەڕانەوە بۆ پسووڵەکان
        </a>
      </div>
    </div>

    <!-- Filter Row -->
    <div class="row g-2 mb-3 no-print">
      <div class="col-md-3">
        <select class="form-select" id="filter_customer_id">
          <option value="">کڕیار: هەموو</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select" id="filter_formulas_id">
          <option value="">ڕێژە: هەموو</option>
          <?php foreach ($formulas as $f): ?>
            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filter_date_from" value="<?= date('Y-m-d') ?>" placeholder="لە بەرواری">
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filter_date_to" value="<?= date('Y-m-d') ?>" placeholder="بۆ بەرواری">
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="button" class="btn btn-sm btn-primary filter-btn" id="filter_today" data-filter="today">
          <i class="fas fa-calendar-day me-1"></i>ئەمڕۆ
        </button>
        <button type="button" class="btn btn-sm btn-warning filter-btn" id="filter_yesterday" data-filter="yesterday">
          <i class="fas fa-calendar-minus me-1"></i>دوێنێ
        </button>
        <button type="button" class="btn btn-sm btn-secondary filter-btn" id="filter_reset" data-filter="reset">
          <i class="fas fa-redo me-1"></i>ڕیفڕێش
        </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4 no-print" id="summary-cards">
      <div class="col-md-3 mb-3">
        <div class="card summary-card text-center">
          <div class="card-body">
            <h5 class="card-title">کۆی گشتی پسووڵەکان</h5>
            <span id="total_receipts" style="font-size:2.5rem;font-weight:bold;">0</span>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card summary-card text-center">
          <div class="card-body">
            <h5 class="card-title">کۆی گشتی بڕی مەتر سێجا</h5>
            <span id="total_meter" style="font-size:2.5rem;font-weight:bold;">0</span>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card summary-card text-center">
          <div class="card-body">
            <h5 class="card-title">کۆی کڕیاران</h5>
            <span id="total_customers" style="font-size:2.5rem;font-weight:bold;">0</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Customer Summary Table -->
    <div class="card no-print">
      <div class="card-header">
        <h5 class="mb-0">پوختەی کڕیاران</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover" id="customerSummaryTable">
            <thead style="background: var(--kelly-green); color: white;">
              <tr>
                <th>#</th>
                <th>ناوی کڕیار</th>
                <th>ژمارەی پسووڵەکان</th>
                <th>کۆی مەتر سێجا</th>
                <?php if (hasPermission('view_concrete_prices')): ?>
                <th>کۆی نرخ</th>
                <th>تێبینی</th>
                <?php endif; ?>
                <th>فۆرمۆلاکان</th>
                <th>کردارەکان</th>
              </tr>
            </thead>
            <tbody>
              <!-- Data will be loaded here -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Customer Details Modal -->
    <div class="modal fade" id="customerDetailsModal" tabindex="-1">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">وردەکاری کڕیار</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div id="customerDetailsContent">
              <!-- Customer details will be loaded here -->
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Price Setting Modal -->
    <div class="modal fade" id="priceSettingModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">دانانی نرخی مەتر سێجا</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="price_per_meter" class="form-label">نرخی یەک مەتر سێجا ($)</label>
              <input type="number" class="form-control" id="price_per_meter" step="0.01" min="0" placeholder="نرخی مەتر سێجا">
            </div>
            <div class="mb-3">
              <label for="notes" class="form-label">تێبینی</label>
              <textarea class="form-control" id="notes" rows="3" placeholder="تێبینی دەربارەی نرخەکە..."></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">پسووڵەکان:</label>
              <div id="selected_receipts_list" class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                <!-- Selected receipts will be listed here -->
              </div>
            </div>
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              ئەم نرخە بۆ هەموو پسووڵە هەڵبژێردراوەکان دانراوەتەوە
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">هەڵوەشاندنەوە</button>
            <button type="button" class="btn btn-primary" onclick="savePricePerMeter()">
              <i class="fas fa-save me-1"></i>پاشەکەوتکردن
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Print Section (Hidden) -->
  <div id="printSection" style="display: none;">
    <div style="text-align: center; margin-bottom: 30px;">

      <p style="color: #666; margin-bottom: 5px;">بەروار: <span id="print_date"></span></p>
      <p style="color: #666;">کاتی چاپکردن: <span id="print_time"></span></p>
    </div>
    
    <div style="margin-bottom: 30px;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
        <div style="text-align: center; flex: 1; margin: 0 10px;">
          <div style="background: var(--seafoam-green); color: white; padding: 15px; border-radius: 10px;">
            <h4 style="margin: 0 0 10px 0;">کۆی گشتی پسووڵەکان</h4>
            <span id="print_total_receipts" style="font-size: 2rem; font-weight: bold;">0</span>
          </div>
        </div>
        <div style="text-align: center; flex: 1; margin: 0 10px;">
          <div style="background: var(--kelly-green); color: white; padding: 15px; border-radius: 10px;">
            <h4 style="margin: 0 0 10px 0;">کۆی گشتی مەتر سێجا</h4>
            <span id="print_total_meter" style="font-size: 2rem; font-weight: bold;">0</span>
          </div>
        </div>
        <div style="text-align: center; flex: 1; margin: 0 10px;">
          <div style="background: #1976d2; color: white; padding: 15px; border-radius: 10px;">
            <h4 style="margin: 0 0 10px 0;">کۆی کڕیاران</h4>
            <span id="print_total_customers" style="font-size: 2rem; font-weight: bold;">0</span>
          </div>
        </div>
      </div>
    </div>
    
    <div id="print_customer_details">
      <!-- Customer details for print will be loaded here -->
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/swalAlert.js"></script>
  <script src="../assets/js/comon/select2_script.js"></script>
  <script src="../assets/js/summery_concrete_receipts/filter.js"></script>
  <script src="../assets/js/summery_concrete_receipts/get_informations.js"></script>
  <script>
    // Pass permissions to JavaScript
    window.userPermissions = {
      canViewPrices: <?php echo hasPermission('view_concrete_prices') ? 'true' : 'false'; ?>,
      canSetPrices: <?php echo hasPermission('set_concrete_prices') ? 'true' : 'false'; ?>,
      canEditPrices: <?php echo hasPermission('edit_concrete_prices') ? 'true' : 'false'; ?>
    };
    function printReport() {
      // Update print section with current data
      document.getElementById('print_date').textContent = new Date().toLocaleDateString('ku-IQ');
      document.getElementById('print_time').textContent = new Date().toLocaleTimeString('ku-IQ');
      
      // Copy summary data to print section
      document.getElementById('print_total_receipts').textContent = document.getElementById('total_receipts').textContent;
      document.getElementById('print_total_meter').textContent = document.getElementById('total_meter').textContent;
      document.getElementById('print_total_customers').textContent = document.getElementById('total_customers').textContent;
      
      // Create a better formatted print version of customer details
      const customerTable = document.getElementById('customerSummaryTable');
      if (customerTable) {
        const printTable = customerTable.cloneNode(true);
        
        // Remove action buttons from print version
        const actionCells = printTable.querySelectorAll('td:last-child');
        actionCells.forEach(cell => cell.remove());
        
        // Update header to remove action column
        const headerRow = printTable.querySelector('thead tr');
        if (headerRow) {
          const lastHeader = headerRow.querySelector('th:last-child');
          if (lastHeader) lastHeader.remove();
        }
        
        // Remove price-related columns from print if user doesn't have permission
        if (!window.userPermissions.canViewPrices) {
          // Remove price and notes columns from header
          const headers = headerRow.querySelectorAll('th');
          if (headers.length >= 6) {
            headers[4].remove(); // Remove price column
            headers[4].remove(); // Remove notes column (now at index 4 after removing price)
          }
          
          // Remove price and notes columns from all rows
          const rows = printTable.querySelectorAll('tbody tr');
          rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 6) {
              cells[4].remove(); // Remove price column
              cells[4].remove(); // Remove notes column (now at index 4 after removing price)
            }
          });
        }
        
        document.getElementById('print_customer_details').innerHTML = `
          <h3 style="margin-bottom: 20px; color: var(--seafoam-green);">پوختەی کڕیاران</h3>
          <div style="overflow-x: auto;">
            ${printTable.outerHTML}
          </div>
        `;
      }
      
      // Print
      window.print();
    }
  </script>
</body>

</html>
