<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!hasPermission('view_accounts')) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- jQuery (پێش هەموو شت) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">
            <?php echo htmlspecialchars($company_name); ?>
        </h2>
        <a href="company_receipts.php?id=<?php echo $company_id; ?>" class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;" target="_blank">
            <i class="fa fa-print"></i> پرینت
        </a>
    </div>
    <div class="mb-3">
        <a href="add_company.php" class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">
            <i class="fa fa-arrow-right"></i> گەڕانەوە بۆ لیستی کۆمپانیاکان
        </a>
    </div>

    <div class="row mb-3" id="company-info-cards">
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">کۆی قەرز</h5>
                    <span class="fs-4" id="total-debt">...</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">ژمارەی کڕینە قەرزەکان</h5>
                    <span class="fs-4" id="credit-count">...</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">کۆی قەرزی سەرەتایی</h5>
                    <span class="fs-4" id="opening-debt">...</span>
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
                    <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                        <tr>
                            <th>#</th>
                            <th>کۆمپانیا</th>
                            <th>شوێن</th>
                            <th>شۆفێر</th>
                            <th>ژمارەی پسوڵە</th>
                            <th>مەواد</th>
                            <th>بەروار</th>
                            <th>جۆری پارەدان</th>
                            <th>جۆری دراو</th>
                            <th>کیلۆگرام</th>
                            <th>نرخی یەک کیلۆ بە دۆلار</th>
                            <th>نرخی یەک کیلۆ بە دینار</th>
                            <th>نرخ</th>
                            <th>بڕی پارە بە دینار</th>
                            <th>نرخی 100 دۆلار بە دینار</th>
                            <th>پارەی دراو بە دۆلار</th>
                            <th>پارەی دراو بە دینار</th>
                            <th>پارەی ماوە بە دۆلار</th>
                            <th>پارەی ماوە بە دینار</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Purchases will be loaded here by JS -->
                    </tbody>
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
                    <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                        <tr>
                            <th>#</th>
                            <th>بەروار</th>
                            <th>بڕی دۆلار</th>
                            <th>بڕی دینار</th>
                            <th>نرخی دۆلار</th>
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
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <form id="addDebtForm">
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
                            <label for="debt_dollar_rate" class="form-label">نرخی دۆلار</label>
                            <input type="number" class="form-control" id="debt_dollar_rate" name="dollar_rate" min="0" step="0.01" value="150000">
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
                            <label for="edit_debt_dollar_rate" class="form-label">نرخی دۆلار</label>
                            <input type="number" class="form-control" id="edit_debt_dollar_rate" name="dollar_rate" min="0" step="0.01" value="150000">
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
<script>
    const COMPANY_ID = <?php echo $company_id; ?>;
    function loadCompanyInfoCards() {
        $.get('../process/company_profile/select_debt.php', { company_id: COMPANY_ID, stats: 1 }, function(data) {
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
        }, 'json');
    }
    $(function() { loadCompanyInfoCards(); });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/company_profile/company_profile.js"></script>
<script src="../assets/js/company_profile/select_purchases.js"></script>
<script src="../assets/js/company_profile/add_debt.js"></script>
<script src="../assets/js/company_profile/select_debt.js"></script>
<script src="../assets/js/company_profile/update_debt.js"></script>
<script src="../assets/js/company_profile/delete_debt.js"></script>
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
</body>
</html>
