<?php
session_start();
require_once '../config/db_conected.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if customer ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<!DOCTYPE html>
    <html lang="ku" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>هەڵە - ناسنامەی کڕیار دیاری نەکراوە</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
        <link href="../assets/css/variables.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    </head>
    <body>
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;background: linear-gradient(135deg, var(--seafoam-green) 0%, var(--kelly-green) 100%);">
            <div class="text-center bg-white p-5 rounded shadow" style="max-width: 500px;">
                <i class="fas fa-exclamation-triangle" style="font-size:5rem;color:#ffc107;margin-bottom:20px;"></i>
                <h2 style="color:#666;margin-bottom:15px;">ناسنامەی کڕیار دیاری نەکراوە</h2>
                <p style="color:#888;margin-bottom:25px;">تکایە کڕیارێک هەڵبژێرە لە لیستی کڕیارەکان</p>
                <a href="add_customers.php" class="btn btn-primary" style="background: var(--seafoam-green); border: none; padding: 10px 25px; font-weight: bold;">
                    <i class="fas fa-users"></i> گەڕانەوە بۆ لیستی کڕیارەکان
                </a>
            </div>
        </div>
    </body>
    </html>';
    exit;
}
// require_once '../config/permissions.php';
// if (!hasPermission('view_customer')) {
//     echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
//         .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
//         .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
//         .'</div>';
//     exit;
// }
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$customer_name = '';

// Debug: Log the customer ID
error_log('Customer Profile - Customer ID: ' . $customer_id . ', GET params: ' . print_r($_GET, true));

