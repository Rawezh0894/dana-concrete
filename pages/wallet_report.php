<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

$user_id = $_SESSION['user_id'];

// --- وەرگرتنی فلتەرەکان (Filters) ---
$from_date = $_GET['from_date'] ?? date('Y-m-01'); // سەرەتای مانگ بە دیفاڵت
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$currency = $_GET['currency'] ?? 'USD'; // دۆلار بە دیفاڵت
$type_filter = $_GET['type'] ?? 'all';

// --- هێنانی زانیاری بەکارهێنەر بۆ هێدەری ڕاپۆرت ---
$stmtUser = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user_info = $stmtUser->fetch();
$user_name = $user_info['username'] ?? 'بەکارهێنەر';

// --- یەکلاییکردنەوەی فلتەری دراو ---
$currency_condition = "";
$currency_params = [];
if ($currency !== 'ALL') {
    $currency_condition = "AND l.currency_code = :currency";
    $currency_params['currency'] = $currency;
}

// --- 1. هێنانەی باڵانسی سەرەتا (Opening Balance) ---
// باڵانسی سەرەتا بریتییە لە کۆی هەموو ئەو پارانەی هاتووە یان دەرچووە پێش بەرواری دیاریکراو
$sql_opening = "
    SELECT l.currency_code, SUM(l.amount) as opening_balance
    FROM ledger_entries l
    JOIN transactions t ON l.transaction_id = t.id
    JOIN wallets w ON l.wallet_id = w.id
    WHERE w.user_id = :user_id 
    $currency_condition
    AND DATE(t.created_at) < :from_date
    GROUP BY l.currency_code
";
$stmtOpening = $pdo->prepare($sql_opening);
$params_opening = array_merge(['user_id' => $user_id, 'from_date' => $from_date], $currency_params);
$stmtOpening->execute($params_opening);
$opening_data = $stmtOpening->fetchAll(PDO::FETCH_KEY_PAIR);

$opening_balances = [
    'USD' => floatval($opening_data['USD'] ?? 0),
    'IQD' => floatval($opening_data['IQD'] ?? 0)
];

// --- 2. هێنانی مامەڵەکانی ناو ماوەی دیاریکراو ---
$sql_transactions = "
    SELECT t.id, t.created_at, t.type as trans_type, l.amount, l.currency_code, l.description, tc.name as category_name
    FROM ledger_entries l
    JOIN transactions t ON l.transaction_id = t.id
    LEFT JOIN transaction_categories tc ON t.category_id = tc.id
    JOIN wallets w ON l.wallet_id = w.id
    WHERE w.user_id = :user_id 
    $currency_condition
    AND DATE(t.created_at) >= :from_date 
    AND DATE(t.created_at) <= :to_date
";

if ($type_filter === 'inflow') {
    $sql_transactions .= " AND l.amount > 0 AND t.type != 'EXCHANGE'";
} elseif ($type_filter === 'outflow') {
    $sql_transactions .= " AND l.amount < 0 AND t.type != 'EXCHANGE'";
} elseif ($type_filter === 'exchange') {
    $sql_transactions .= " AND t.type = 'EXCHANGE'";
}

$sql_transactions .= " ORDER BY t.created_at ASC, t.id ASC"; // دەبێت ڕیزبەندی کات بێت بۆ باڵانسی تراکەمی

$stmtTrans = $pdo->prepare($sql_transactions);
$params_trans = array_merge(['user_id' => $user_id, 'from_date' => $from_date, 'to_date' => $to_date], $currency_params);
$stmtTrans->execute($params_trans);
$transactions = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

// --- 3. لۆجیکی Running Balance (باڵانسی تراکەمی) و ئامارەکان ---
$total_inflow = ['USD' => 0, 'IQD' => 0];
$total_outflow = ['USD' => 0, 'IQD' => 0];

foreach ($transactions as $row) {
    if ($row['amount'] > 0) {
        $total_inflow[$row['currency_code']] += $row['amount'];
    } else {
        $total_outflow[$row['currency_code']] += abs($row['amount']);
    }
}

