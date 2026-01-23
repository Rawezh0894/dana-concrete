<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!hasPermission('view_company')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
$company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$company_name = '';
if ($company_id) {
    $stmt = $pdo->prepare('SELECT name FROM company WHERE id = ?');
    $stmt->execute([$company_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $company_name = $row ? $row['name'] : '';
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>پرۆفایلی کۆمپانیا</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- jQuery (پێش هەموو شت) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <!-- select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css" rel="stylesheet">
    <!-- DataTables Buttons CSS -->
    <link href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.min.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">
            <?php echo htmlspecialchars($company_name); ?>
        </h2>
        <a href="company_receipts.php?id=<?php echo $company_id; ?>" class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;" target="_blank">
            <i class="fa fa-print"></i> پرینت
        </a>
    </div>
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="add_company.php" class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">
            <i class="fa fa-arrow-right"></i> گەڕانەوە بۆ لیستی کۆمپانیاکان
        </a>
        <div class="d-flex align-items-center gap-3" id="date-filter-container" style="flex-wrap: wrap;">
            <div class="d-flex align-items-center gap-2">
                <label for="from_date" class="form-label mb-0" style="font-weight: bold;">لە:</label>
                <input type="date" class="form-control" id="from_date" style="width: 180px;">
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="to_date" class="form-label mb-0" style="font-weight: bold;">بۆ:</label>
                <input type="date" class="form-control" id="to_date" style="width: 180px;">
            </div>
            <button class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;" onclick="applyFilters()">
                <i class="fa fa-filter"></i> فلتەر
            </button>
            <button class="btn btn-secondary" onclick="resetFilters()">
                <i class="fa fa-redo"></i> پاککردنەوە
            </button>
        </div>
    </div>

    <div class="row mb-3" id="company-info-cards">
        <div class="col-md-4 col-lg-3 mb-2">
            <div class="card text-center shadow  card-gradient-danger card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-money-bill-wave card-icon"></i>
                    <h6 class="card-title">کۆی قەرز</h6>
                    <div class="fs-4 fw-bold" id="total-debt">...</div>
                    <small class="text-light">کۆی قەرزی کۆمپانیا</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3 mb-2">
            <div class="card text-center shadow  card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-shopping-cart card-icon"></i>
                    <h6 class="card-title">ژمارەی کڕینە قەرزەکان</h6>
                    <div class="fs-4 fw-bold" id="credit-count">...</div>
                    <small class="text-light">ژمارەی کڕینەکان</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3 mb-2">
            <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-credit-card card-icon"></i>
                    <h6 class="card-title">کۆی قەرزی سەرەتایی</h6>
                    <div class="fs-4 fw-bold" id="opening-debt">...</div>
                    <small class="text-light">قەرزی سەرەتایی</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3 mb-2">
            <div class="card text-center shadow  card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-tags card-icon"></i>
                    <h6 class="card-title">کۆی نرخ</h6>
                    <div class="fs-4 fw-bold" id="total-price">...</div>
                    <small class="text-light">بەپێی فلتەرەکان</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3 mb-2">
            <div class="card text-center shadow  card-gradient-primary card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-weight-hanging card-icon"></i>
                    <h6 class="card-title">کۆی کیلۆگرام</h6>
                    <div class="fs-4 fw-bold" id="total-kg">...</div>
                    <small class="text-light">بەپێی فلتەرەکان</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs for purchase and debt payment history -->
    <ul class="nav nav-tabs mb-3" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button" role="tab" aria-controls="purchases" aria-selected="true">مێژووی کڕینەکان</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="debt-tab" data-bs-toggle="tab" data-bs-target="#debt" type="button" role="tab" aria-controls="debt" aria-selected="false">مێژووی دانەوەی قەرزەکان</button>
        </li>
    </ul>
    <div class="tab-content" id="profileTabsContent">
        <div class="tab-pane fade show active" id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center" id="purchasesTable">
                    <!-- DataTables will build the table structure -->
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="debt" role="tabpanel" aria-labelledby="debt-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">مێژووی دانەوەی قەرزەکان</h5>
                <button class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;" data-bs-toggle="modal" data-bs-target="#addDebtModal"><i class="fa fa-plus"></i> دانەوەی قەرز</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center" id="debtTable">
                    <!-- DataTables will build the table structure -->
                </table>
            </div>
        </div>
    </div>
    <!-- Add Debt Modal -->
    <div class="modal fade" id="addDebtModal" tabindex="-1" aria-labelledby="addDebtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <form id="addDebtForm">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addDebtModalLabel">دانەوەی قەرز</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="debt_date" class="form-label">بەروار</label>
                            <input type="date" class="form-control" id="debt_date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">بڕی قەرز</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="debt_amount_usd" class="form-label">دۆلار</label>
                                    <input type="number" class="form-control" id="debt_amount_usd" name="amount_usd" min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-6">
                                    <label for="debt_amount_iqd" class="form-label">دینار</label>
                                    <input type="number" class="form-control" id="debt_amount_iqd" name="amount_iqd" min="0" step="0.01" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">داشکاندن</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="debt_discount_usd" class="form-label">دۆلار</label>
                                    <input type="number" class="form-control" id="debt_discount_usd" name="discount_usd" min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-6">
                                    <label for="debt_discount_iqd" class="form-label">دینار</label>
                                    <input type="number" class="form-control" id="debt_discount_iqd" name="discount_iqd" min="0" step="0.01" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="debt_dollar_rate" class="form-label">نرخی دۆلار</label>
                            <input type="number" class="form-control" id="debt_dollar_rate" name="dollar_rate" min="0" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="debt_note" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="debt_note" name="note" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">کۆی پارەی ماوە (تەنها بۆ زانیاری)</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="total_remaining_usd" readonly placeholder="دۆلار">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="total_remaining_iqd" readonly placeholder="دینار">
                                </div>
                            </div>
                            <small class="form-text text-muted">کۆی پارەی ماوەی مامەڵەکانی کڕین + بڕی قەرزە سەرەتایی</small>
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
    <!-- Edit Debt Modal -->
    <div class="modal fade" id="editDebtModal" tabindex="-1" aria-labelledby="editDebtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <form id="editDebtForm">
                    <input type="hidden" id="edit_debt_id" name="id">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editDebtModalLabel">دەستکاری دانەوەی قەرز</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_debt_date" class="form-label">بەروار</label>
                            <input type="date" class="form-control" id="edit_debt_date" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">بڕی قەرز</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="edit_debt_amount_usd" class="form-label">دۆلار</label>
                                    <input type="number" class="form-control" id="edit_debt_amount_usd" name="amount_usd" min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_debt_amount_iqd" class="form-label">دینار</label>
                                    <input type="number" class="form-control" id="edit_debt_amount_iqd" name="amount_iqd" min="0" step="0.01" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">داشکاندن</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="edit_debt_discount_usd" class="form-label">دۆلار</label>
                                    <input type="number" class="form-control" id="edit_debt_discount_usd" name="discount_usd" min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_debt_discount_iqd" class="form-label">دینار</label>
                                    <input type="number" class="form-control" id="edit_debt_discount_iqd" name="discount_iqd" min="0" step="0.01" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_debt_dollar_rate" class="form-label">نرخی دۆلار</label>
                            <input type="number" class="form-control" id="edit_debt_dollar_rate" name="dollar_rate" min="0" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="edit_debt_note" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="edit_debt_note" name="note" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">پاشەکەوتکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script nonce="<?php echo $csp_nonce; ?>">
    const COMPANY_ID = <?php echo $company_id; ?>;
    let currentFilters = { from_date: '', to_date: '' };
    
    function loadCompanyInfoCards() {
        const params = { company_id: COMPANY_ID, stats: 1 };
        if (currentFilters.from_date) params.from_date = currentFilters.from_date;
        if (currentFilters.to_date) params.to_date = currentFilters.to_date;
        
        $.get('../process/company_profile/select_debt.php', params, function(data) {
            if (!data || !data.stats) return;
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
            $('#credit-count').text(s.credit_count);
            let openingDebtText = '';
            if (s.opening_debt_usd > 0 && s.opening_debt_iqd > 0) {
                openingDebtText = s.opening_debt_usd.toLocaleString('en-US') + ' $ / ' + s.opening_debt_iqd.toLocaleString('en-US') + ' د.ع';
            } else if (s.opening_debt_usd > 0) {
                openingDebtText = s.opening_debt_usd.toLocaleString('en-US') + ' $';
            } else {
                openingDebtText = s.opening_debt_iqd.toLocaleString('en-US') + ' د.ع';
            }
            $('#opening-debt').text(openingDebtText);

            // Total price (per filters)
            let priceText = '';
            if ((s.total_price_usd ?? 0) > 0 && (s.total_price_iqd ?? 0) > 0) {
                priceText = s.total_price_usd.toLocaleString('en-US') + ' $ / ' + s.total_price_iqd.toLocaleString('en-US') + ' د.ع';
            } else if ((s.total_price_usd ?? 0) > 0) {
                priceText = s.total_price_usd.toLocaleString('en-US') + ' $';
            } else {
                priceText = s.total_price_iqd.toLocaleString('en-US') + ' د.ع';
            }
            $('#total-price').text(priceText);
            
            // Total KG (per filters)
            const totalKg = s.total_kg ?? 0;
            $('#total-kg').text(totalKg.toLocaleString('en-US'));
        }, 'json');
    }
    
    function applyFilters() {
        currentFilters.from_date = $('#from_date').val() || '';
        currentFilters.to_date = $('#to_date').val() || '';
        
        // Validate dates
        if (currentFilters.from_date && currentFilters.to_date && currentFilters.from_date > currentFilters.to_date) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'بەرواری سەرەتاکە نابێت گەورەتر بێت لە بەرواری کۆتایی',
                confirmButtonColor: '#20b2aa'
            });
            return;
        }
        
        loadCompanyInfoCards();
        if (typeof loadPurchases === 'function') loadPurchases();
        if (typeof loadDebts === 'function') loadDebts();
    }
    
    function resetFilters() {
        $('#from_date').val('');
        $('#to_date').val('');
        currentFilters.from_date = '';
        currentFilters.to_date = '';
        
        loadCompanyInfoCards();
        if (typeof loadPurchases === 'function') loadPurchases();
        if (typeof loadDebts === 'function') loadDebts();
    }
    
    $(function() { 
        // Set default dates to current month
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        $('#from_date').val(firstDay.toISOString().split('T')[0]);
        $('#to_date').val(lastDay.toISOString().split('T')[0]);
        
        currentFilters.from_date = $('#from_date').val();
        currentFilters.to_date = $('#to_date').val();
        
        loadCompanyInfoCards(); 
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<!-- DataTables Buttons JS -->
<script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/company_profile/company_profile.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/company_profile/select_purchases.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/company_profile/add_debt.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/company_profile/select_debt.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/company_profile/update_debt.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/company_profile/delete_debt.js" nonce="<?php echo $csp_nonce; ?>"></script>
<style>
.nav-tabs .nav-link {
    color: var(--seafoam-green) !important;
}
.nav-tabs .nav-link.active {
    background: var(--seafoam-green) !important;
    color: #fff !important;
    border-color: var(--seafoam-green) var(--seafoam-green) #fff !important;
}

/* DataTables Custom Styling */
.dataTables_wrapper {
    margin-top: 1rem;
}

.dataTables_wrapper .dataTables_length select {
    padding: 0.375rem 1.75rem 0.375rem 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background-color: #fff;
}

.dataTables_wrapper .dataTables_filter input {
    padding: 0.375rem 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    margin-right: 0.5rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin-left: 2px;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background-color: #fff;
    color: #495057 !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: var(--seafoam-green) !important;
    border-color: var(--seafoam-green) !important;
    color: #fff !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: var(--seafoam-green) !important;
    border-color: var(--seafoam-green) !important;
    color: #fff !important;
}

.dataTables_wrapper .dataTables_info {
    color: #6c757d;
}

.table thead {
    background: var(--kelly-green);
}

.table thead th {
    background-color: var(--kelly-green) !important;
    color: var(--seafoam-green) !important;
}

/* Column filter inputs styling */
.column-filter {
    background: rgba(255, 255, 255, 0.95) !important;
    font-size: 0.8rem !important;
}

.column-filter:focus {
    background: #fff !important;
    border-color: var(--seafoam-green) !important;
    outline: none !important;
    box-shadow: 0 0 0 0.2rem rgba(32, 178, 170, 0.25) !important;
}

.column-filter::placeholder {
    color: #999 !important;
    font-size: 0.75rem !important;
}

/* DataTables sort indicator */
table.dataTable thead .sorting,
table.dataTable thead .sorting_asc,
table.dataTable thead .sorting_desc,
table.dataTable thead .sorting_asc_disabled,
table.dataTable thead .sorting_desc_disabled {
    cursor: pointer;
    position: relative;
    padding-right: 30px !important;
}

table.dataTable thead .sorting:before,
table.dataTable thead .sorting_asc:before,
table.dataTable thead .sorting_desc:before {
    content: "⇅";
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.5;
    font-size: 0.9rem;
}

table.dataTable thead .sorting_asc:before {
    content: "↑";
    opacity: 1;
    color: var(--seafoam-green);
}

table.dataTable thead .sorting_desc:before {
    content: "↓";
    opacity: 1;
    color: var(--seafoam-green);
}

/* DataTables buttons styling */
.dt-buttons {
    margin-bottom: 1rem;
}

.dt-buttons .btn {
    margin-right: 5px;
    margin-bottom: 5px;
}

/* Ensure table headers are not too tall */
.table thead th {
    white-space: nowrap;
    vertical-align: top !important;
}

/* Wrap column filter inputs properly */
.table thead th > input {
    margin-top: 5px !important;
}
</style>
</body>
</html>