if ($customer_id) {
    $stmt = $pdo->prepare('SELECT name FROM customers WHERE id = ?');
    $stmt->execute([$customer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $customer_name = $row ? $row['name'] : '';
    
    if (!$customer_name) {
        error_log('Customer Profile - Customer not found for ID: ' . $customer_id);
        echo '<!DOCTYPE html>
        <html lang="ku" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>هەڵە - کڕیار نەدۆزرایەوە</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
            <link href="../assets/css/variables.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        </head>
        <body>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;background: linear-gradient(135deg, var(--seafoam-green) 0%, var(--kelly-green) 100%);">
                <div class="text-center bg-white p-5 rounded shadow" style="max-width: 500px;">
                    <i class="fas fa-user-times" style="font-size:5rem;color:#dc3545;margin-bottom:20px;"></i>
                    <h2 style="color:#666;margin-bottom:15px;">کڕیار نەدۆزرایەوە</h2>
                    <p style="color:#888;margin-bottom:25px;">کڕیارێک بە ناسنامەی ' . $customer_id . ' نەدۆزرایەوە</p>
                    <a href="add_customers.php" class="btn btn-primary" style="background: var(--seafoam-green); border: none; padding: 10px 25px; font-weight: bold;">
                        <i class="fas fa-users"></i> گەڕانەوە بۆ لیستی کڕیارەکان
                    </a>
                </div>
            </div>
        </body>
        </html>';
        exit;
    }
} else {
    error_log('Customer Profile - No customer ID provided');
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرۆفایلی کڕیار</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Placeholder for customer profile JS includes -->
<style>
.nav-tabs .nav-link {
    color: var(--seafoam-green) !important;
}
.nav-tabs .nav-link.active {
    background: var(--seafoam-green) !important;
    color: #fff !important;
    border-color: var(--seafoam-green) var(--seafoam-green) #fff !important;
}
</style>
  </head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">
            <?php echo htmlspecialchars($customer_name); ?>
        </h2>
        <button class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;" onclick="window.location.href='receipts.php?id=' + CUSTOMER_ID">
            <i class="fa fa-print"></i> پرێنت
        </button>
    </div>
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="add_customers.php" class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">
            <i class="fa fa-arrow-right"></i> گەڕانەوە بۆ لیستی کڕیارەکان
        </a>
    </div>
    <div class="row mb-3" id="customer-summary-cards">
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow  card-gradient-danger card-animate-hover">
          <div class="card-body">
            <i class="fas fa-money-bill-wave card-icon"></i>
            <h6 class="card-title">کۆی قەرز</h6>
            <div class="fs-4 fw-bold" id="total-debt">...</div>
            <small class="text-light">کۆی قەرزی کڕیار</small>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow  card-gradient-info card-animate-hover">
          <div class="card-body">
            <i class="fas fa-shopping-cart card-icon"></i>
            <h6 class="card-title">ژمارەی مامەڵەکان</h6>
            <div class="fs-4 fw-bold" id="sales-count">...</div>
            <small class="text-light">ژمارەی فرۆشتنەکان</small>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="card text-center shadow  card-gradient-warning card-animate-hover">
          <div class="card-body">
            <i class="fas fa-credit-card card-icon"></i>
            <h6 class="card-title">کۆی قەرزی سەرەتایی</h6>
            <div class="fs-4 fw-bold" id="opening-debt">...</div>
            <small class="text-light">قەرزی سەرەتایی</small>
          </div>
        </div>
      </div>
    </div>
    <div class="row mb-4" id="customer-cards">
        <!-- Cards will be loaded here by JS -->
    </div>
    <ul class="nav nav-tabs mb-3" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab" aria-controls="sales" aria-selected="true">مێژووی فرۆشتن</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="debt-tab" data-bs-toggle="tab" data-bs-target="#debt" type="button" role="tab" aria-controls="debt" aria-selected="false">مێژووی دانەوەی قەرزەکان</button>
        </li>
    </ul>
    <div class="tab-content" id="profileTabsContent">
        <div class="tab-pane fade show active" id="sales" role="tabpanel" aria-labelledby="sales-tab">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center" id="salesTable">
                    <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                        <tr>
                            <th>#</th>
                            <th>کڕیار</th>
                            <th>شوێن</th>
                            <th>وەرگر</th>
                            <th>ژمارەی پسوڵە</th>
                            <th>فۆرمۆلا</th>
                            <th>بەروار</th>
                            <th>جۆری پارەدان</th>
                            <th>بڕ</th>
                            <th>نرخی یەکە</th>
                            <th>کۆی نرخ</th>
                            <th>پارەی دراو بە دینار</th>
                            <th>پارەی دراو بە دۆلار</th>
                            <th>پارەی ماوە</th>
                            <th>نرخی ١٠٠ دۆلار</th>
                            <th>داشکاندن</th>
                            <th>تێبینی</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sales will be loaded here by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="debt" role="tabpanel" aria-labelledby="debt-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">مێژووی دانەوەی قەرزەکان</h5>
                <button class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;" data-bs-toggle="modal" data-bs-target="#addCustomerDebtModal">
                  <i class="fa fa-plus"></i> دانەوەی قەرز
                </button>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle text-center" id="customerDebtTable">
                <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                  <tr>
                    <th>#</th>
                    <th>بەروار</th>
                    <th>نرخی ١٠٠ دۆلار</th>
                    <th>بڕی داوە (USD)</th>
                    <th>بڕی داوە (IQD)</th>
                    <th>داشکاندن</th>
                    <th>تێبینی</th>
                    <th>کردارەکان</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Debt payments will be loaded here by JS -->
                </tbody>
              </table>
            </div>
        </div>
    </div>
    <!-- Add Customer Debt Modal -->
    <div class="modal fade" id="addCustomerDebtModal" tabindex="-1" aria-labelledby="addCustomerDebtModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="addCustomerDebtForm">
            <div class="modal-header">
              <h5 class="modal-title" id="addCustomerDebtModalLabel">دانەوەی قەرز</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="customer_debt_date" class="form-label">بەروار</label>
                  <input type="date" class="form-control" id="customer_debt_date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-6">
                  <label for="customer_debt_dolar_rate" class="form-label">نرخی ١٠٠ دۆلار</label>
                  <input type="number" class="form-control" id="customer_debt_dolar_rate" name="dolar_rate" min="0" step="0.01" value="150000">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="customer_debt_paid_usd" class="form-label">بڕی پارەی داوە (USD)</label>
                  <input type="number" class="form-control" id="customer_debt_paid_usd" name="paid_usd" min="0" step="0.01" value="0">
                </div>
                <div class="col-md-6">
                  <label for="customer_debt_paid_iqd" class="form-label">بڕی پارەی داوە (IQD)</label>
                  <input type="number" class="form-control" id="customer_debt_paid_iqd" name="paid_iqd" min="0" step="1" value="0">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="customer_debt_discount" class="form-label">داشکاندن (USD)</label>
                  <input type="number" class="form-control" id="customer_debt_discount" name="discount" min="0" step="0.01" value="0">
                </div>
                <div class="col-md-6">
                  <label for="customer_debt_remaining" class="form-label">قەرزی ماوە (USD)</label>
                  <input type="text" class="form-control" id="customer_debt_remaining" readonly style="background-color: #f8f9fa;">
                </div>
              </div>
              <div class="mb-3">
                <label for="customer_debt_note" class="form-label">تێبینی</label>
                <textarea class="form-control" id="customer_debt_note" name="note" rows="2"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
              <button type="submit" class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">دانەوە</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Edit Customer Debt Modal -->
    <div class="modal fade" id="editCustomerDebtModal" tabindex="-1" aria-labelledby="editCustomerDebtModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="editCustomerDebtForm">
            <input type="hidden" id="edit_customer_debt_id" name="id">
            <div class="modal-header">
              <h5 class="modal-title" id="editCustomerDebtModalLabel">تازەکردنەوەی دانەوەی قەرز</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="edit_customer_debt_date" class="form-label">بەروار</label>
                  <input type="date" class="form-control" id="edit_customer_debt_date" name="date" required>
                </div>
                <div class="col-md-6">
                  <label for="edit_customer_debt_dolar_rate" class="form-label">نرخی ١٠٠ دۆلار</label>
                  <input type="number" class="form-control" id="edit_customer_debt_dolar_rate" name="dolar_rate" min="0" step="0.01">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="edit_customer_debt_paid_usd" class="form-label">بڕی پارەی داوە (USD)</label>
                  <input type="number" class="form-control" id="edit_customer_debt_paid_usd" name="paid_usd" min="0" step="0.01">
                </div>
                <div class="col-md-6">
                  <label for="edit_customer_debt_paid_iqd" class="form-label">بڕی پارەی داوە (IQD)</label>
                  <input type="number" class="form-control" id="edit_customer_debt_paid_iqd" name="paid_iqd" min="0" step="1">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="edit_customer_debt_discount" class="form-label">داشکاندن (USD)</label>
                  <input type="number" class="form-control" id="edit_customer_debt_discount" name="discount" min="0" step="0.01">
                </div>
              </div>
              <div class="mb-3">
                <label for="edit_customer_debt_note" class="form-label">تێبینی</label>
                <textarea class="form-control" id="edit_customer_debt_note" name="note" rows="2"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
              <button type="submit" class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">تازەکردنەوە</button>
            </div>
          </form>
        </div>
      </div>
    </div>
</div>
<script>
    const CUSTOMER_ID = <?php echo $customer_id; ?>;
    
    // Debug: Log the customer ID
    console.log('PHP Customer ID:', <?php echo $customer_id; ?>);
    console.log('JavaScript CUSTOMER_ID:', CUSTOMER_ID);
    console.log('URL Parameters:', window.location.search);
    
    function loadCustomerSummaryCards() {
        if (!CUSTOMER_ID || CUSTOMER_ID <= 0) {
            console.error('Invalid customer ID:', CUSTOMER_ID);
            return;
        }
        
        $.get('../process/customer_profile/select_sale.php', { customer_id: CUSTOMER_ID, stats: 1 }, function(data) {
            if (!data || !data.stats) {
                console.error('API returned no data or missing stats:', data);
                return;
            }
            const s = data.stats;
            let debtText = '';
            if (s.total_debt_usd > 0 && s.total_debt_iqd > 0) {
                debtText = s.total_debt_usd.toLocaleString('en-US') + ' $ / ' + s.total_debt_iqd.toLocaleString('en-US') + ' د.ع';
            } else if (s.total_debt_usd > 0) {
                debtText = s.total_debt_usd.toLocaleString('en-US') + ' $';
            } else {
                debtText = s.total_debt_iqd.toLocaleString('en-US') + ' د.ع';
            }
            $('#total-debt').text(debtText);
            $('#sales-count').text(s.sales_count);
            // Opening debt card
            let openingDebtText = '';
            if (s.opening_debt_usd > 0 && s.opening_debt_iqd > 0) {
                openingDebtText = s.opening_debt_usd.toLocaleString('en-US') + ' $ / ' + s.opening_debt_iqd.toLocaleString('en-US') + ' د.ع';
            } else if (s.opening_debt_usd > 0) {
                openingDebtText = s.opening_debt_usd.toLocaleString('en-US') + ' $';
            } else {
                openingDebtText = s.opening_debt_iqd.toLocaleString('en-US') + ' د.ع';
            }
            $('#opening-debt').text(openingDebtText);
        }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX error loading customer summary cards:', textStatus, errorThrown, jqXHR.responseText);
        });
    }
    
    // Make loadCustomerSummaryCards globally available
    window.loadCustomerSummaryCards = loadCustomerSummaryCards;
    
    $(function() { loadCustomerSummaryCards(); });
</script>

<script>
    // Function to refresh all customer data after any activity
    function refreshCustomerData() {
        if (typeof CUSTOMER_ID !== 'undefined' && CUSTOMER_ID && CUSTOMER_ID > 0) {
            // Refresh summary cards
            if (typeof loadCustomerSummaryCards === 'function') {
                loadCustomerSummaryCards();
            }
            
            // Refresh sales data
            if (typeof loadCustomerSales === 'function') {
                loadCustomerSales(CUSTOMER_ID);
            }
            
            // Refresh debt payments data
            if (typeof loadCustomerReturnDebts === 'function') {
                loadCustomerReturnDebts(CUSTOMER_ID);
            }
            
            console.log('Customer data refreshed automatically');
        }
    }
    
    // Make refreshCustomerData globally available
    window.refreshCustomerData = refreshCustomerData;
</script>

<script>
    // Modal improvement functions
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize modals with better event handling
        const addModal = document.getElementById('addCustomerDebtModal');
        const editModal = document.getElementById('editCustomerDebtModal');
        
        if (addModal) {
            addModal.addEventListener('show.bs.modal', function() {
                // Reset form when opening add modal
                document.getElementById('addCustomerDebtForm').reset();
                document.getElementById('customer_debt_date').value = new Date().toISOString().split('T')[0];
                // Dollar rate will be updated by API in add_return_debt.js
            });
        }
        
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function() {
                // Clear any previous error states
                const inputs = editModal.querySelectorAll('.form-control');
                inputs.forEach(input => input.classList.remove('is-invalid'));
                // Dollar rate will be updated by API in update_return_debt.js
            });
            
            editModal.addEventListener('hidden.bs.modal', function() {
                // Reset form when closing edit modal
                document.getElementById('editCustomerDebtForm').reset();
                document.getElementById('edit_customer_debt_id').value = '';
            });
        }
        
        // Add automatic calculation for remaining debt in add modal
        const addForm = document.getElementById('addCustomerDebtForm');
        if (addForm) {
            const paidUsdInput = addForm.querySelector('#customer_debt_paid_usd');
            const paidIqdInput = addForm.querySelector('#customer_debt_paid_iqd');
            const discountInput = addForm.querySelector('#customer_debt_discount');
            const dolarRateInput = addForm.querySelector('#customer_debt_dolar_rate');
            const remainingInput = addForm.querySelector('#customer_debt_remaining');
            
            function calculateRemaining() {
                const paidUsd = parseFloat(paidUsdInput.value) || 0;
                const paidIqd = parseFloat(paidIqdInput.value) || 0;
                const discount = parseFloat(discountInput.value) || 0;
                const dolarRate = parseFloat(dolarRateInput.value) || 150000;
                
                const paidIqdUsd = dolarRate > 0 ? paidIqd / (dolarRate / 100) : 0;
                const totalPaid = paidUsd + paidIqdUsd + discount;
                
                // Get customer's total debt (this would need to be fetched from server)
                // For now, just show the calculated total
                remainingInput.value = totalPaid.toFixed(2) + ' USD';
            }
            
            [paidUsdInput, paidIqdInput, discountInput, dolarRateInput].forEach(input => {
                input.addEventListener('input', calculateRemaining);
            });
        }
    });
