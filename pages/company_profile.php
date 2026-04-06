<?php
// c:\xampp\htdocs\dana-concrete\pages\company_profile.php
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
    redirectToLogin();
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css" rel="stylesheet">
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
                    <small class="text-light">کۆی قەرزی گشتی</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3 mb-2">
            <div class="card text-center shadow  card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-shopping-cart card-icon"></i>
                    <h6 class="card-title">کڕینە قەرزەکان</h6>
                    <div class="fs-4 fw-bold" id="credit-count">...</div>
                    <small class="text-light">ژمارەی کڕینەکان</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3 mb-2">
            <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-credit-card card-icon"></i>
                    <h6 class="card-title">قەرزی سەرەتایی</h6>
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
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button" role="tab" aria-controls="purchases" aria-selected="true">مێژووی کڕینەکان</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="debt-tab" data-bs-toggle="tab" data-bs-target="#debt" type="button" role="tab" aria-controls="debt" aria-selected="false">مێژووی دانەوەی قەرزەکان</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="adjustment-tab" data-bs-toggle="tab" data-bs-target="#adjustment" type="button" role="tab" aria-controls="adjustment" aria-selected="false">ڕێکخستنەوەی حیساب</button>
        </li>
    </ul>
    
    <div class="tab-content" id="profileTabsContent">
        <!-- Purchases Tab -->
        <div class="tab-pane fade show active" id="purchases" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center" id="purchasesTable"></table>
            </div>
        </div>
        <!-- Debt Tab -->
        <div class="tab-pane fade" id="debt" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">مێژووی دانەوەی قەرزەکان</h5>
                <button class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;" data-bs-toggle="modal" data-bs-target="#addDebtModal"><i class="fa fa-plus"></i> دانەوەی قەرز</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center" id="debtTable"></table>
            </div>
        </div>
        <!-- Adjustment Tab -->
        <div class="tab-pane fade" id="adjustment" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">مێژووی ڕێکخستنەوەی حیساب</h5>
                <button class="btn btn-warning fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#addAdjustmentModal"><i class="fa fa-sync"></i> ڕێکخستنەوەی نوێ</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center" id="adjustmentTable"></table>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Add Debt Modal -->
    <div class="modal fade" id="addDebtModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <form id="addDebtForm">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">دانەوەی قەرز</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">بەروار</label>
                            <input type="date" class="form-control" name="date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3"><label class="form-label">دۆلار</label><input type="number" step="0.01" class="form-control" name="amount_usd" value="0"></div>
                            <div class="col-6 mb-3"><label class="form-label">دینار</label><input type="number" step="1" class="form-control" name="amount_iqd" value="0"></div>
                        </div>
                        <div class="mb-3"><label class="form-label">تێبینی</label><textarea class="form-control" name="note" rows="2"></textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-success">تۆمارکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Adjustment Modal -->
    <div class="modal fade" id="addAdjustmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <form id="addAdjustmentForm">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title fw-bold">ڕێکخستنەوەی حیساب</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted small">بۆ زیادکردنی قەرز بڕی پۆزەتیڤ و بۆ کەمکردنەوە بڕی نێگەتیڤ (-) بنووسە.</p>
                        <div class="row">
                            <div class="col-6 mb-3"><label class="form-label">دۆلار</label><input type="number" step="0.01" class="form-control" name="amount_usd" value="0"></div>
                            <div class="col-6 mb-3"><label class="form-label">دینار</label><input type="number" step="1" class="form-control" name="amount_iqd" value="0"></div>
                        </div>
                        <div class="mb-3"><label class="form-label">بەروار</label><input type="date" class="form-control" name="date" required value="<?php echo date('Y-m-d'); ?>"></div>
                        <div class="mb-3"><label class="form-label">هۆکاری ڕێکخستنەوە</label><textarea class="form-control" name="note" rows="3" required></textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-warning fw-bold">تۆمارکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
    const COMPANY_ID = <?php echo $company_id; ?>;
    let currentFilters = { from_date: '', to_date: '' };

    function loadCompanyInfoCards() {
        $.get('../process/company_profile/select_debt.php', { company_id: COMPANY_ID, stats: 1, ...currentFilters }, function(data) {
            if (!data || !data.stats) return;
            const s = data.stats;
            $('#total-debt').text(s.total_debt_usd.toLocaleString() + ' $ / ' + s.total_debt_iqd.toLocaleString() + ' د.ع');
            $('#credit-count').text(s.credit_count);
            $('#opening-debt').text(s.opening_debt_usd.toLocaleString() + ' $ / ' + s.opening_debt_iqd.toLocaleString() + ' د.ع');
            $('#total-price').text(s.total_price_usd.toLocaleString() + ' $ / ' + s.total_price_iqd.toLocaleString() + ' د.ع');
        }, 'json');
    }

    function applyFilters() {
        currentFilters.from_date = $('#from_date').val();
        currentFilters.to_date = $('#to_date').val();
        loadCompanyInfoCards();
        loadPurchases(); loadDebts(); loadAdjustments();
    }

    function resetFilters() {
        $('#from_date, #to_date').val('');
        currentFilters = { from_date: '', to_date: '' };
        applyFilters();
    }

    $(function() {
        loadCompanyInfoCards();
        loadAdjustments();
        
        $('#addAdjustmentForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '../process/company_profile/add_adjustment.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        $('#addAdjustmentModal').modal('hide');
                        Swal.fire('سەرکەوتوو', 'ڕێکخستنەوەکە ئەنجامدرا', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('هەڵە', res.msg, 'error');
                    }
                }
            });
        });
    });

    function loadAdjustments() {
        $.get('../process/company_profile/select_adjustments.php', { company_id: COMPANY_ID, ...currentFilters }, function(data) {
            let html = '<thead><tr><th>بەروار</th><th>بڕ (USD)</th><th>بڕ (IQD)</th><th>تێبینی</th><th>کردار</th></tr></thead><tbody>';
            data.forEach(adj => {
                html += `<tr>
                    <td>${adj.date}</td>
                    <td class="${adj.amount_usd < 0 ? 'text-danger' : 'text-success'} fw-bold">${adj.amount_usd} $</td>
                    <td class="${adj.amount_iqd < 0 ? 'text-danger' : 'text-success'} fw-bold">${adj.amount_iqd} دینار</td>
                    <td>${adj.note}</td>
                    <td><button class="btn btn-sm btn-danger" onclick="deleteAdjustment(${adj.id})"><i class="fa fa-trash"></i></button></td>
                </tr>`;
            });
            html += '</tbody>';
            $('#adjustmentTable').html(html);
        }, 'json');
    }

    function deleteAdjustment(id) {
        Swal.fire({ title: 'دڵنیای؟', text: "ئەم دەستکارییە دەسڕێتەوە", icon: 'warning', showCancelButton: true, confirmButtonText: 'سڕینەوە' }).then(r => {
            if(r.isConfirmed) {
                $.post('../process/company_profile/delete_adjustment.php', { id }, res => {
                    if(res.success) location.reload();
                }, 'json');
            }
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/company_profile/company_profile.js"></script>
<script src="../assets/js/company_profile/select_purchases.js"></script>
<script src="../assets/js/company_profile/add_debt.js"></script>
<script src="../assets/js/company_profile/select_debt.js"></script>

<style>
    body { font-family: 'Rabar', sans-serif; background: #f4f7f6; }
    .nav-tabs .nav-link.active { background: var(--seafoam-green) !important; color: white !important; }
    .card-gradient-danger { background: linear-gradient(45deg, #ff6b6b, #ee5253); color: white; }
    .card-gradient-info { background: linear-gradient(45deg, #48dbfb, #0abde3); color: white; }
    .card-gradient-warning { background: linear-gradient(45deg, #feca57, #ff9f43); color: white; }
    .card-gradient-success { background: linear-gradient(45deg, #1dd1a1, #10ac84); color: white; }
</style>
</body>
</html>
