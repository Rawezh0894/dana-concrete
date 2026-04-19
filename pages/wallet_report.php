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
        
        /* Print Optimization - Premium Designer Layout */
        @media print {
            body * { visibility: visible !important; position: static !important; }
            html, body {
                height: auto !important;
                overflow: visible !important;
                position: static !important;
                background-color: #fff !important; 
                margin: 0; 
                padding: 0; 
                font-size: 10.5pt; 
                color: #2c3e50;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-family: 'Rabar', sans-serif;
            }
            .no-print, .no-print *, .sidebar, .sidebar *, .navbar, .navbar *, .filters-section, .filters-section *, .btn, .btn * { 
                display: none !important; 
                visibility: hidden !important;
            }
            
            /* Professional Header */
            .print-header { 
                display: block !important; 
                border-bottom: 3px solid #003b73; 
                padding-bottom: 20px; 
                margin-bottom: 30px; 
                text-align: right;
            }
            .print-header h2 {
                color: #003b73 !important;
                font-size: 24pt;
                font-weight: 800;
                margin-bottom: 8px;
                letter-spacing: -0.5px;
            }
            .print-header h4 {
                color: #666 !important;
                font-size: 14pt;
                margin-bottom: 20px;
                font-weight: 500;
            }
            .header-info-grid {
                display: flex;
                justify-content: space-between;
                background: #f8fafc;
                padding: 15px 20px;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                font-size: 11pt;
            }
            .header-info-item strong { color: #003b73; }

            /* Professional Print Summary Section */
            .print-summary-section {
                display: block !important;
                margin-bottom: 30px;
            }
            .print-summary-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            }
            .print-summary-table td {
                padding: 15px;
                border: 1px solid #e2e8f0;
                background: #fff;
                vertical-align: top;
            }
            .summary-label {
                font-size: 9pt;
                color: #64748b;
                text-transform: uppercase;
                font-weight: 700;
                margin-bottom: 5px;
                display: block;
            }
            .summary-value {
                font-size: 13pt;
                font-weight: 800;
                color: #0f172a;
            }
            .val-usd { color: #003b73; display: block; }
            .val-iqd { color: #0074b7; display: block; border-top: 1px dashed #cbd5e1; margin-top: 5px; padding-top: 5px; }

            /* Table Refinement */
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
                border: 1px solid #cbd5e1 !important; 
                padding: 10px 8px !important; 
            }
            thead { display: table-header-group; }
            th { 
                background-color: #003b73 !important; 
                color: #fff !important; 
                font-weight: 700 !important;
                text-align: center !important;
                font-size: 10pt;
            }
            
            /* Color Coding for Rows */
            .row-inflow { background-color: #f0fff4 !important; }
            .row-outflow { background-color: #fff5f5 !important; }
            .row-exchange { background-color: #fffbeb !important; }

            .badge {
                border: 1px solid #000 !important;
                background: transparent !important;
                color: #000 !important;
                font-weight: 800 !important;
                font-size: 8pt !important;
                padding: 3px 6px !important;
            }
            
            @page { size: A4 portrait; margin: 15mm; }
            
            .container-fluid { padding: 0 !important; margin: 0 !important; width: 100% !important; }
            .summary-card { display: none !important; } /* Hide web cards in print */
        }
        
        /* Web Styling for row colors */
        .row-inflow { background-color: #f0fff4; }
        .row-outflow { background-color: #fff5f5; }
        .row-exchange { background-color: #fffbeb; }
    </style>
</head>
<body dir="rtl">
    <div class="no-print">
        <?php include '../includes/navbar.php'; ?>
        <?php include '../includes/sidebar.php'; ?>
    </div>

    <!-- Print Header (Integrated & Professional) -->
    <div class="print-header">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>کارگەی کۆنکرێتی دانا</h2>
                <h4>ڕاپۆرتی دارایی و جوڵەی دراوەکان (Account Statement)</h4>
            </div>
            <div style="text-align: left;">
                <img src="../assets/images/logo.png" style="height: 80px; filter: grayscale(1);">
            </div>
        </div>
        
        <div class="header-info-grid">
            <div class="header-info-item">
                <strong>ناوی قاسە:</strong> <span><?= htmlspecialchars($user_name) ?></span>
            </div>
            <div class="header-info-item">
                <strong>ماوەی ڕاپۆرت:</strong> <span><?= $from_date ?> ➟ <?= $to_date ?></span>
            </div>
            <div class="header-info-item">
                <strong>کاتی دەرچوون:</strong> <span><?= date('Y-m-d H:i') ?></span>
            </div>
            <div class="header-info-item">
                <strong>جۆری دراو:</strong> <span><?= $currency == 'ALL' ? 'USD & IQD' : $currency ?></span>
            </div>
        </div>
    </div>

    <!-- Print Summary Section (Only visible on print) -->
    <div class="container-fluid print-summary-section d-none">
        <table class="print-summary-table">
            <tr>
                <td>
                    <span class="summary-label">Opening Balance</span>
                    <div class="summary-value">
                        <?php if ($currency === 'ALL' || $currency === 'USD'): ?><span class="val-usd"><?= number_format($opening_balances['USD'], 2) ?> USD</span><?php endif; ?>
                        <?php if ($currency === 'ALL' || $currency === 'IQD'): ?><span class="val-iqd"><?= number_format($opening_balances['IQD'], 0) ?> IQD</span><?php endif; ?>
                    </div>
                </td>
                <td>
                    <span class="summary-label">Total Inflow</span>
                    <div class="summary-value">
                        <?php if ($currency === 'ALL' || $currency === 'USD'): ?><span class="val-usd text-success">+<?= number_format($total_inflow['USD'], 2) ?> USD</span><?php endif; ?>
                        <?php if ($currency === 'ALL' || $currency === 'IQD'): ?><span class="val-iqd text-success">+<?= number_format($total_inflow['IQD'], 0) ?> IQD</span><?php endif; ?>
                    </div>
                </td>
                <td>
                    <span class="summary-label">Total Outflow</span>
                    <div class="summary-value">
                        <?php if ($currency === 'ALL' || $currency === 'USD'): ?><span class="val-usd text-danger">-<?= number_format($total_outflow['USD'], 2) ?> USD</span><?php endif; ?>
                        <?php if ($currency === 'ALL' || $currency === 'IQD'): ?><span class="val-iqd text-danger">-<?= number_format($total_outflow['IQD'], 0) ?> IQD</span><?php endif; ?>
                    </div>
                </td>
                <td style="background: #f0f7ff;">
                    <span class="summary-label">Final Net Worth</span>
                    <div class="summary-value">
                        <?php if ($currency === 'ALL' || $currency === 'USD'): ?><span class="val-usd text-primary"><?= number_format($final_balances['USD'], 2) ?> USD</span><?php endif; ?>
                        <?php if ($currency === 'ALL' || $currency === 'IQD'): ?><span class="val-iqd text-primary"><?= number_format($final_balances['IQD'], 0) ?> IQD</span><?php endif; ?>
                    </div>
                </td>
            </tr>
        </table>
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
                    <?php 
                    $counter = 1;
                    foreach ($transactions as $row): 
                        // داواکردنی ڕەنینگ باڵانس لەو ئەرەییەی کە بۆی دروستکراوە
                        $current_rb = $running_balances[$row['id']] ?? 0;
                        $is_inflow = $row['amount'] > 0;
                        $is_exchange = ($row['trans_type'] === 'EXCHANGE');
                        $decimals = $row['currency_code'] === 'IQD' ? 0 : 2;
                        
                        // Dynamic Classes for Row Painting
                        $row_class = "";
                        if($is_exchange) $row_class = "row-exchange";
                        elseif($is_inflow) $row_class = "row-inflow";
                        else $row_class = "row-outflow";
                    ?>
                        <tr class="<?= $row_class ?>">
                            <td><?= $counter++ ?></td>
                            <td><span style="direction: ltr; display: inline-block; font-size: 0.9em;"><?= $row['created_at'] ?></span></td>
                            <td>
                                <?php if($is_exchange): ?>
                                    <span class="badge bg-warning text-dark no-print">ئاڵوگۆڕ</span>
                                    <span class="d-none d-print-block fw-bold text-dark border border-dark p-1 rounded-1">Exchange</span>
                                <?php elseif($is_inflow): ?>
                                    <span class="badge bg-success no-print">هاتن</span>
                                    <span class="d-none d-print-block fw-bold text-success border border-success p-1 rounded-1">Credit</span>
                                <?php else: ?>
                                    <span class="badge bg-danger no-print">دەرچوون</span>
                                    <span class="d-none d-print-block fw-bold text-danger border border-danger p-1 rounded-1">Debit</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-white text-dark border shadow-sm"><?= $row['currency_code'] ?></span></td>
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
                            <td dir="ltr" class="fw-bold text-dark fs-6" style="background: rgba(0,0,0,0.02);">
                                <?= number_format($current_rb, $decimals) ?> <span style="font-size: 0.8em; opacity: 0.6;"><?= $row['currency_code'] ?></span>
                            </td>
                            
                            <td class="text-end small" style="font-size: 0.85em;"><?= htmlspecialchars($row['description'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="9" class="text-muted py-4">هیچ مامەڵەیەک نەدۆزرایەوە</td></tr>
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