</script>




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/customer_profile/select_sale.js"></script>
<script src="../assets/js/customer_profile/select_return_debt.js"></script>
<script src="../assets/js/customer_profile/add_return_debt.js"></script>
<script src="../assets/js/customer_profile/customer_profile.js"></script>
<script src="../assets/js/customer_profile/delete_return_debt.js"></script>
<script src="../assets/js/customer_profile/update_return_debt.js"></script>

<script>
    // Debugging script to help identify issues
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== Customer Profile Debug Info ===');
        console.log('CUSTOMER_ID:', typeof CUSTOMER_ID !== 'undefined' ? CUSTOMER_ID : 'undefined');
        console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
        console.log('jQuery available:', typeof $ !== 'undefined');
        console.log('Swal available:', typeof Swal !== 'undefined');
        
        // Check if all required elements exist
        const requiredElements = [
            'editCustomerDebtModal',
            'editCustomerDebtForm',
            'addCustomerDebtModal',
            'addCustomerDebtForm',
            'customerDebtTable'
        ];
        
        requiredElements.forEach(id => {
            const element = document.getElementById(id);
            console.log(`Element #${id}:`, element ? 'Found' : 'Missing');
        });
        
        // Check if all required functions are available
        const requiredFunctions = [
            'loadCustomerReturnDebts',
            'loadCustomerSales',
            'loadCustomerSummaryCards',
            'refreshCustomerData'
        ];
        
        requiredFunctions.forEach(funcName => {
            console.log(`Function ${funcName}:`, typeof window[funcName] === 'function' ? 'Available' : 'Missing');
        });
        
        console.log('=== End Debug Info ===');
    });
</script>

</body>
</html>
