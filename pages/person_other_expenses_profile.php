<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!hasPermission('view_person_other_expenses_profile')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}
$person_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$person_name = '';
if ($person_id) {
    $stmt = $pdo->prepare('SELECT name FROM other_expense_persons WHERE id = ?');
    $stmt->execute([$person_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $person_name = $row ? $row['name'] : '';
}

// Fetch default exchange rate
$stmt = $pdo->query("SELECT value FROM settings WHERE name = 'usd_iqd_rate'");
$rate = $stmt->fetchColumn();
$default_rate = $rate ? $rate : 150000;
?>
<!DOCTYPE html>
<html lang="ku">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرۆفایلی کەس</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link href="../assets/css/person_other_expenses_profile.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css" rel="stylesheet">
    <!-- DataTables Buttons CSS -->
    <link href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>

<body dir="rtl">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <div class="container-fluid py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">
                <?php echo htmlspecialchars($person_name); ?>
            </h2>
            <div>
                <button class="btn btn-info me-2" onclick="checkSummaryCardRemaining()" title="پشکنینی کارتی کۆی گشتی">
                    <i class="fas fa-chart-pie me-2"></i>پشکنینی کارتی کۆی گشتی
                </button>
            </div>
        </div>
        <div class="mb-3">
            <a href="person_other_expenses.php" class="btn"
                style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">
                <i class="fa fa-arrow-right"></i> گەڕانەوە بۆ لیستی کەسان
            </a>
        </div>
        <div class="row mb-3" id="person-summary-cards">
          <div class="col mb-2">
            <div class="card text-center shadow card-gradient-danger card-animate-hover" style="cursor: pointer;" onclick="showDebtBreakdown('usd')" title="کلیک بکە بۆ بینینی وردەکاری قەرزەکە">
              <div class="card-body">
                <i class="fas fa-credit-card card-icon"></i>
                <h6 class="card-title">کۆی قەرزی ئێمە بە دۆلار</h6>
                <div class="fs-4 fw-bold" id="summary_our_debt_usd">0</div>
                <small class="text-light">کۆی قەرزی ئێمە بە دۆلار</small>
              </div>
            </div>
          </div>
          <div class="col mb-2">
            <div class="card text-center shadow card-gradient-primary card-animate-hover" style="cursor: pointer;" onclick="showDebtBreakdown('iqd')" title="کلیک بکە بۆ بینینی وردەکاری قەرزەکە">
              <div class="card-body">
                <i class="fas fa-credit-card card-icon"></i>
                <h6 class="card-title">کۆی قەرزی ئێمە بە دینار</h6>
                <div class="fs-4 fw-bold" id="summary_our_debt_iqd">0</div>
                <small class="text-light">کۆی قەرزی ئێمە بە دینار</small>
              </div>
            </div>
          </div>
          <div class="col mb-2">
            <div class="card text-center shadow card-gradient-info card-animate-hover">
              <div class="card-body">
                <i class="fas fa-list-alt card-icon"></i>
                <h6 class="card-title">ژمارەی خەرجیەکان</h6>
                <div class="fs-4 fw-bold" id="summary_count">0</div>
                <small class="text-light">ژمارەی هەموو خەرجیەکان</small>
              </div>
            </div>
          </div>
        </div>
        <div class="row mb-4" id="person-cards">
            <!-- Cards will be loaded here by JS -->
        </div>
        <ul class="nav nav-tabs mb-3" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses"
                    type="button" role="tab" aria-controls="expenses" aria-selected="true">مێژووی خەرجیەکان</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button"
                    role="tab" aria-controls="purchases" aria-selected="false">مێژووی کڕینی کاڵاکان</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="debt-tab" data-bs-toggle="tab" data-bs-target="#debt" type="button"
                    role="tab" aria-controls="debt" aria-selected="false">مێژووی دانەوە</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="adjustment-tab" data-bs-toggle="tab" data-bs-target="#adjustment" type="button"
                    role="tab" aria-controls="adjustment" aria-selected="false">ڕێکخستنەوە</button>
            </li>
        </ul>
        <div class="tab-content" id="profileTabsContent">
            <div class="tab-pane fade show active" id="expenses" role="tabpanel" aria-labelledby="expenses-tab">
                <div class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="expensesDateFrom" class="form-label">لە بەروار:</label>
                            <input type="date" id="expensesDateFrom" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="expensesDateTo" class="form-label">بۆ بەروار:</label>
                            <input type="date" id="expensesDateTo" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" id="clearExpensesFilter" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>سڕینەوەی فلتەر
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="expensesTable">
                        <!-- DataTables will build the table structure -->
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                <div class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="purchasesDateFrom" class="form-label">لە بەروار:</label>
                            <input type="date" id="purchasesDateFrom" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="purchasesDateTo" class="form-label">بۆ بەروار:</label>
                            <input type="date" id="purchasesDateTo" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" id="clearPurchasesFilter" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>سڕینەوەی فلتەر
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="purchasesTable">
                        <!-- DataTables will build the table structure -->
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="debt" role="tabpanel" aria-labelledby="debt-tab">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">مێژووی دانەوە</h5>
                    <button class="btn"
                        style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;"
                        data-bs-toggle="modal" data-bs-target="#addDebtModal"><i class="fa fa-plus"></i> دانەوە</button>
                </div>
                <div class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="debtDateFrom" class="form-label">لە بەروار:</label>
                            <input type="date" id="debtDateFrom" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="debtDateTo" class="form-label">بۆ بەروار:</label>
                            <input type="date" id="debtDateTo" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" id="clearDebtFilter" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>سڕینەوەی فلتەر
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="debtTable">
                        <!-- DataTables will build the table structure -->
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="adjustment" role="tabpanel" aria-labelledby="adjustment-tab">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">ڕێکخستنەوەی قەرز</h5>
                    <button class="btn"
                        style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;"
                        data-bs-toggle="modal" data-bs-target="#addAdjustmentModal"><i class="fa fa-plus"></i> ڕێکخستنەوە</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="adjustmentTable">
                        <!-- DataTables will build the table structure -->
                    </table>
                </div>
            </div>
        </div>
        <!-- Add Adjustment Modal -->
        <div class="modal fade" id="addAdjustmentModal" tabindex="-1" aria-labelledby="addAdjustmentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="addAdjustmentForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addAdjustmentModalLabel">ڕێکخستنەوەی نوێ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="adj_date" class="form-label">بەروار</label>
                                <input type="date" class="form-control" id="adj_date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="adj_amount_usd" class="form-label">بڕی دۆلار (بۆ کەمکردنەوە (-) دابنێ)</label>
                                <input type="number" class="form-control" id="adj_amount_usd" name="amount_usd" step="0.01" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="adj_amount_iqd" class="form-label">بڕی دینار (بۆ کەمکردنەوە (-) دابنێ)</label>
                                <input type="number" class="form-control" id="adj_amount_iqd" name="amount_iqd" step="0.01" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="adj_note" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="adj_note" name="note" rows="2" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                            <button type="submit" class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">تۆمارکردن</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Add Debt Modal -->
        <div class="modal fade" id="addDebtModal" tabindex="-1" aria-labelledby="addDebtModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="addDebtForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addDebtModalLabel">دانەوە</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="debt_date" class="form-label">بەروار</label>
                                <input type="date" class="form-control" id="debt_date" name="date" required
                                    value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="exchange_rate" class="form-label">نرخی ١٠٠ دۆلار (د.ع)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="exchange_rate" name="exchange_rate" min="1" step="0.01" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="fetchAndSetDollarRate('exchange_rate')">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="debt_amount_usd" class="form-label">بڕی دۆلار</label>
                                    <input type="number" class="form-control" id="debt_amount_usd" name="amount_usd" min="0"
                                        step="0.01" value="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="debt_discount_usd" class="form-label">داشکاندن بە دۆلار</label>
                                    <input type="number" class="form-control" id="debt_discount_usd" name="discount_usd" min="0"
                                        step="0.01" value="0">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="debt_amount_iqd" class="form-label">بڕی دینار</label>
                                    <input type="number" class="form-control" id="debt_amount_iqd" name="amount_iqd" min="0"
                                        step="0.01" value="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="debt_discount_iqd" class="form-label">داشکاندن بە دینار</label>
                                    <input type="number" class="form-control" id="debt_discount_iqd" name="discount_iqd" min="0"
                                        step="0.01" value="0">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="debt_change_back_usd" class="form-label">باقی (دۆلار)</label>
                                    <input type="number" class="form-control" id="debt_change_back_usd" name="change_back_usd" min="0"
                                        step="0.01" value="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="debt_change_back_iqd" class="form-label">باقی (دینار)</label>
                                    <input type="number" class="form-control" id="debt_change_back_iqd" name="change_back_iqd" min="0"
                                        step="1" value="0">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="debt_remaining_usd" class="form-label">کۆی قەرزی ماوە بە دۆلار</label>
                                    <input type="text" class="form-control bg-light fw-bold" id="debt_remaining_usd" value="0" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="debt_remaining_iqd" class="form-label">کۆی قەرزی ماوە بە دینار</label>
                                    <input type="text" class="form-control bg-light fw-bold" id="debt_remaining_iqd" value="0" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="debt_note" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="debt_note" name="note" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                            <button type="submit" class="btn"
                                style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">دانەوە</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Edit Debt Modal -->
        <div class="modal fade" id="editDebtModal" tabindex="-1" aria-labelledby="editDebtModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="editDebtForm">
                        <input type="hidden" id="edit_debt_id" name="id">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editDebtModalLabel">دەستکاری دانەوە</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_debt_date" class="form-label">بەروار</label>
                                <input type="date" class="form-control" id="edit_debt_date" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_exchange_rate" class="form-label">نرخی ١٠٠ دۆلار (د.ع)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="edit_exchange_rate" name="exchange_rate" min="1" step="0.01" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="fetchAndSetDollarRate('edit_exchange_rate')">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_debt_amount_usd" class="form-label">بڕی دۆلار</label>
                                    <input type="number" class="form-control" id="edit_debt_amount_usd" name="amount_usd"
                                        min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_debt_discount_usd" class="form-label">داشکاندن بە دۆلار</label>
                                    <input type="number" class="form-control" id="edit_debt_discount_usd" name="discount_usd"
                                        min="0" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_debt_amount_iqd" class="form-label">بڕی دینار</label>
                                    <input type="number" class="form-control" id="edit_debt_amount_iqd" name="amount_iqd"
                                        min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_debt_discount_iqd" class="form-label">داشکاندن بە دینار</label>
                                    <input type="number" class="form-control" id="edit_debt_discount_iqd" name="discount_iqd"
                                        min="0" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_debt_change_back_usd" class="form-label">باقی (دۆلار)</label>
                                    <input type="number" class="form-control" id="edit_debt_change_back_usd" name="change_back_usd" min="0"
                                        step="0.01" value="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_debt_change_back_iqd" class="form-label">باقی (دینار)</label>
                                    <input type="number" class="form-control" id="edit_debt_change_back_iqd" name="change_back_iqd" min="0"
                                        step="1" value="0">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_debt_remaining_usd" class="form-label">کۆی قەرزی ماوە بە دۆلار</label>
                                    <input type="text" class="form-control bg-light fw-bold" id="edit_debt_remaining_usd" value="0" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_debt_remaining_iqd" class="form-label">کۆی قەرزی ماوە بە دینار</label>
                                    <input type="text" class="form-control bg-light fw-bold" id="edit_debt_remaining_iqd" value="0" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="edit_debt_note" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="edit_debt_note" name="note" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                            <button type="submit" class="btn"
                                style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">پاشەکەوتکردن</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script nonce="<?php echo $csp_nonce; ?>">
        const PERSON_ID = <?php echo $person_id; ?>;
        const DEFAULT_USD_RATE = <?php echo floatval($default_rate); ?>;
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
    <script src="../assets/js/person_other_expenses_profile/select_other_expenses.js?v=1.1" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/person_other_expenses_profile/select_purchases.js?v=1.1" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/person_other_expenses_profile/select_debt.js?v=1.1" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/person_other_expenses_profile/add_debt.js?v=1.1" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/person_other_expenses_profile/update_debt.js?v=1.1" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/person_other_expenses_profile/delete_debt.js?v=1.1" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/person_other_expenses_profile/summary_cards.js?v=1.1" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/person_other_expenses_profile/select_adjustments.js?v=1.1" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/person_other_expenses_profile/add_adjustment.js?v=1.1" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/person_other_expenses_profile/delete_adjustment.js?v=1.1" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/person_other_expenses_profile/adjustments.js?v=1.1" nonce="<?php echo $csp_nonce; ?>"></script>
    
    <script nonce="<?php echo $csp_nonce; ?>">
        // Check summary card remaining amounts function
        function checkSummaryCardRemaining() {
            $.ajax({
                url: '../process/person_other_expenses_profile/check_summary_card_remaining.php',
                type: 'GET',
                data: { 
                    person_id: PERSON_ID
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showSummaryCardRemainingModal(response.data);
                    } else {
                        console.error('Error checking summary card remaining:', response.error);
                        alert('هەڵە لە پشکنینی کارتی کۆی گشتی: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('هەڵە لە پەیوەندی بە سێرڤەر');
                }
            });
        }
        
        // Show summary card remaining modal
        function showSummaryCardRemainingModal(data) {
            const modalContent = `
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-chart-pie me-2"></i>پشکنینی کارتی کۆی گشتی
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-primary">قەرزی سەرەتایی:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>جۆر</th>
                                            <th>بە دۆلار</th>
                                            <th>بە دینار</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>قەرزی سەرەتایی</td>
                                            <td>$${Number(data.opening_debt_usd).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                            <td>${Number(data.opening_debt_iqd).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-success">خەرجیەکان:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>جۆر</th>
                                            <th>بە دۆلار</th>
                                            <th>بە دینار</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>کۆی خەرجیەکان</td>
                                            <td>$${Number(data.expenses.total_expense_usd).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                            <td>${Number(data.expenses.total_expense_iqd).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                                        </tr>
                                        <tr>
                                            <td>پارەی ماوەی خەرجیەکان</td>
                                            <td>$${Number(data.remaining_expenses.total_remaining_usd).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                            <td>${Number(data.remaining_expenses.total_remaining_iqd).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-warning">کڕینەکان:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>جۆر</th>
                                            <th>بە دۆلار</th>
                                            <th>بە دینار</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="table-danger">
                                            <td>پارەی ماوەی کڕینەکان (کۆن)</td>
                                            <td>$${Number(data.remaining_purchase_old.total_remaining_usd).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                            <td>${Number(data.remaining_purchase_old.total_remaining_iqd).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                                        </tr>
                                        <tr class="table-success">
                                            <td>پارەی ماوەی کڕینەکان (نوێ)</td>
                                            <td>$${Number(data.remaining_purchase_new.total_remaining_usd).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                            <td>${Number(data.remaining_purchase_new.total_remaining_iqd).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-info">کۆی قەرزی ئێمە:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>جۆر</th>
                                            <th>بە دۆلار</th>
                                            <th>بە دینار</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="table-danger">
                                            <td>کۆی قەرزی ئێمە (کۆن)</td>
                                            <td>$${Number(data.our_debt_old.usd).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                            <td>${Number(data.our_debt_old.iqd).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                                        </tr>
                                        <tr class="table-success">
                                            <td>کۆی قەرزی ئێمە (نوێ)</td>
                                            <td>$${Number(data.our_debt_new.usd).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                            <td>${Number(data.our_debt_new.iqd).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                                        </tr>
                                        <tr class="${data.differences.has_issues ? 'table-warning' : 'table-success'}">
                                            <td>جیاوازی</td>
                                            <td>$${Number(data.differences.usd).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                            <td>${Number(data.differences.iqd).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" onclick="fixAllRemainingAmounts()">
                        <i class="fas fa-calculator me-2"></i>چاککردنەوەی پارەی ماوە
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                </div>
            `;
            
            // Create and show modal
            const modal = `
                <div class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            ${modalContent}
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            $('.modal').modal('show');
            
            // Remove modal from DOM when hidden
            $('.modal').on('hidden.bs.modal', function() {
                $(this).remove();
            });
        }
        
        // Show debt breakdown modal
        function showDebtBreakdown(currency) {
            $.ajax({
                url: '../process/person_other_expenses_profile/get_debt_breakdown.php',
                type: 'GET',
                data: {
                    person_id: PERSON_ID,
                    currency: currency
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showDebtBreakdownModal(response.data, currency);
                    } else {
                        console.error('Error loading debt breakdown:', response.error);
                        alert('هەڵە لە بارکردنی وردەکاری قەرز: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('هەڵە لە پەیوەندی بە سێرڤەر');
                }
            });
        }
        
        // Show debt breakdown modal
        function showDebtBreakdownModal(data, currency) {
            const currencyLabel = currency === 'usd' ? 'دۆلار' : 'دینار';
            const currencySymbol = currency === 'usd' ? '$' : 'د.ع';
            
            let expensesRows = '';
            if (data.expenses && data.expenses.length > 0) {
                data.expenses.forEach(function(expense) {
                    expensesRows += `
                        <tr>
                            <td>خەرجی</td>
                            <td>${expense.date}</td>
                            <td>${expense.description || '-'}</td>
                            <td>${expense.invoice_number || '-'}</td>
                            <td>${Number(expense.amount_usd).toLocaleString('en-US', {minimumFractionDigits: 2})} $</td>
                            <td>${Number(expense.amount_iqd).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                            <td class="fw-bold">${Number(expense.remaining).toLocaleString('en-US', {minimumFractionDigits: 2})} ${currencySymbol}</td>
                        </tr>
                    `;
                });
            } else {
                expensesRows = '<tr><td colspan="7" class="text-center text-muted">هیچ خەرجییەکی قەرز نییە</td></tr>';
            }
            
            let purchasesRows = '';
            if (data.purchases && data.purchases.length > 0) {
                data.purchases.forEach(function(purchase) {
                    purchasesRows += `
                        <tr>
                            <td>کڕین</td>
                            <td>${purchase.date}</td>
                            <td>${purchase.receipt_number || '-'}</td>
                            <td>${purchase.notes || '-'}</td>
                            <td>${Number(purchase.total_price_usd).toLocaleString('en-US', {minimumFractionDigits: 2})} $</td>
                            <td>${Number(purchase.total_price_iqd).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                            <td class="fw-bold">${Number(purchase.remaining).toLocaleString('en-US', {minimumFractionDigits: 2})} ${currencySymbol}</td>
                        </tr>
                    `;
                });
            } else {
                purchasesRows = '<tr><td colspan="7" class="text-center text-muted">هیچ کڕینێکی قەرز نییە</td></tr>';
            }
            
            const modalContent = `
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-list-alt me-2"></i>وردەکاری قەرز بە ${currencyLabel}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-primary">کۆی گشتی:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>جۆر</th>
                                            <th>بڕ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.totals.opening_debt > 0 ? `
                                        <tr>
                                            <td>قەرزی سەرەتایی</td>
                                            <td class="fw-bold">${Number(data.totals.opening_debt).toLocaleString('en-US', {minimumFractionDigits: 2})} ${currencySymbol}</td>
                                        </tr>
                                        ` : ''}
                                        <tr>
                                            <td>ماوەی خەرجیەکان</td>
                                            <td class="fw-bold">${Number(data.totals.expenses_remaining).toLocaleString('en-US', {minimumFractionDigits: 2})} ${currencySymbol}</td>
                                        </tr>
                                        <tr>
                                            <td>ماوەی کڕینەکان</td>
                                            <td class="fw-bold">${Number(data.totals.purchases_remaining).toLocaleString('en-US', {minimumFractionDigits: 2})} ${currencySymbol}</td>
                                        </tr>
                                        <tr class="table-primary">
                                            <td><strong>کۆی قەرزی ئێمە</strong></td>
                                            <td class="fw-bold"><strong>${Number(data.totals.total_debt).toLocaleString('en-US', {minimumFractionDigits: 2})} ${currencySymbol}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-success">خەرجیەکان:</h6>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>جۆر</th>
                                            <th>بەروار</th>
                                            <th>بەکارهێنان</th>
                                            <th>ژمارەی فاکتور</th>
                                            <th>کۆی دۆلار</th>
                                            <th>کۆی دینار</th>
                                            <th>ماوە بە ${currencyLabel}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${expensesRows}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-warning">کڕینەکان:</h6>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>جۆر</th>
                                            <th>بەروار</th>
                                            <th>ژمارەی وەسڵ</th>
                                            <th>تێبینی</th>
                                            <th>کۆی دۆلار</th>
                                            <th>کۆی دینار</th>
                                            <th>ماوە بە ${currencyLabel}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${purchasesRows}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                </div>
            `;
            
            // Create and show modal
            const modal = `
                <div class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            ${modalContent}
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            $('.modal').modal('show');
            
            // Remove modal from DOM when hidden
            $('.modal').on('hidden.bs.modal', function() {
                $(this).remove();
            });
        }

    </script>
</body>

</html>