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
        <link href="../assets/css/kurdish-font.css" rel="stylesheet">
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
    <!-- AG Grid CSS -->
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css" rel="stylesheet">
    <link href="../assets/css/comon/ag_grid.css" rel="stylesheet">
    <link href="../assets/css/customer_profile/ag_grid_customer_profile.css" rel="stylesheet">
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
            <!-- AG Grid Container for Sales -->
            <div class="table-responsive">
                <div id="customerSalesGrid" class="ag-grid-container ag-theme-alpine"></div>
            </div>
        </div>
        <div class="tab-pane fade" id="debt" role="tabpanel" aria-labelledby="debt-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">مێژووی دانەوەی قەرزەکان</h5>
                <button class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;" data-bs-toggle="modal" data-bs-target="#addCustomerDebtModal">
                  <i class="fa fa-plus"></i> دانەوەی قەرز
                </button>
            </div>
            <!-- AG Grid Container for Debt Payments -->
            <div class="table-responsive">
                <div id="customerDebtGrid" class="ag-grid-container ag-theme-alpine"></div>
            </div>
        </div>
    </div>
    <!-- Add Customer Debt Modal -->
    <div class="modal fade" id="addCustomerDebtModal" tabindex="-1" aria-labelledby="addCustomerDebtModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
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
                <div class="col-md-12">
                  <label for="customer_debt_payment_type" class="form-label">جۆری پارەدان</label>
                  <select class="form-control" id="customer_debt_payment_type" name="payment_type" required>
                    <option value="fifo">FIFO (یەکەم دەرچوو - یەکەم داهات)</option>
                    <option value="opening_debt_only">تەنها قەرزی سەرەتایی</option>
                    <option value="specific_sales">فرۆشتنێکی دیاریکراو</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3" id="specific_sales_section" style="display: none;">
                <div class="col-md-12">
                  <label class="form-label"><i class="fa fa-list"></i> هەڵبژاردنی فرۆشتنەکان</label>
                  <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> پارەکەت بۆ ئەم فرۆشتنانە دەبڕدرێت (دەتوانیت چەند فرۆشتنێک هەڵبژێریت):
                  </div>
                  <div class="card">
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                      <div id="sales_selection_container">
                        <!-- Sales will be loaded here by JavaScript -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="customer_debt_discount" class="form-label">داشکاندن (USD)</label>
                  <input type="number" class="form-control" id="customer_debt_discount" name="discount" min="0" step="0.0001" value="0">
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
      <div class="modal-dialog modal-xl">
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
                <div class="col-md-12">
                  <label for="edit_customer_debt_payment_type" class="form-label">جۆری پارەدان</label>
                  <select class="form-control" id="edit_customer_debt_payment_type" name="payment_type" required>
                    <option value="fifo">FIFO (یەکەم دەرچوو - یەکەم داهات)</option>
                    <option value="opening_debt_only">تەنها قەرزی سەرەتایی</option>
                    <option value="specific_sales">فرۆشتنێکی دیاریکراو</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3" id="edit_specific_sales_section" style="display: none;">
                <div class="col-md-12">
                  <label class="form-label"><i class="fa fa-list"></i> هەڵبژاردنی فرۆشتنەکان</label>
                  <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> پارەکەت بۆ ئەم فرۆشتنانە دەبڕدرێت (دەتوانیت چەند فرۆشتنێک هەڵبژێریت):
                  </div>
                  <div class="card">
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                      <div id="edit_sales_selection_container">
                        <!-- Sales will be loaded here by JavaScript -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="edit_customer_debt_discount" class="form-label">داشکاندن (USD)</label>
                  <input type="number" class="form-control" id="edit_customer_debt_discount" name="discount" min="0" step="0.0001">
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
            // تەنها ئەگەر هەردووکەیان گەورەتر بن لە سفر نیشانیان بدە
            if (s.total_debt_usd > 0 && s.total_debt_iqd > 0) {
                debtText = s.total_debt_usd.toLocaleString('en-US') + ' $ / ' + s.total_debt_iqd.toLocaleString('en-US') + ' د.ع';
            } else if (s.total_debt_usd > 0) {
                debtText = s.total_debt_usd.toLocaleString('en-US') + ' $';
            } else if (s.total_debt_iqd > 0) {
                debtText = s.total_debt_iqd.toLocaleString('en-US') + ' د.ع';
            } else {
                debtText = '0 $'; // ئەگەر هیچ قەرزێک نییە
            }
            $('#total-debt').text(debtText);
            $('#sales-count').text(s.sales_count);
            // Opening debt card
            let openingDebtText = '';
            // تەنها ئەگەر هەردووکەیان گەورەتر بن لە سفر نیشانیان بدە
            if (s.opening_debt_usd > 0 && s.opening_debt_iqd > 0) {
                openingDebtText = s.opening_debt_usd.toLocaleString('en-US') + ' $ / ' + s.opening_debt_iqd.toLocaleString('en-US') + ' د.ع';
            } else if (s.opening_debt_usd > 0) {
                openingDebtText = s.opening_debt_usd.toLocaleString('en-US') + ' $';
            } else if (s.opening_debt_iqd > 0) {
                openingDebtText = s.opening_debt_iqd.toLocaleString('en-US') + ' د.ع';
            } else {
                openingDebtText = '0 $'; // ئەگەر هیچ قەرزی سەرەتایی نییە
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
            
            // Refresh sales data - use AG Grid reload if available
            if (typeof reloadCustomerSales === 'function') {
                reloadCustomerSales();
            } else if (typeof loadCustomerSales === 'function') {
                loadCustomerSales(CUSTOMER_ID);
            }
            
            // Refresh debt payments data - use AG Grid reload if available
            if (typeof reloadCustomerDebts === 'function') {
                reloadCustomerDebts();
            } else if (typeof loadCustomerReturnDebts === 'function') {
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
    window.editPaymentAllocations = window.editPaymentAllocations || [];

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
                
                // Reset validation state
                setTimeout(() => {
                    validatePaymentInputs();
                }, 100);
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
                window.editPaymentAllocations = [];
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
                if (typeof calculateRemainingDebt === 'function') {
                    calculateRemainingDebt();
                }
            }
            
            [paidUsdInput, paidIqdInput, discountInput, dolarRateInput].forEach(input => {
                input.addEventListener('input', calculateRemaining);
            });
        }
        
        // Payment type change handlers
        const paymentTypeSelect = document.getElementById('customer_debt_payment_type');
        if (paymentTypeSelect) {
            paymentTypeSelect.addEventListener('change', handlePaymentTypeChange);
        }
        
        const editPaymentTypeSelect = document.getElementById('edit_customer_debt_payment_type');
        if (editPaymentTypeSelect) {
            editPaymentTypeSelect.addEventListener('change', handleEditPaymentTypeChange);
        }
        
        // Add real-time validation for payment amounts
        const paymentInputs = ['customer_debt_paid_usd', 'customer_debt_paid_iqd', 'customer_debt_discount', 'customer_debt_dolar_rate'];
        paymentInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', function() {
                    // Add visual feedback for validation
                    validatePaymentInputs();
                });
            }
        });
    });
    
    // Payment type selection handler
    function handlePaymentTypeChange() {
        const paymentType = document.getElementById('customer_debt_payment_type').value;
        const specificSalesSection = document.getElementById('specific_sales_section');
        
        if (paymentType === 'specific_sales') {
            specificSalesSection.style.display = 'block';
            loadSalesForSelection();
        } else {
            specificSalesSection.style.display = 'none';
        }
    }
    
    // Edit payment type selection handler
    function handleEditPaymentTypeChange() {
        const paymentType = document.getElementById('edit_customer_debt_payment_type').value;
        const specificSalesSection = document.getElementById('edit_specific_sales_section');
        
        if (paymentType === 'specific_sales') {
            specificSalesSection.style.display = 'block';
            loadSalesForEditSelection();
        } else {
            specificSalesSection.style.display = 'none';
        }
    }
    
    // Load sales for selection
    function loadSalesForSelection() {
        if (!CUSTOMER_ID) return;
        
        $.get('../process/customer_profile/select_sale.php', { 
            customer_id: CUSTOMER_ID, 
            remaining_only: 1 
        }, function(data) {
            if (data && data.sales) {
                const container = document.getElementById('sales_selection_container');
                container.innerHTML = '';
                
                data.sales.forEach(sale => {
                    const saleDiv = document.createElement('div');
                    saleDiv.className = 'card mb-3';
                    saleDiv.innerHTML = `
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <div class="form-check">
                                        <input class="form-check-input sale-checkbox" type="checkbox" 
                                               value="${sale.id}" id="sale_${sale.id}" 
                                               data-remaining="${sale.remaining_amount}">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <small class="text-muted">ژ.فاکتور:</small><br>
                                            <strong>${sale.invoice_number}</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">بەروار:</small><br>
                                            <strong>${sale.order_date}</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">ماوە:</small><br>
                                            <strong class="text-danger">${parseFloat(sale.remaining_amount).toFixed(2)} $</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">وەرگر:</small><br>
                                            <strong>${sale.recipient}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">بڕی پارە:</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control sale-amount" 
                                               data-sale-id="${sale.id}" min="0" max="${sale.remaining_amount}" 
                                               step="0.01" placeholder="0.00">
                                        <span class="input-group-text">$</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    container.appendChild(saleDiv);
                });
                
                // Add event listeners for amount inputs
                container.querySelectorAll('.sale-amount').forEach(input => {
                    input.addEventListener('input', function() {
                        validatePaymentInputs();
                    });
                });
                
                // Add event listeners for checkboxes
                container.querySelectorAll('.sale-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        validatePaymentInputs();
                    });
                });
            }
        }, 'json');
    }
    
    // Load sales for edit selection
    function loadSalesForEditSelection() {
        if (!CUSTOMER_ID) return;
        
        const params = { 
            customer_id: CUSTOMER_ID, 
            remaining_only: 1 
        };
        
        if (Array.isArray(window.editPaymentAllocations) && window.editPaymentAllocations.length > 0) {
            params.include_sales = window.editPaymentAllocations.map(item => item.sale_id).join(',');
        }
        
        $.get('../process/customer_profile/select_sale.php', params, function(data) {
            if (data && data.sales) {
                const container = document.getElementById('edit_sales_selection_container');
                container.innerHTML = '';
                const allocationMap = {};
                (window.editPaymentAllocations || []).forEach(item => {
                    allocationMap[item.sale_id] = parseFloat(item.allocated_amount);
                });
                
                data.sales.forEach(sale => {
                    const baseRemaining = parseFloat(sale.remaining_amount);
                    const allocatedAmount = allocationMap[sale.id] || 0;
                    const displayRemaining = (isNaN(baseRemaining) ? 0 : baseRemaining) + allocatedAmount;
                    const saleDiv = document.createElement('div');
                    saleDiv.className = 'card mb-3';
                    saleDiv.innerHTML = `
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <div class="form-check">
                                        <input class="form-check-input edit-sale-checkbox" type="checkbox" 
                                               value="${sale.id}" id="edit_sale_${sale.id}" 
                                               data-remaining="${sale.remaining_amount}">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <small class="text-muted">ژ.فاکتور:</small><br>
                                            <strong>${sale.invoice_number}</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">بەروار:</small><br>
                                            <strong>${sale.order_date}</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">ماوە:</small><br>
                                            <strong class="text-danger">${displayRemaining.toFixed(2)} $</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">وەرگر:</small><br>
                                            <strong>${sale.recipient}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">بڕی پارە:</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control edit-sale-amount" 
                                               data-sale-id="${sale.id}" min="0" max="${displayRemaining}" 
                                               step="0.01" placeholder="0.00">
                                        <span class="input-group-text">$</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    container.appendChild(saleDiv);
                });
                
                // Add event listeners for amount inputs
                container.querySelectorAll('.edit-sale-amount').forEach(input => {
                    input.addEventListener('input', function() {
                        validatePaymentInputs();
                    });
                });
                
                // Add event listeners for checkboxes
                container.querySelectorAll('.edit-sale-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        validatePaymentInputs();
                    });
                    
                    const allocationValue = allocationMap[checkbox.value] || 0;
                    if (allocationValue > 0) {
                        checkbox.checked = true;
                        const amountInput = container.querySelector(`.edit-sale-amount[data-sale-id="${checkbox.value}"]`);
                        if (amountInput) {
                            amountInput.value = allocationValue.toFixed(2);
                        }
                    }
                });
            }
        }, 'json');
    }
    
    // Validate specific sales payment
    function validateSpecificSalesPayment() {
        const checkboxes = document.querySelectorAll('.sale-checkbox:checked');
        const totalAmount = parseFloat(document.getElementById('customer_debt_paid_usd').value) || 0;
        const totalIqd = parseFloat(document.getElementById('customer_debt_paid_iqd').value) || 0;
        const dolarRate = parseFloat(document.getElementById('customer_debt_dolar_rate').value) || 150000;
        const discount = parseFloat(document.getElementById('customer_debt_discount').value) || 0;
        
        const totalPaidUsd = totalAmount + (totalIqd / (dolarRate / 100)) + discount;
        let allocatedAmount = 0;
        
        checkboxes.forEach(checkbox => {
            const amountInput = document.querySelector(`.sale-amount[data-sale-id="${checkbox.value}"]`);
            const amount = parseFloat(amountInput.value) || 0;
            allocatedAmount += amount;
        });
        
        if (Math.abs(allocatedAmount - totalPaidUsd) > 0.01) {
            alert('کۆی بڕی پارەی دابەشکراو دەبێت یەکسان بێت بە کۆی پارەی داوە!');
            return false;
        }
        
        return true;
    }
    
    // Comprehensive payment validation
    function validatePayment() {
        const paymentType = document.getElementById('customer_debt_payment_type').value;
        const totalAmount = parseFloat(document.getElementById('customer_debt_paid_usd').value) || 0;
        const totalIqd = parseFloat(document.getElementById('customer_debt_paid_iqd').value) || 0;
        const dolarRate = parseFloat(document.getElementById('customer_debt_dolar_rate').value) || 150000;
        const discount = parseFloat(document.getElementById('customer_debt_discount').value) || 0;
        
        const totalPaidUsd = totalAmount + (totalIqd / (dolarRate / 100)) + discount;
        
        if (totalPaidUsd <= 0) {
            alert('بڕی پارەی داوە دەبێت گەورەتر بێت لە سفر!');
            return false;
        }
        
        if (paymentType === 'opening_debt_only') {
            // Validate against opening debt only
            if (totalPaidUsd > CUSTOMER_OPENING_DEBT_USD) {
                alert(`بڕی پارەی داوە (${totalPaidUsd.toFixed(2)} $) نابێت زیاتر بێت لە قەرزی سەرەتایی (${CUSTOMER_OPENING_DEBT_USD.toFixed(2)} $)!`);
                return false;
            }
        } else if (paymentType === 'specific_sales') {
            // Validate specific sales selection
            const checkboxes = document.querySelectorAll('.sale-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('تکایە لانیکەم یەک فرۆشتن هەڵبژێرە!');
                return false;
            }
            
            // Check if any selected sale has invalid amount
            let hasInvalidAmount = false;
            let totalAllocated = 0;
            
            checkboxes.forEach(checkbox => {
                const amountInput = document.querySelector(`.sale-amount[data-sale-id="${checkbox.value}"]`);
                const amount = parseFloat(amountInput.value) || 0;
                const maxAmount = parseFloat(amountInput.getAttribute('max')) || 0;
                
                if (amount > maxAmount) {
                    alert(`بڕی پارە بۆ فرۆشتن ${checkbox.value} نابێت زیاتر بێت لە ${maxAmount.toFixed(2)} $!`);
                    hasInvalidAmount = true;
                }
                
                if (amount <= 0) {
                    alert(`بڕی پارە بۆ فرۆشتن ${checkbox.value} دەبێت گەورەتر بێت لە سفر!`);
                    hasInvalidAmount = true;
                }
                
                totalAllocated += amount;
            });
            
            if (hasInvalidAmount) return false;
            
            if (Math.abs(totalAllocated - totalPaidUsd) > 0.01) {
                alert(`کۆی بڕی پارەی دابەشکراو (${totalAllocated.toFixed(2)} $) دەبێت یەکسان بێت بە کۆی پارەی داوە (${totalPaidUsd.toFixed(2)} $)!`);
                return false;
            }
        } else {
            // FIFO validation - check against total debt
            const totalDebt = CUSTOMER_CURRENT_DEBT + CUSTOMER_OPENING_DEBT_USD;
            if (totalPaidUsd > totalDebt) {
                alert(`بڕی پارەی داوە (${totalPaidUsd.toFixed(2)} $) نابێت زیاتر بێت لە کۆی قەرز (${totalDebt.toFixed(2)} $)!`);
                return false;
            }
        }
        
        return true;
    }
    
    // Real-time validation with visual feedback
    function validatePaymentInputs() {
        const paymentType = document.getElementById('customer_debt_payment_type').value;
        const totalAmount = parseFloat(document.getElementById('customer_debt_paid_usd').value) || 0;
        const totalIqd = parseFloat(document.getElementById('customer_debt_paid_iqd').value) || 0;
        const dolarRate = parseFloat(document.getElementById('customer_debt_dolar_rate').value) || 150000;
        const discount = parseFloat(document.getElementById('customer_debt_discount').value) || 0;
        
        const totalPaidUsd = totalAmount + (totalIqd / (dolarRate / 100)) + discount;
        
        // Reset all input styles
        const inputs = ['customer_debt_paid_usd', 'customer_debt_paid_iqd', 'customer_debt_discount'];
        inputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.classList.remove('is-invalid', 'is-valid');
            }
        });
        
        // Validate based on payment type
        let isValid = true;
        let errorMessage = '';
        
        if (totalPaidUsd <= 0) {
            isValid = false;
            errorMessage = 'بڕی پارەی داوە دەبێت گەورەتر بێت لە سفر!';
        } else if (paymentType === 'opening_debt_only') {
            if (totalPaidUsd > CUSTOMER_OPENING_DEBT_USD) {
                isValid = false;
                errorMessage = `بڕی پارەی داوە نابێت زیاتر بێت لە قەرزی سەرەتایی (${CUSTOMER_OPENING_DEBT_USD.toFixed(2)} $)!`;
            }
        } else if (paymentType === 'specific_sales') {
            const checkboxes = document.querySelectorAll('.sale-checkbox:checked');
            if (checkboxes.length === 0) {
                isValid = false;
                errorMessage = 'تکایە لانیکەم یەک فرۆشتن هەڵبژێرە!';
            } else {
                let totalAllocated = 0;
                let hasInvalidAmount = false;
                
                checkboxes.forEach(checkbox => {
                    const amountInput = document.querySelector(`.sale-amount[data-sale-id="${checkbox.value}"]`);
                    const amount = parseFloat(amountInput.value) || 0;
                    const maxAmount = parseFloat(amountInput.getAttribute('max')) || 0;
                    
                    if (amount > maxAmount) {
                        isValid = false;
                        errorMessage = `بڕی پارە بۆ فرۆشتن ${checkbox.value} نابێت زیاتر بێت لە ${maxAmount.toFixed(2)} $!`;
                        hasInvalidAmount = true;
                    }
                    
                    if (amount <= 0) {
                        isValid = false;
                        errorMessage = `بڕی پارە بۆ فرۆشتن ${checkbox.value} دەبێت گەورەتر بێت لە سفر!`;
                        hasInvalidAmount = true;
                    }
                    
                    totalAllocated += amount;
                });
                
                if (!hasInvalidAmount && Math.abs(totalAllocated - totalPaidUsd) > 0.01) {
                    isValid = false;
                    errorMessage = `کۆی بڕی پارەی دابەشکراو (${totalAllocated.toFixed(2)} $) دەبێت یەکسان بێت بە کۆی پارەی داوە (${totalPaidUsd.toFixed(2)} $)!`;
                }
            }
        } else {
            // FIFO validation
            const totalDebt = CUSTOMER_CURRENT_DEBT + CUSTOMER_OPENING_DEBT_USD;
            if (totalPaidUsd > totalDebt) {
                isValid = false;
                errorMessage = `بڕی پارەی داوە نابێت زیاتر بێت لە کۆی قەرز (${totalDebt.toFixed(2)} $)!`;
            }
        }
        
        // Apply visual feedback
        inputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input && totalPaidUsd > 0) {
                input.classList.add(isValid ? 'is-valid' : 'is-invalid');
            }
        });
        
        // Update submit button state
        const submitBtn = document.querySelector('#addCustomerDebtForm button[type="submit"]');
        if (submitBtn) {
            if (!isValid) {
                submitBtn.disabled = true;
                submitBtn.classList.add('btn-danger');
                submitBtn.classList.remove('btn-primary');
            } else {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-danger');
                submitBtn.classList.add('btn-primary');
            }
        }
        
        // Show/hide error message
        let errorDiv = document.getElementById('payment-validation-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'payment-validation-error';
            errorDiv.className = 'alert alert-danger mt-2';
            document.getElementById('addCustomerDebtForm').appendChild(errorDiv);
        }
        
        if (!isValid && errorMessage) {
            errorDiv.innerHTML = `<i class="fa fa-exclamation-triangle"></i> ${errorMessage}`;
            errorDiv.style.display = 'block';
        } else {
            errorDiv.style.display = 'none';
        }
        
        return isValid;
    }
    
    // Make validation function globally available
    window.validatePayment = validatePayment;
    window.validatePaymentInputs = validatePaymentInputs;
</script>




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<!-- AG Grid JS -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js"></script>
<script src="../assets/js/comon/ag_grid_base.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/customer_profile/ag_grid_customer_sales.js"></script>
<script src="../assets/js/customer_profile/ag_grid_customer_debt.js"></script>
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