// بۆ کەیسی Running Balance پێویستە هەموو مامەڵەکان بهێنین بۆ ئەوەی ڕیزبەندییەکە دروست بێت
$stmtAllTrans = $pdo->prepare("
    SELECT t.id, t.created_at, t.type as trans_type, l.amount, l.currency_code, l.description, tc.name as category_name
    FROM ledger_entries l
    JOIN transactions t ON l.transaction_id = t.id
    LEFT JOIN transaction_categories tc ON t.category_id = tc.id
    JOIN wallets w ON l.wallet_id = w.id
    WHERE w.user_id = :user_id 
    $currency_condition
    AND DATE(t.created_at) >= :from_date 
    AND DATE(t.created_at) <= :to_date
    ORDER BY t.created_at ASC, t.id ASC
");
$stmtAllTrans->execute($params_trans);
$all_transactions = $stmtAllTrans->fetchAll(PDO::FETCH_ASSOC);

$running_balances = [];
$current_balances = [
    'USD' => $opening_balances['USD'],
    'IQD' => $opening_balances['IQD']
];

foreach ($all_transactions as $row) {
    $current_balances[$row['currency_code']] += $row['amount'];
    $running_balances[$row['id']] = $current_balances[$row['currency_code']];
}

$final_balances = [
    'USD' => $opening_balances['USD'] + $total_inflow['USD'] - $total_outflow['USD'],
    'IQD' => $opening_balances['IQD'] + $total_inflow['IQD'] - $total_outflow['IQD']
];

?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕاپۆرتی قاسە - کشف حساب</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SheetJS for Excel Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <style>
        body { font-family: 'Rabar', sans-serif; background-color: #f4f6f9; }
        .summary-card { border-radius: 10px; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .print-header { display: none; text-align: center; margin-bottom: 20px; }
        .currency-symbol { font-size: 1.2rem; font-weight: bold; }
        .table-responsive { background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        /* Print Optimization - Professional Layout */
        @media print {
            /* Override global variables.css rule causing white screen */
            body * { visibility: visible !important; position: static !important; }
            html, body {
                height: auto !important;
                overflow: visible !important;
                position: static !important;
                background-color: #fff !important; 
                margin: 0; 
                padding: 0; 
                font-size: 11pt; 
                color: #000;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print, .no-print *, .sidebar, .sidebar *, .navbar, .navbar *, .filters-section, .filters-section *, .btn, .btn * { 
                display: none !important; 
                visibility: hidden !important;
            }
            .print-header { 
                display: flex !important; 
                flex-direction: column;
                align-items: center;
                border-bottom: 2px solid #000; 
                padding-bottom: 15px; 
                margin-bottom: 25px; 
            }
            .print-header h2 {
                color: #000 !important;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .print-header h4 {
                color: #444 !important;
                margin-bottom: 15px;
            }
            .print-header .d-flex {
                width: 100%;
                justify-content: space-between;
                border-top: 1px dashed #ccc;
                padding-top: 10px;
                font-size: 12pt;
            }
            .table-responsive { 
                overflow: visible !important; 
                box-shadow: none !important; 
                background: transparent !important;
            }
            .table { 
                width: 100% !important; 
                border-collapse: collapse !important; 
                margin-bottom: 0 !important;
            }
            .table-bordered th, .table-bordered td { 
                border: 1px solid #000 !important; 
                padding: 8px 10px !important; 
                color: #000 !important;
            }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            th { 
                background-color: #f0f0f0 !important; 
                color: #000 !important; 
                font-weight: bold;
                text-align: center !important;
            }
            .summary-card { 
                border: 1px solid #000 !important; 
                break-inside: avoid; 
                page-break-inside: avoid; 
                margin-bottom: 20px; 
                background-color: #fff !important;
                box-shadow: none !important;
            }
            @page { size: A4 portrait; margin: 15mm; }
            
            .container-fluid { 
                padding: 0 !important; 
                margin: 0 !important;
                width: 100% !important;
            }
            
            .row {
                display: flex !important;
                flex-wrap: wrap !important;
                margin-right: -5px !important;
                margin-left: -5px !important;
            }
            .col-xl-3, .col-md-6 { 
                width: 25% !important; 
                flex: 0 0 25% !important; 
                max-width: 25% !important; 
                padding: 5px !important; 
                box-sizing: border-box !important;
            }
            .card-body {
                padding: 10px 15px !important;
            }
            .text-xs { font-size: 9pt !important; color: #555 !important; }
            .h4 { font-size: 13pt !important; color: #000 !important; font-weight: bold; margin-top: 5px; }
            .fa-2x { font-size: 1.5em !important; opacity: 0.8 !important; }
            
            /* Badges for Print */
            .badge {
                border: 1px solid #000 !important;
                color: #000 !important;
                background: transparent !important;
                padding: 4px 8px !important;
                font-weight: bold !important;
            }
            #reportTable td, #reportTable th {
                vertical-align: middle;
            }
            .fs-5 { font-size: 1.1rem !important; }
            .fs-6 { font-size: 1rem !important; }
        }
    </style>
</head>
<body dir="rtl">
    <div class="no-print">
        <?php include '../includes/navbar.php'; ?>
        <?php include '../includes/sidebar.php'; ?>
    </div>

    <!-- Print Header (Hidden on Web) -->
    <div class="print-header">
        <h2 class="fw-bold">کارگەی کۆنکرێتی دانا</h2>
        <h4>ڕاپۆرتی جوڵەی قاسە (کشف حساب)</h4>
        <div class="d-flex justify-content-between mt-3" style="font-size: 14pt;">
            <span><strong>ناوی قاسە:</strong> <?= htmlspecialchars($user_name) ?></span>
            <span><strong>جۆری دراو:</strong> <?= $currency == 'ALL' ? 'گشتی (هەردووکی)' : $currency ?></span>
            <span><strong>بەروار:</strong> <?= $from_date ?> بـــۆ <?= $to_date ?></span>
        </div>
    </div>

    <div class="container-fluid py-4 pl-print-0 pr-print-0 mb-5">
        
        <!-- Header & Actions -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 no-print gap-2">
            <div>
                <h2 class="fw-bold" style="color: var(--seafoam-green);"><i class="fa fa-file-invoice-dollar me-2"></i> ڕاپۆرتی دارایی</h2>
                <span class="text-muted">ڕاپۆرتی سەرجەم جوڵەکانی قاسە بۆ بەکارهێنەر</span>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-primary fw-bold px-4 hover-shadow"><i class="fa fa-print me-2"></i> چاپکردن</button>
                <button onclick="exportToExcel()" class="btn btn-success fw-bold px-4 hover-shadow"><i class="fa fa-file-excel me-2"></i> ئێکسڵ</button>
                <a href="user_wallets.php" class="btn btn-secondary px-3"><i class="fa fa-arrow-right me-1"></i> گەڕانەوە</a>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card shadow-sm border-0 mb-4 filters-section no-print">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">لە بەرواری</label>
                        <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">تا بەرواری</label>
                        <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">هەڵبژاردنی دراو</label>
                        <select name="currency" class="form-select">
                            <option value="ALL" <?= $currency == 'ALL' ? 'selected' : '' ?>>گشتی (All)</option>
                            <option value="USD" <?= $currency == 'USD' ? 'selected' : '' ?>>دۆلار (USD)</option>
                            <option value="IQD" <?= $currency == 'IQD' ? 'selected' : '' ?>>دینار (IQD)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">جۆری مامەڵە</label>
                        <select name="type" class="form-select">
                            <option value="all" <?= $type_filter == 'all' ? 'selected' : '' ?>>گشتی (All)</option>
                            <option value="inflow" <?= $type_filter == 'inflow' ? 'selected' : '' ?>>هاتن (Inflow)</option>
                            <option value="outflow" <?= $type_filter == 'outflow' ? 'selected' : '' ?>>چوون (Outflow)</option>
                            <option value="exchange" <?= $type_filter == 'exchange' ? 'selected' : '' ?>>ئاڵوگۆڕ (Exchange)</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-start">
                        <button type="submit" class="btn fw-bold w-100" style="background-color: var(--seafoam-green); color: white;"><i class="fa fa-search me-1"></i> فلتەرکردن</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card summary-card border-left-info h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-info text-uppercase mb-1">باڵانسی سەرەتا (Opening)</div>
                                <?php if ($currency === 'ALL' || $currency === 'USD'): ?>
                                    <div class="h5 mb-0 fw-bold border-bottom pb-1" style="color: #003b73;" dir="ltr"><?= number_format($opening_balances['USD'], 2) ?> <span class="fs-6">USD</span></div>
                                <?php endif; ?>
                                <?php if ($currency === 'ALL' || $currency === 'IQD'): ?>
                                    <div class="h5 mb-0 fw-bold mt-1" style="color: #0074b7;" dir="ltr"><?= number_format($opening_balances['IQD'], 0) ?> <span class="fs-6">IQD</span></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-auto"><i class="fa fa-hourglass-start fa-2x text-info opacity-50"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card summary-card border-left-success h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">کۆی هاتن (Total Inflow)</div>
                                <?php if ($currency === 'ALL' || $currency === 'USD'): ?>
                                    <div class="h5 mb-0 fw-bold border-bottom pb-1 text-success" dir="ltr">+ <?= number_format($total_inflow['USD'], 2) ?> <span class="fs-6">USD</span></div>
                                <?php endif; ?>
                                <?php if ($currency === 'ALL' || $currency === 'IQD'): ?>
                                    <div class="h5 mb-0 fw-bold mt-1 text-success" dir="ltr">+ <?= number_format($total_inflow['IQD'], 0) ?> <span class="fs-6">IQD</span></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-auto"><i class="fa fa-arrow-down fa-2x text-success opacity-50"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card summary-card border-left-danger h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-danger text-uppercase mb-1">کۆی چوون (Total Outflow)</div>
                                <?php if ($currency === 'ALL' || $currency === 'USD'): ?>
                                    <div class="h5 mb-0 fw-bold border-bottom pb-1 text-danger" dir="ltr">- <?= number_format($total_outflow['USD'], 2) ?> <span class="fs-6">USD</span></div>
                                <?php endif; ?>
                                <?php if ($currency === 'ALL' || $currency === 'IQD'): ?>
                                    <div class="h5 mb-0 fw-bold mt-1 text-danger" dir="ltr">- <?= number_format($total_outflow['IQD'], 0) ?> <span class="fs-6">IQD</span></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-auto"><i class="fa fa-arrow-up fa-2x text-danger opacity-50"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card summary-card border-left-primary h-100 py-2 bg-light">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">باڵانسی کۆتایی (Closing)</div>
                                <?php if ($currency === 'ALL' || $currency === 'USD'): ?>
                                    <div class="h5 mb-0 fw-bold border-bottom pb-1 text-primary" dir="ltr"><?= number_format($final_balances['USD'], 2) ?> <span class="fs-6">USD</span></div>
                                <?php endif; ?>
                                <?php if ($currency === 'ALL' || $currency === 'IQD'): ?>
                                    <div class="h5 mb-0 fw-bold mt-1 text-primary" dir="ltr"><?= number_format($final_balances['IQD'], 0) ?> <span class="fs-6">IQD</span></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-auto"><i class="fa fa-wallet fa-2x text-primary opacity-50"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="table-responsive p-3">
            <h5 class="fw-bold mb-3 d-none d-print-block">خشتەی مامەڵەکان</h5>
            <table class="table table-bordered table-striped align-middle text-center mb-0" id="reportTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>بەروار و کات</th>
                        <th>جۆری مامەڵە</th>
                        <th>دراو</th>
                        <th>پۆلێن / هۆکار</th>
                        <th>هاتن (In)</th>
                        <th>چوون (Out)</th>
                        <th>باڵانسی تراکەمی (Bal)</th>
                        <th width="20%">تێبینی</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row -->
                    <?php if ($currency === 'ALL' || $currency === 'USD'): ?>
                    <tr class="table-secondary">
                        <td>-</td>
                        <td colspan="4" class="fw-bold text-start">باڵانسی پێشوو دۆلار (Opening Balance USD) تا <?= $from_date ?></td>
                        <td dir="ltr" class="fw-bold fs-6 text-primary"><?= number_format($opening_balances['USD'], 2) ?></td>
                        <td>-</td>
                        <td dir="ltr" class="fw-bold fs-6 text-primary"><?= number_format($opening_balances['USD'], 2) ?> USD</td>
                        <td>-</td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($currency === 'ALL' || $currency === 'IQD'): ?>
                    <tr class="table-secondary">
                        <td>-</td>
                        <td colspan="4" class="fw-bold text-start">باڵانسی پێشوو دینار (Opening Balance IQD) تا <?= $from_date ?></td>
                        <td dir="ltr" class="fw-bold fs-6 text-primary"><?= number_format($opening_balances['IQD'], 0) ?></td>
                        <td>-</td>
                        <td dir="ltr" class="fw-bold fs-6 text-primary"><?= number_format($opening_balances['IQD'], 0) ?> IQD</td>
                        <td>-</td>
                    </tr>
                    <?php endif; ?>

                    <?php 
                    $counter = 1;
                    foreach ($transactions as $row): 
                        // داواکردنی ڕەنینگ باڵانس لەو ئەرەییەی کە بۆی دروستکراوە
                        $current_rb = $running_balances[$row['id']] ?? 0;
                        $is_inflow = $row['amount'] > 0;
                        $decimals = $row['currency_code'] === 'IQD' ? 0 : 2;
                    ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><span style="direction: ltr; display: inline-block; font-size: 0.9em;"><?= $row['created_at'] ?></span></td>
                            <td>
                                <?php if($row['trans_type'] === 'EXCHANGE'): ?>
                                    <span class="badge bg-warning text-dark no-print">ئاڵوگۆڕ</span>
                                    <span class="d-none d-print-block fw-bold text-warning border border-warning p-1">ئاڵوگۆڕ</span>
                                <?php elseif($is_inflow): ?>
                                    <span class="badge bg-success no-print">هاتن</span>
                                    <span class="d-none d-print-block fw-bold text-success border border-success p-1">هاتن</span>
                                <?php else: ?>
                                    <span class="badge bg-danger no-print">دەرچوون</span>
                                    <span class="d-none d-print-block fw-bold text-danger border border-danger p-1">چوون</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $row['currency_code'] ?></span></td>
                            <td><?= htmlspecialchars($row['category_name'] ?? 'بێ جۆر') ?></td>
                            
                            <!-- Inflow Column -->
                            <td dir="ltr" class="fw-bold text-success">
                                <?= $is_inflow ? number_format($row['amount'], $decimals) : '-' ?>
                            </td>
                            
                            <!-- Outflow Column -->
                            <td dir="ltr" class="fw-bold text-danger">
                                <?= !$is_inflow ? number_format(abs($row['amount']), $decimals) : '-' ?>
                            </td>
                            
                            <!-- Running Balance Column -->
                            <td dir="ltr" class="fw-bold text-primary fs-6" style="background:#f8f9fa;">
                                <?= number_format($current_rb, $decimals) ?> <span style="font-size: 0.8em;"><?= $row['currency_code'] ?></span>
                            </td>
                            
                            <td class="text-end small"><?= htmlspecialchars($row['description'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="9" class="text-muted py-4">کشف حسابی ئەم ناوچەیە بەتاڵە</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Script for Export to Excel -->
    <script>
        function exportToExcel() {
            var wb = XLSX.utils.table_to_book(document.getElementById('reportTable'), {sheet:"Account Statement"});
            XLSX.writeFile(wb, 'Cashbox_Statement_<?= $currency ?>_<?= $from_date ?>_to_<?= $to_date ?>.xlsx');
        }
    </script>
</body>
</html>
