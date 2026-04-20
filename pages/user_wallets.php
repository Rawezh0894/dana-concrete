<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

$user_id = $_SESSION['user_id'];

// ڕاستەوخۆ دیفاولت قاسە دەیکەینەوە بۆ بەکارهێنەر گەر یەکەم جاری بێت
$pdo->exec("INSERT IGNORE INTO wallets (user_id, currency_code) VALUES ($user_id, 'USD'), ($user_id, 'IQD')");

// هێنانی باڵانسەکان
$stmt = $pdo->prepare("SELECT currency_code, balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallets = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$usd_balance = floatval($wallets['USD'] ?? 0);
$iqd_balance = floatval($wallets['IQD'] ?? 0);

// هێنانی جۆرەکانی مامەڵە (Categories)
$stmt = $pdo->query("SELECT id, name, type FROM transaction_categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// هێنانی کۆی گشتی هاتوو و ڕۆیشتوو (Inflow/Outflow Totals)
$stmt = $pdo->prepare("
    SELECT 
        le.currency_code,
        SUM(CASE WHEN le.amount > 0 THEN le.amount ELSE 0 END) as total_inflow,
        SUM(CASE WHEN le.amount < 0 THEN ABS(le.amount) ELSE 0 END) as total_outflow
    FROM ledger_entries le
    JOIN transactions t ON le.transaction_id = t.id
    WHERE t.created_by = ?
    GROUP BY le.currency_code
");
$stmt->execute([$user_id]);
$totals_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totals = [
    'USD' => ['in' => 0, 'out' => 0],
    'IQD' => ['in' => 0, 'out' => 0]
];

foreach ($totals_raw as $row) {
    if (isset($totals[$row['currency_code']])) {
        $totals[$row['currency_code']]['in'] = floatval($row['total_inflow']);
        $totals[$row['currency_code']]['out'] = floatval($row['total_outflow']);
    }
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قاسەی بەکارهێنەر - User Wallets</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <style>
        body { font-family: 'Rabar', sans-serif; background-color: #f4f6f9; }
        .wallet-card { border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; border: none;}
        .wallet-card:hover { transform: translateY(-5px); }
        .currency-symbol { font-size: 1.5rem; font-weight: bold; opacity: 0.8; }
        .balance { font-size: clamp(1.5rem, 5vw, 2.5rem); font-weight: bold; }
        .quick-action { background-color: var(--seafoam-green); color: white; font-weight: bold; border-radius: 8px; padding: 10px 20px; transition: all 0.3s ease; border: none; flex: 1; text-align: center; }
        .quick-action:hover { background-color: var(--kelly-green); color: white; transform: scale(1.05); }
        
        .stat-card {
            border-radius: 12px;
            border: none;
            overflow: hidden;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            z-index: -1;
        }
        .stat-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }
        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        .stat-icon {
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            font-size: 3rem;
            opacity: 0.15;
            pointer-events: none;
        }

        .main-content {
            transition: all 0.3s;
            padding: 20px;
        }

        /* Responsive Adjustments */
        @media (max-width: 991.98px) {
            .quick-actions { width: 100%; display: grid !important; grid-template-columns: repeat(2, 1fr); gap: 10px !important; }
            .quick-action { padding: 12px 10px; font-size: 0.85rem; }
            .balance { font-size: 1.8rem; }
        }

        @media (max-width: 575.98px) {
            .quick-actions { grid-template-columns: 1fr; }
            .header-title h2 { font-size: 1.5rem; }
            .filters-section .row > div { margin-bottom: 10px; }
            .card-header h5 { font-size: 1rem; }
            .dt-filter { font-size: 0.9rem; }
        }

        /* Fix for DataTables on mobile */
        .dataTables_wrapper .row {
            margin-bottom: 1rem;
        }
        .table-responsive {
            border: none !important;
        }
        #transactionsTable {
            font-size: 0.95rem;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        #transactionsTable thead th {
            background-color: #f8f9fa;
            border: none;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 15px;
            text-align: center !important;
        }
        #transactionsTable tbody tr {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        #transactionsTable tbody tr:hover {
            background-color: #f1f4f9;
            transform: scale(1.002);
        }
        #transactionsTable tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            border: none;
            text-align: center !important;
        }
        #transactionsTable tbody td:first-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        #transactionsTable tbody td:last-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }
        .badge {
            font-weight: 500;
            padding: 0.5em 0.8em;
        }
    </style>
</head>
<body dir="rtl">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 header-title">
                <h2 class="mb-0 fw-bold" style="color: var(--seafoam-green);">بەڕێوەبردنی قاسە (Cashbox)</h2>
                <div class="quick-actions d-flex gap-2 flex-grow-1 flex-md-grow-0 justify-content-end">
                    <a href="wallet_report.php" class="btn quick-action bg-info text-dark"><i class="fa fa-file-invoice"></i> ڕاپۆرت</a>
                    <button class="btn quick-action" data-bs-toggle="modal" data-bs-target="#actionModal" onclick="setFormAction('inflow')"><i class="fa fa-plus"></i> هاتن</button>
                    <button class="btn quick-action" data-bs-toggle="modal" data-bs-target="#actionModal" onclick="setFormAction('outflow')"><i class="fa fa-minus"></i> چوون</button>
                    <button class="btn quick-action bg-warning text-dark" data-bs-toggle="modal" data-bs-target="#exchangeModal"><i class="bi bi-arrow-left-right"></i> گۆڕینەوە</button>
                </div>
            </div>
            
            <!-- Balance Cards -->
        <div class="row g-3 mb-4">
            <!-- USD Summary -->
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="stat-label">کۆیی هاتوو (USD)</div>
                        <div class="stat-value" id="usdInVal">$ <?= number_format($totals['USD']['in'], 2) ?></div>
                        <i class="fas fa-arrow-down stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-danger text-white h-100">
                    <div class="card-body">
                        <div class="stat-label">کۆیی ڕۆیشتوو (USD)</div>
                        <div class="stat-value" id="usdOutVal">$ <?= number_format($totals['USD']['out'], 2) ?></div>
                        <i class="fas fa-arrow-up stat-icon"></i>
                    </div>
                </div>
            </div>
            
            <!-- IQD Summary -->
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="stat-label">کۆیی هاتوو (IQD)</div>
                        <div class="stat-value" id="iqdInVal"><?= number_format($totals['IQD']['in'], 0) ?> <small>د.ع</small></div>
                        <i class="fas fa-arrow-down stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-danger text-white h-100">
                    <div class="card-body">
                        <div class="stat-label">کۆیی ڕۆیشتوو (IQD)</div>
                        <div class="stat-value" id="iqdOutVal"><?= number_format($totals['IQD']['out'], 0) ?> <small>د.ع</small></div>
                        <i class="fas fa-arrow-up stat-icon"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mt-3">
                <div class="card wallet-card bg-dark text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">باڵانسی دۆلار (USD)</h5>
                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <span class="currency-symbol">$</span>
                            <span class="balance" id="usdBalance" data-balance="<?= $usd_balance ?>"><?= number_format($usd_balance, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mt-3">
                <div class="card wallet-card bg-dark text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">باڵانسی دینار (IQD)</h5>
                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <span class="currency-symbol">د.ع</span>
                            <span class="balance" id="iqdBalance" data-balance="<?= $iqd_balance ?>"><?= number_format($iqd_balance, 0) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm border-0 mb-4 filters-section" style="border-radius: 12px;">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label small fw-bold text-muted">لە بەرواری</label>
                        <input type="date" id="filterFrom" class="form-control dt-filter">
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label small fw-bold text-muted">تا بەرواری</label>
                        <input type="date" id="filterTo" class="form-control dt-filter">
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label small fw-bold text-muted">جۆری مامەڵە</label>
                        <select id="filterType" class="form-select dt-filter">
                            <option value="">گشتی (هەمووی)</option>
                            <option value="INFLOW">هاتن (Inflow)</option>
                            <option value="OUTFLOW">چوون (Outflow)</option>
                            <option value="EXCHANGE">ئاڵوگۆڕ</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label small fw-bold text-muted">هۆکار / پۆلێن</label>
                        <select id="filterCategory" class="form-select dt-filter">
                            <option value="">گشتی (هەمووی)</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label small fw-bold text-muted">بڕی پارە</label>
                        <input type="number" id="filterAmount" class="form-control dt-filter" placeholder="بڕ... ">
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label small fw-bold text-muted">تێبینی</label>
                        <input type="text" id="filterNotes" class="form-control dt-filter" placeholder="گەڕان...">
                    </div>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold" style="color: var(--seafoam-green);">دواین چالاکییەکان و مامەڵەکان</h5>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table id="transactionsTable" class="table align-middle mb-0 text-center w-100 table-hover" style="border-collapse: separate; border-spacing: 0 10px;">
                        <thead>
                            <tr>
                                <th class="text-center">بەروار و کات</th>
                                <th class="text-center">جۆر</th>
                                <th class="text-center">هۆکار</th>
                                <th class="text-center">بڕی USD</th>
                                <th class="text-center">بڕی IQD</th>
                                <th class="text-start">تێبینی</th>
                                <th class="text-center">کردار</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- actionModal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <form id="actionForm">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="actionModalLabel">مامەڵەی قاسە</h5>
                        <button type="button" class="btn-close m-0 ms-auto" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formActionType">
                        <input type="hidden" name="transaction_id" id="editTxId">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold">بەرواری مامەڵە</label>
                            <input type="datetime-local" name="created_at" id="actionDate" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold">هۆکاری مامەڵە</label>
                            <select name="category_id" id="actionCategory" class="form-select" required>
                                <option value="">هەڵبژاردن...</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" data-type="<?= $cat['type'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="p-3 border rounded bg-light">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">بڕی پارە بە هەردوو دراو</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label small text-success">بڕی دۆلار ($)</label>
                                            <input type="number" name="amount_usd" id="amountUSD" class="form-control" step="0.01" placeholder="0.00">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small text-primary">بڕی دینار (IQD)</label>
                                            <input type="number" name="amount_iqd" id="amountIQD" class="form-control" step="250" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold">تێبینی</label>
                            <textarea name="description" id="actionDesc" class="form-control" rows="2" placeholder="تێبینی..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">لابردن</button>
                        <button type="submit" class="btn btn-primary px-4" id="submitBtn">تۆمارکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Exchange Modal (Optional Update) -->
    <div class="modal fade" id="exchangeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow text-center">
                <form id="exchangeForm">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold">ئاڵوگۆڕی دراو</h5>
                        <button type="button" class="btn-close m-0 ms-auto" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <input type="hidden" name="action" value="exchange">
                        <div class="row align-items-center">
                            <div class="col-md-5 mb-3">
                                <label class="mb-2 fw-bold text-danger">لە پارەی</label>
                                <select name="from_currency" id="exFrom" class="form-select" required>
                                    <option value="USD">دۆلار (USD)</option>
                                    <option value="IQD">دینار (IQD)</option>
                                </select>
                            </div>
                            <div class="col-md-2 text-center fs-3 mb-3 text-muted">➡️</div>
                            <div class="col-md-5 mb-3">
                                <label class="mb-2 fw-bold text-success">بۆ پارەی</label>
                                <select name="to_currency" id="exTo" class="form-select" required>
                                    <option value="IQD" selected>دینار (IQD)</option>
                                    <option value="USD">دۆلار (USD)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small">بڕی خەرجکراو</label>
                                <input type="number" name="exchange_amount" id="exAmount" class="form-control" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small">سێرعی گۆڕینەوە</label>
                                <input type="number" name="exchange_rate" class="form-control" step="0.0001" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">لابردن</button>
                        <button type="submit" class="btn btn-warning px-5 fw-bold text-dark">گۆڕینەوە</button>
                    </div>
                </form>
                    </div>
        </div>
    </div>
        </div>
    </div>

    </div>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        function setFormAction(type) {
            $('#formActionType').val(type);
            $('#editTxId').val('');
            $('#submitBtn').text('تۆمارکردن');
            
            const filterType = (type === 'inflow') ? 'INFLOW' : 'OUTFLOW';
            $('#actionCategory option').each(function() {
                const catType = $(this).data('type');
                if (catType && catType !== 'BOTH' && catType !== filterType) $(this).hide();
                else $(this).show();
            });
            $('#actionCategory, #amountUSD, #amountIQD, #actionDesc').val('');
            
            // Set default date to now
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            $('#actionDate').val(`${year}-${month}-${day}T${hours}:${minutes}`);

            if(type === 'inflow'){
                $('#actionModalLabel').text('تۆمارکردنی زیادکردنی پارە');
                $('#actionModal .modal-header').removeClass('bg-danger bg-info').addClass('bg-success text-white');
            } else {
                $('#actionModalLabel').text('تۆمارکردنی خەرجی / ڕاکێشان');
                $('#actionModal .modal-header').removeClass('bg-success bg-info').addClass('bg-danger text-white');
            }
        }

        function prepareEdit(tx) {
            $('#actionModal').modal('show');
            $('#actionModalLabel').text('دەستکاری کردنی مامەڵە');
            $('#actionModal .modal-header').removeClass('bg-success bg-danger').addClass('bg-info text-dark');
            
            $('#formActionType').val('edit_transaction');
            $('#editTxId').val(tx.id);
            $('#actionCategory').val(tx.category_id);
            $('#amountUSD').val(Math.abs(tx.usd_amount || 0));
            $('#amountIQD').val(Math.abs(tx.iqd_amount || 0));
            $('#actionDesc').val(tx.description);
            // Handle date format for datetime-local (replace space with T)
            const txDate = tx.created_at.replace(' ', 'T').substring(0, 16);
            $('#actionDate').val(txDate);
            $('#submitBtn').text('نوێکردنەوە');
            
            // Show all categories for edit
            $('#actionCategory option').show();
        }

        function deleteTransaction(id) {
            if (!confirm('ئایا دڵنیایت لە سڕینەوەی ئەم مامەڵەیە؟ هەموو بڕە پارەکان لە باڵانسەکەت پێچەوانە دەبنەوە.')) return;
            $.ajax({
                url: '../process/user_wallets/process.php',
                type: 'POST',
                data: { action: 'delete_transaction', transaction_id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) location.reload();
                    else alert('هەڵە: ' + res.message);
                }
            });
        }

        $('#actionForm').submit(function(e) {
            e.preventDefault();
            submitData($(this));
        });

        $('#exchangeForm').submit(function(e) {
            e.preventDefault();
            submitData($(this));
        });

        function submitData(form) {
            $.ajax({
                url: '../process/user_wallets/process.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) location.reload();
                    else alert('کێشە: ' + res.message);
                },
                error: function() { alert('هەڵە لە سێرڤەر'); }
            });
        }

        $(document).ready(function() {
            // Initialize DataTable
            let table = $('#transactionsTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "../process/user_wallets/fetch_transactions.php",
                    "type": "POST",
                    "data": function(d) {
                        d.from_date = $('#filterFrom').val();
                        d.to_date = $('#filterTo').val();
                        d.type = $('#filterType').val();
                        d.category = $('#filterCategory').val();
                        d.amount = $('#filterAmount').val();
                        d.notes = $('#filterNotes').val();
                    }
                },
                "drawCallback": function(settings) {
                    let json = settings.json;
                    if (json && json.totals) {
                        $('#usdInVal').text('$ ' + (json.totals.usd_in || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#usdOutVal').text('$ ' + (json.totals.usd_out || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#iqdInVal').html((json.totals.iqd_in || 0).toLocaleString() + ' <small>د.ع</small>');
                        $('#iqdOutVal').html((json.totals.iqd_out || 0).toLocaleString() + ' <small>د.ع</small>');
                    }
                },
                "columns": [
                    { "data": "created_at", "className": "dt-center" },
                    { "data": "type", "className": "dt-center" },
                    { "data": "category", "className": "dt-center" },
                    { "data": "usd", "className": "dt-center" },
                    { "data": "iqd", "className": "dt-center" },
                    { "data": "notes", "className": "dt-start" },
                    { "data": "action", "className": "dt-center", "orderable": false }
                ],
                "language": {
                    "sProcessing":   "چاوەڕێ بە...",
                    "sLengthMenu":   "پیشاندانی _MENU_ ڕیزەکان لە پەڕەیەکدا",
                    "sZeroRecords":  "هیچ داتایەک نەدۆزرایەوە بە پشتبەستن بەم فلتەرانە",
                    "sInfo":         "پیشاندانی _START_ بۆ _END_ لە کۆی _TOTAL_ ڕیکۆرد",
                    "sInfoEmpty":    "پیشاندانی 0 بۆ 0 لە کۆی 0 ڕیکۆرد",
                    "sInfoFiltered": "(فلتەرکراوە لە کۆی _MAX_ ڕیکۆرد)",
                    "oPaginate": {
                        "sFirst":    "یەکەم",
                        "sPrevious": "پێشوو",
                        "sNext":     "دواتر",
                        "sLast":     "کۆتایی"
                    }
                },
                "order": [[0, "desc"]],
                "lengthMenu": [10, 25, 50, 100],
                "pageLength": 10,
                "bFilter": false, // custom filter is used
                "createdRow": function(row, data, dataIndex) {
                    $(row).addClass('shadow-sm bg-white mb-2');
                }
            });

            // Delay timer for typing to prevent overloaded Ajax requests
            let typingTimer;
            const doneTypingInterval = 400; // time in ms 

            $('.dt-filter').on('keyup', function () {
                clearTimeout(typingTimer);
                typingTimer = setTimeout(function() {
                    table.draw();
                }, doneTypingInterval);
            });

            $('.dt-filter').on('change', function () {
                table.draw();
            });
        });
    </script>
</body>
</html>
