<?php
// c:\xampp\htdocs\dana-concrete\pages\company_profile.php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

if (!hasPermission('view_company')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="fas fa-lock" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

$company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$company_name = '';
if ($company_id) {
    $stmt = $pdo->prepare('SELECT name FROM company WHERE id = ?');
    $stmt->execute([$company_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $company_name = $row ? $row['name'] : 'کۆمپانیا نەدۆزرایەوە';
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرۆفایلی کۆمپانیا - <?= htmlspecialchars($company_name) ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.datatables.net/2.0.0/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/3.0.0/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    
    <style>
        body { background: #f8f9fa; font-family: 'Rabar', sans-serif; }
        .nav-tabs .nav-link { color: #666; font-weight: bold; border-radius: 10px 10px 0 0; }
        .nav-tabs .nav-link.active { background: #20b2aa !important; color: white !important; border: none; }
        .tab-content { background: white; padding: 30px; border-radius: 0 0 15px 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .card-stat { border: none; border-radius: 20px; color: white; padding: 20px; transition: transform 0.3s; }
        .card-stat:hover { transform: translateY(-5px); }
        .bg-gradient-debt { background: linear-gradient(45deg, #ff6b6b, #ee5253); }
        .bg-gradient-pur { background: linear-gradient(45deg, #48dbfb, #0abde3); }
        .bg-gradient-init { background: linear-gradient(45deg, #feca57, #ff9f43); }
        .bg-gradient-total { background: linear-gradient(45deg, #1dd1a1, #10ac84); }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark"><i class="fas fa-building text-teal me-2"></i> <?= htmlspecialchars($company_name) ?></h2>
            <div>
                <a href="add_company.php" class="btn btn-outline-secondary rounded-pill me-2"><i class="fas fa-arrow-right me-1"></i> گەڕانەوە</a>
                <a href="company_receipts.php?id=<?= $company_id ?>" target="_blank" class="btn btn-teal rounded-pill text-white fw-bold px-4"><i class="fas fa-print me-1"></i> ڕاپۆرتی گشتی</a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3"><div class="card-stat bg-gradient-debt"><h6>کۆی قەرز</h6><h3 id="total-debt" class="fw-bold fs-4">...</h3><small>قەرزی هەنووکەیی</small></div></div>
            <div class="col-md-3"><div class="card-stat bg-gradient-pur"><h6>ژمارەی کڕینەکان</h6><h3 id="credit-count" class="fw-bold fs-4">...</h3><small>کۆی پسوڵە قەرزەکان</small></div></div>
            <div class="col-md-3"><div class="card-stat bg-gradient-init"><h6>قەرزی سەرەتایی</h6><h3 id="opening-debt" class="fw-bold fs-4">...</h3><small>Initial Debt</small></div></div>
            <div class="col-md-3"><div class="card-stat bg-gradient-total"><h6>کۆی نرخی شتومەک</h6><h3 id="total-price" class="fw-bold fs-4">...</h3><small>بەپێی فلتەر</small></div></div>
        </div>

        <div class="row mb-4 align-items-end">
            <div class="col-md-3"><label class="form-label small fw-bold">لە بەرواری:</label><input type="date" id="from_date" class="form-control rounded-3 shadow-none"></div>
            <div class="col-md-3"><label class="form-label small fw-bold">بۆ بەرواری:</label><input type="date" id="to_date" class="form-control rounded-3 shadow-none"></div>
            <div class="col-md-4">
                <button onclick="applyFilters()" class="btn btn-teal rounded-pill px-4 me-2">فلتەر بکە</button>
                <button onclick="resetFilters()" class="btn btn-light rounded-pill px-4">بەتاڵکردنەوە</button>
            </div>
        </div>

        <ul class="nav nav-tabs border-0" id="profileTabs">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#purchases">مێژووی کڕینەکان</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#debt">دانەوەی قەرزەکان</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#adjustment">ڕێکخستنەوەی حیساب</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#locations_statement">کەشف حیسابی شوێنەکان</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#drivers_statement">کەشف حیسابی شۆفێرەکان</button></li>
        </ul>
        
        <div class="tab-content border-0">
            <div class="tab-pane fade show active" id="purchases"><table id="purchasesTable" class="table table-hover w-100"></table></div>
            <div class="tab-pane fade" id="debt">
                <div class="d-flex justify-content-end mb-3"><button class="btn btn-success fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addDebtModal"><i class="fas fa-plus-circle me-1"></i> پارەدان یان داشکاندن</button></div>
                <table id="debtTable" class="table table-hover w-100"></table>
            </div>
            <div class="tab-pane fade" id="adjustment">
                <div class="d-flex justify-content-end mb-3"><button class="btn btn-warning fw-bold rounded-pill px-4 text-dark" data-bs-toggle="modal" data-bs-target="#addAdjustmentModal"><i class="fas fa-sync-alt me-1"></i> ڕێکخستنەوەی حیساب</button></div>
                <table id="adjustmentTable" class="table table-hover w-100 text-center"></table>
            </div>
            <div class="tab-pane fade" id="locations_statement">
                <table id="locationsSummaryTable" class="table table-hover w-100 text-center"></table>
            </div>
            <div class="tab-pane fade" id="drivers_statement">
                <table id="driversSummaryTable" class="table table-hover w-100 text-center"></table>
            </div>
        </div>
    </div>
</main>

<!-- Modals -->
<div class="modal fade" id="addDebtModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md"><div class="modal-content border-0 shadow"><form id="addDebtForm">
        <input type="hidden" name="company_id" value="<?= $company_id ?>">
        <div class="modal-header bg-success text-white"><h5 class="modal-title fw-bold">تۆمارکردنی پارەدان</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <div class="mb-3"><label class="form-label fw-bold">بەروار:</label><input type="date" class="form-control" name="date" required value="<?= date('Y-m-d') ?>"></div>
            <div class="mb-3">
                <label class="form-label fw-bold">نرخی ١٠٠ دۆلار:</label>
                <div class="input-group">
                    <input type="number" class="form-control fw-bold text-primary" id="debt_dollar_rate" name="dollar_rate" value="150000">
                    <button type="button" class="btn btn-outline-secondary" onclick="fetchAndSetDollarRate('debt_dollar_rate')">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-6"><label class="form-label">پارەی داوە (دۆلار)</label><input type="number" step="0.01" class="form-control" id="debt_amount_usd" name="amount_usd" value="0"></div>
                <div class="col-6"><label class="form-label">پارەی داوە (دینار)</label><input type="number" step="1" class="form-control" id="debt_amount_iqd" name="amount_iqd" value="0"></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-6"><label class="form-label">داشکاندن (دۆلار)</label><input type="number" step="0.01" class="form-control" id="debt_discount_usd" name="discount_usd" value="0"></div>
                <div class="col-6"><label class="form-label">داشکاندن (دینار)</label><input type="number" step="1" class="form-control" id="debt_discount_iqd" name="discount_iqd" value="0"></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-6"><label class="form-label">باقی (دۆلار)</label><input type="number" step="0.01" class="form-control" id="debt_change_back_usd" name="change_back_usd" value="0"></div>
                <div class="col-6"><label class="form-label">باقی (دینار)</label><input type="number" step="1" class="form-control" id="debt_change_back_iqd" name="change_back_iqd" value="0"></div>
            </div>
            <div class="row g-3 mt-3">
                <div class="col-6">
                    <label class="form-label small text-muted">قەرزی نوێ (USD)</label>
                    <input type="text" class="form-control bg-light fw-bold" id="total_remaining_usd" readonly>
                </div>
                <div class="col-6">
                    <label class="form-label small text-muted">قەرزی نوێ (IQD)</label>
                    <input type="text" class="form-control bg-light fw-bold" id="total_remaining_iqd" readonly>
                </div>
            </div>
            <div class="mb-3 mt-3"><label class="form-label">تێبینی:</label><textarea class="form-control" name="note" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-success fw-bold px-5">پاشەکەوتکردن</button></div>
    </form></div></div>
</div>

<div class="modal fade" id="addAdjustmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md"><div class="modal-content border-0 shadow"><form id="addAdjustmentForm">
        <input type="hidden" name="company_id" value="<?= $company_id ?>">
        <div class="modal-header bg-warning"><h5 class="modal-title fw-bold">ڕێکخستنەوەی حیساب</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <p class="text-muted small">بۆ زیادکردنی قەرزی کۆمپانیا بڕی ئاسایی و بۆ کەمکردنەوە بڕی نێگەتیڤ (-) بنووسە.</p>
            <div class="row g-3">
                <div class="col-6"><label class="form-label">بڕ (دۆلار)</label><input type="number" step="0.01" class="form-control" name="amount_usd" value="0"></div>
                <div class="col-6"><label class="form-label">بڕ (دینار)</label><input type="number" step="1" class="form-control" name="amount_iqd" value="0"></div>
            </div>
            <div class="mb-3 mt-3"><label class="form-label">هۆکاری ڕێکخستنەوە:</label><textarea class="form-control" name="note" rows="3" required></textarea></div>
            <div class="mb-3"><label class="form-label">بەروار:</label><input type="date" class="form-control" name="date" required value="<?= date('Y-m-d') ?>"></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-warning fw-bold px-5">تۆمارکردن</button></div>
    </form></div></div>
</div>

<div class="modal fade" id="locationLedgerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background: #20b2aa;">
                <h5 class="modal-title fw-bold" id="locationLedgerTitle">کەشف حیسابی شوێن</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table id="locationLedgerTable" class="table table-hover w-100 text-center"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="driverLedgerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background: #20b2aa;">
                <h5 class="modal-title fw-bold" id="driverLedgerTitle">کەشف حیسابی شۆفێر</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table id="driverLedgerTable" class="table table-hover w-100 text-center"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.0/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.0/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const COMPANY_ID = <?= $company_id ?>;
    let currentFilters = { from_date: '', to_date: '' };

    function loadStats() {
        $.get('../process/company_profile/select_debt.php', { company_id: COMPANY_ID, stats: 1, ...currentFilters }, function(res) {
            const s = res.stats;
            $('#total-debt').text(s.total_debt_usd.toLocaleString() + ' $ / ' + s.total_debt_iqd.toLocaleString() + ' د.ع');
            $('#credit-count').text(s.credit_count);
            $('#opening-debt').text(s.opening_debt_usd.toLocaleString() + ' $ / ' + s.opening_debt_iqd.toLocaleString() + ' د.ع');
            $('#total-price').text(s.total_price_usd.toLocaleString() + ' $ / ' + s.total_price_iqd.toLocaleString() + ' د.ع');
        });
    }

    function applyFilters() {
        currentFilters.from_date = $('#from_date').val();
        currentFilters.to_date = $('#to_date').val();
        // Refresh all data without page reload
        if (typeof loadDebts === 'function') loadDebts();
        if (typeof loadPurchases === 'function') loadPurchases();
        if (typeof loadStats === 'function') loadStats();
        if (typeof loadAdjustments === 'function') loadAdjustments();
        if (typeof loadLocationsSummary === 'function') loadLocationsSummary();
        if (typeof loadDriversSummary === 'function') loadDriversSummary();
    }

    function resetFilters() {
        $('#from_date, #to_date').val('');
        currentFilters = { from_date: '', to_date: '' };
        applyFilters();
    }

    $('#addAdjustmentForm').on('submit', function(e) {
        e.preventDefault();
        $.post('../process/company_profile/add_adjustment.php', $(this).serialize(), function(res) {
            if(res.success) {
                $('#addAdjustmentModal').modal('hide');
                Swal.fire('سەرکەوتوو', 'ڕێکخستنەوە فەرمییەکە تۆمارکرا', 'success').then(() => location.reload());
            } else Swal.fire('هەڵە', res.msg, 'error');
        }, 'json');
    });

    function loadAdjustments() {
        $.get('../process/company_profile/select_adjustments.php', { company_id: COMPANY_ID, ...currentFilters }, function(data) {
            let html = '<thead><tr><th>بەروار</th><th class="text-center">دۆلار ($)</th><th class="text-center">دینار (د.ع)</th><th>هۆکار / تێبینی</th><th>کردار</th></tr></thead><tbody>';
            data.forEach(adj => {
                html += `<tr>
                    <td>${adj.date}</td>
                    <td class="fw-bold ${adj.amount_usd < 0 ? 'text-danger': 'text-success'}">${parseFloat(adj.amount_usd).toLocaleString()} $</td>
                    <td class="fw-bold ${adj.amount_iqd < 0 ? 'text-danger': 'text-success'}">${parseFloat(adj.amount_iqd).toLocaleString()} د.ع</td>
                    <td>${adj.note}</td>
                    <td><button onclick="deleteAdjustment(${adj.id})" class="btn btn-sm btn-outline-danger border-0"><i class="fas fa-trash"></i></button></td>
                </tr>`;
            });
            if(data.length === 0) html += '<tr><td colspan="5">هیچ داتایەک نییە</td></tr>';
            html += '</tbody>';
            $('#adjustmentTable').html(html);
        }, 'json');
    }

    function deleteAdjustment(id) {
        Swal.fire({ title: 'دڵنیای؟', text: "ئەم حیسابە دەگەڕێتەوە بارەکەی پێشوو", icon: 'warning', showCancelButton: true, confirmButtonText: 'بەڵێ، بیسڕەوە' }).then(r => {
            if(r.isConfirmed) $.post('../process/company_profile/delete_adjustment.php', {id}, res => { if(res.success) location.reload(); }, 'json');
        });
    }

    $(document).ready(function() {
        loadStats(); loadAdjustments();
    });
</script>

<!-- Existing Custom Scripts -->
<script src="../assets/js/company_profile/select_purchases.js?v=1.1"></script>
<script src="../assets/js/company_profile/select_debt.js?v=1.1"></script>
<script src="../assets/js/company_profile/add_debt.js?v=1.1"></script>
<script src="../assets/js/company_profile/select_locations_statement.js?v=1.1"></script>
<script src="../assets/js/company_profile/select_drivers_statement.js?v=1.1"></script>

</body>
</html>
