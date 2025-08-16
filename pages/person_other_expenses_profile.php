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
    header('Location: ../index.php');
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
                <button class="btn btn-info me-2" onclick="checkAllRemainingAmounts()" title="پشکنینی هەموو پارەی ماوەکان">
                    <i class="fas fa-search me-2"></i>پشکنینی پارەی ماوە
                </button>
                <button class="btn btn-warning me-2" onclick="fixAllRemainingAmounts()" title="چاککردنەوەی هەموو پارەی ماوەکان">
                    <i class="fas fa-calculator me-2"></i>چاککردنەوەی پارەی ماوەکان
                </button>
                <button class="btn btn-danger me-2" onclick="fixAllPersonsRemainingAmounts()" title="چاککردنەوەی هەموو پارەی ماوەکان لە هەموو کەسەکان">
                    <i class="fas fa-globe me-2"></i>چاککردنەوەی گشتی
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
            <div class="card text-center shadow card-gradient-success card-animate-hover">
              <div class="card-body">
                <i class="fas fa-dollar-sign card-icon"></i>
                <h6 class="card-title">کۆی گشتی خەرجی بە دۆلار</h6>
                <div class="fs-4 fw-bold" id="summary_total_usd">0</div>
                <small class="text-light">کۆی خەرجی بە دۆلار</small>
              </div>
            </div>
          </div>
          <div class="col mb-2">
            <div class="card text-center shadow card-gradient-warning card-animate-hover">
              <div class="card-body">
                <i class="fas fa-coins card-icon"></i>
                <h6 class="card-title">کۆی گشتی خەرجی بە دینار</h6>
                <div class="fs-4 fw-bold" id="summary_total_iqd">0</div>
                <small class="text-light">کۆی خەرجی بە دینار</small>
              </div>
            </div>
          </div>
          <div class="col mb-2">
            <div class="card text-center shadow card-gradient-danger card-animate-hover">
              <div class="card-body">
                <i class="fas fa-credit-card card-icon"></i>
                <h6 class="card-title">کۆی قەرزی ئێمە بە دۆلار</h6>
                <div class="fs-4 fw-bold" id="summary_our_debt_usd">0</div>
                <small class="text-light">کۆی قەرزی ئێمە بە دۆلار</small>
              </div>
            </div>
          </div>
          <div class="col mb-2">
            <div class="card text-center shadow card-gradient-primary card-animate-hover">
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
        </ul>
        <div class="tab-content" id="profileTabsContent">
            <div class="tab-pane fade show active" id="expenses" role="tabpanel" aria-labelledby="expenses-tab">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="expensesTable">
                        <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                            <tr>
                                <th>#</th>
                                <th>مەبەست</th>
                                <th>کارمەند</th>
                                <th>سەیارە</th>
                                <th>جۆری مامەڵە</th>
                                <th>جۆری پارە</th>
                                <th>ژمارەی وەسڵ</th>
                                <th>بڕی دینار</th>
                                <th>بڕی دۆلار</th>
                                <th>پارەی دراو دینار</th>
                                <th>پارەی دراو دۆلار</th>
                                <th>نرخی 100 دۆلار</th>
                                <th>ماوە دینار</th>
                                <th>ماوە دۆلار</th>
                                <th>بەروار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Expenses will be loaded here by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="purchasesTable">
                        <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                            <tr>
                                <th>#</th>
                                <th>ژمارەی پسووڵە</th>
                                <th>بەروار</th>
                                <th>کۆی کاڵاکان</th>
                                <th>کۆی نرخ بە دۆلار</th>
                                <th>کۆی نرخ بە دینار</th>
                                <th>جۆری دراو</th>
                                <th>جۆری مامەڵە</th>
                                <th>پارەی دراو بە دۆلار</th>
                                <th>پارەی دراو بە دینار</th>
                                <th>پارەی ماوە بە دۆلار</th>
                                <th>پارەی ماوە بە دینار</th>
                                <th>تێبینی</th>
                                <th>کردارەکان</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Purchase materials will be loaded here by JS -->
                        </tbody>
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
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="debtTable">
                        <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                            <tr>
                                <th>#</th>
                                <th>بەروار</th>
                                <th>بڕی دۆلار</th>
                                <th>بڕی دینار</th>
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
                                <label for="debt_amount_usd" class="form-label">بڕی دۆلار</label>
                                <input type="number" class="form-control" id="debt_amount_usd" name="amount_usd" min="0"
                                    step="0.01" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="debt_amount_iqd" class="form-label">بڕی دینار</label>
                                <input type="number" class="form-control" id="debt_amount_iqd" name="amount_iqd" min="0"
                                    step="0.01" value="0">
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
                                <label for="edit_debt_amount_usd" class="form-label">بڕی دۆلار</label>
                                <input type="number" class="form-control" id="edit_debt_amount_usd" name="amount_usd"
                                    min="0" step="0.01" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="edit_debt_amount_iqd" class="form-label">بڕی دینار</label>
                                <input type="number" class="form-control" id="edit_debt_amount_iqd" name="amount_iqd"
                                    min="0" step="0.01" value="0">
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
    <script>
        const PERSON_ID = <?php echo $person_id; ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/swalAlert.js"></script>
    <script src="../assets/js/comon/table-controler.js"></script>
    <script src="../assets/js/person_other_expenses_profile/select_other_expenses.js"></script>
    <script src="../assets/js/person_other_expenses_profile/select_purchases.js"></script>
    <script src="../assets/js/person_other_expenses_profile/select_debt.js"></script>
    <script src="../assets/js/person_other_expenses_profile/add_debt.js"></script>
    <script src="../assets/js/person_other_expenses_profile/update_debt.js"></script>
    <script src="../assets/js/person_other_expenses_profile/delete_debt.js"></script>
    <script src="../assets/js/person_other_expenses_profile/summary_cards.js"></script>
    
    <script>
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
        
        // Check all remaining amounts function
        function checkAllRemainingAmounts() {
            $.ajax({
                url: '../process/person_other_expenses_profile/check_all_remaining_amounts.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAllRemainingAmountsModal(response.data);
                    } else {
                        console.error('Error checking all remaining amounts:', response.error);
                        alert('هەڵە لە پشکنینی پارەی ماوەکان: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('هەڵە لە پەیوەندی بە سێرڤەر');
                }
            });
        }
        
        // Show all remaining amounts modal
        function showAllRemainingAmountsModal(data) {
            const modalContent = `
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-search me-2"></i>پشکنینی هەموو پارەی ماوەکان
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
                                        <tr>
                                            <td>کەسانی کێشەدار</td>
                                            <td>${data.overall_summary.total_persons_with_issues}</td>
                                        </tr>
                                        <tr>
                                            <td>پسووڵەکان</td>
                                            <td>${data.overall_summary.total_receipts_with_issues}</td>
                                        </tr>
                                        <tr>
                                            <td>ئایتمەکان</td>
                                            <td>${data.overall_summary.total_items_with_issues}</td>
                                        </tr>
                                        <tr class="table-warning">
                                            <td>جیاوازی بە دۆلار</td>
                                            <td>$${Number(data.overall_summary.total_usd_difference).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                        </tr>
                                        <tr class="table-warning">
                                            <td>جیاوازی بە دینار</td>
                                            <td>${Number(data.overall_summary.total_iqd_difference).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-info">وردەکاری کەسان:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ناوی کەس</th>
                                            <th>پسووڵەکان</th>
                                            <th>ئایتمەکان</th>
                                            <th>جیاوازی بە دۆلار</th>
                                            <th>جیاوازی بە دینار</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.persons.map(person => `
                                            <tr class="table-danger">
                                                <td>${person.person_name || 'نەناسراو'}</td>
                                                <td>${person.receipts_count}</td>
                                                <td>${person.items_count}</td>
                                                <td>$${Number(person.total_usd_difference).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                                <td>${Number(person.total_iqd_difference).toLocaleString('en-US', {minimumFractionDigits: 2})} د.ع</td>
                                            </tr>
                                        `).join('')}
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
        
        // Fix all remaining amounts function
        function fixAllRemainingAmounts() {
            if (!confirm('دڵنیای لە چاککردنەوەی هەموو پارەی ماوەکان؟ ئەم کردارە ناتوانرێت هەڵوەشێنرێتەوە.')) {
                return;
            }
            
            $.ajax({
                url: '../process/person_other_expenses_profile/fix_all_remaining_amounts.php',
                type: 'GET',
                data: { 
                    person_id: PERSON_ID
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('هەموو پارەی ماوەکان بە سەرکەوتووی چاککرانەوە!');
                        // Refresh the page to show updated data
                        location.reload();
                    } else {
                        console.error('Error fixing all remaining amounts:', response.error);
                        alert('هەڵە لە چاککردنەوەی پارەی ماوەکان: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('هەڵە لە پەیوەندی بە سێرڤەر');
                }
            });
        }
        
        // Fix all persons remaining amounts function
        function fixAllPersonsRemainingAmounts() {
            if (!confirm('دڵنیای لە چاککردنەوەی هەموو پارەی ماوەکان لە هەموو کەسەکان؟ ئەم کردارە ناتوانرێت هەڵوەشێنرێتەوە.')) {
                return;
            }
            
            $.ajax({
                url: '../process/person_other_expenses_profile/fix_all_persons_remaining_amounts.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('هەموو پارەی ماوەکان لە هەموو کەسەکان بە سەرکەوتووی چاککرانەوە!');
                        // Refresh the page to show updated data
                        location.reload();
                    } else {
                        console.error('Error fixing all persons remaining amounts:', response.error);
                        alert('هەڵە لە چاککردنەوەی پارەی ماوەکان: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('هەڵە لە پەیوەندی بە سێرڤەر');
                }
            });
        }
    </script>
</body>

</html>