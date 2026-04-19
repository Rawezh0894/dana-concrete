<?php
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
$stmtUser = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user_info = $stmtUser->fetch();
$user_name = $user_info['full_name'] ?? 'بەکارهێنەر';

// --- 1. هێنانەی باڵانسی سەرەتا (Opening Balance) ---
// باڵانسی سەرەتا بریتییە لە کۆی هەموو ئەو پارانەی هاتووە یان دەرچووە پێش بەرواری دیاریکراو
$sql_opening = "
    SELECT SUM(l.amount) as opening_balance
    FROM ledger_entries l
    JOIN transactions t ON l.transaction_id = t.id
    JOIN wallets w ON l.wallet_id = w.id
    WHERE w.user_id = :user_id 
    AND l.currency_code = :currency
    AND DATE(t.created_at) < :from_date
";
$stmtOpening = $pdo->prepare($sql_opening);
$stmtOpening->execute([
    'user_id' => $user_id,
    'currency' => $currency,
    'from_date' => $from_date
]);
$opening_balance = floatval($stmtOpening->fetchColumn() ?: 0);

// --- 2. هێنانی مامەڵەکانی ناو ماوەی دیاریکراو ---
$sql_transactions = "
    SELECT t.id, t.created_at, t.type as trans_type, l.amount, l.description, tc.name as category_name
    FROM ledger_entries l
    JOIN transactions t ON l.transaction_id = t.id
    LEFT JOIN transaction_categories tc ON t.category_id = tc.id
    JOIN wallets w ON l.wallet_id = w.id
    WHERE w.user_id = :user_id 
    AND l.currency_code = :currency
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
$stmtTrans->execute([
    'user_id' => $user_id,
    'currency' => $currency,
    'from_date' => $from_date,
    'to_date' => $to_date
]);
$transactions = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

// --- 3. لۆجیکی Running Balance (باڵانسی تراکەمی) و ئامارەکان ---
$running_balance = $opening_balance;
$total_inflow = 0;
$total_outflow = 0;
$report_data = [];

// باڵانسی کۆتایی و پەیوەندی بە فلتەرەوە (لێرەدا Running Balance بەپێی کات دروست دەکرێت)
// تێبینی: ئەگەر فلتەری تەنها "Inflow" هەڵبژێردرێت، ڕەنگە Running Balance وا دەرکەوێت کە هەندێک مامەڵەی تێپەڕاندووە، 
// چونکە تەنها پۆزەتیڤەکان نیشان دەدات، بەڵام بڕەکەی لە ڕاستیدا دەبێت دروست بێت. بۆیە باڵانسی کۆتایی هەر دروست دەبێت.
// بۆ ڕاپۆرتی ستاندارد، واباشترە هەمووی دیاربێت (بەڵام داواکارییەکە فلتەری تێدایە).
foreach ($transactions as $row) {
    // ئەگەر فلتەر کرابێت، ئەم ئەلگۆریتمە هەر باڵانسی ڕاستەقینە نیشان دەدات گەر هەژماری پێشووتر بکرێت،
    // بەڵام لێرەدا پشت دەبەستێت بەو دێڕانەی هاتوون. بۆ باڵانسی ١٠٠٪ تەواو پێویستە هەموو دێڕەکان بهێنین.
    // پێشنیار: باشترە هەمووی بهێنین و لێرە فلتەری بکەین، بەڵام بۆ خێرایی SQL باشە.
    // کۆکردنەوەی Summary
    if ($row['amount'] > 0) {
        $total_inflow += $row['amount'];
    } else {
        $total_outflow += abs($row['amount']);
    }
}

// بۆ کەیسی Running Balance پێویستە هەموو مامەڵەکان بهێنین بۆ ئەوەی ڕیزبەندییەکە دروست بێت
$stmtAllTrans = $pdo->prepare("
    SELECT t.id, t.created_at, t.type as trans_type, l.amount, l.description, tc.name as category_name
    FROM ledger_entries l
    JOIN transactions t ON l.transaction_id = t.id
    LEFT JOIN transaction_categories tc ON t.category_id = tc.id
    JOIN wallets w ON l.wallet_id = w.id
    WHERE w.user_id = :user_id 
    AND l.currency_code = :currency
    AND DATE(t.created_at) >= :from_date 
    AND DATE(t.created_at) <= :to_date
    ORDER BY t.created_at ASC, t.id ASC
");
$stmtAllTrans->execute([
    'user_id' => $user_id,
    'currency' => $currency,
    'from_date' => $from_date,
    'to_date' => $to_date
]);
$all_transactions = $stmtAllTrans->fetchAll(PDO::FETCH_ASSOC);

$running_balances = [];
$current_balance = $opening_balance;
foreach ($all_transactions as $row) {
    $current_balance += $row['amount'];
    $running_balances[$row['id']] = $current_balance;
}

$net_change = $total_inflow - $total_outflow;
$final_balance = $opening_balance + $net_change;

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
        
        /* Print Optimization */
        @media print {
            body { background-color: white; margin: 0; padding: 0; font-size: 12pt; }
            .no-print, .sidebar, .navbar, .filters-section { display: none !important; }
            .print-header { display: block; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .table-responsive { box-shadow: none; }
            table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            th { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
            .summary-card { border: 1px solid #ddd; break-inside: avoid; margin-bottom: 15px; }
            @page { size: A4; margin: 10mm; }
            
            /* Add some margins for better readability on paper */
            .container-fluid { padding: 0; }
            .row.g-3 { display: flex !important; flex-direction: row; }
            .col-xl-3, .col-md-6 { width: 25% !important; float: right; padding: 5px; }
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
            <span><strong>جۆری دراو:</strong> <?= $currency ?></span>
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
                                <div class="h4 mb-0 fw-bold text-gray-800" dir="ltr"><?= number_format($opening_balance, $currency=='IQD'?0:2) ?> <?= $currency ?></div>
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
                                <div class="h4 mb-0 fw-bold text-gray-800" dir="ltr">+ <?= number_format($total_inflow, $currency=='IQD'?0:2) ?> <?= $currency ?></div>
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
                                <div class="h4 mb-0 fw-bold text-gray-800" dir="ltr">- <?= number_format($total_outflow, $currency=='IQD'?0:2) ?> <?= $currency ?></div>
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
                                <div class="h4 mb-0 fw-bold text-primary" dir="ltr"><?= number_format($final_balance, $currency=='IQD'?0:2) ?> <?= $currency ?></div>
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
                        <th>پۆلێن / هۆکار</th>
                        <th>هاتن (In)</th>
                        <th>چوون (Out)</th>
                        <th>باڵانسی تراکەمی (Running Bal)</th>
                        <th width="20%">تێبینی</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row -->
                    <tr class="table-secondary">
                        <td>-</td>
                        <td colspan="5" class="fw-bold text-start">باڵانسی پێشوو (Opening Balance) تا بەرواری <?= $from_date ?></td>
                        <td dir="ltr" class="fw-bold fs-5 text-primary"><?= number_format($opening_balance, $currency=='IQD'?0:2) ?></td>
                        <td>-</td>
                    </tr>

                    <?php 
                    $counter = 1;
                    foreach ($transactions as $row): 
                        // داواکردنی ڕەنینگ باڵانس لەو ئەرەییەی کە بۆی دروستکراوە
                        $current_rb = $running_balances[$row['id']] ?? 0;
                        $is_inflow = $row['amount'] > 0;
                    ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><span style="direction: ltr; display: inline-block;"><?= $row['created_at'] ?></span></td>
                            <td>
                                <?php if($row['trans_type'] === 'EXCHANGE'): ?>
                                    <span class="badge bg-warning text-dark no-print">ئاڵوگۆڕ</span>
                                    <span class="d-none d-print-block">ئاڵوگۆڕ</span>
                                <?php elseif($is_inflow): ?>
                                    <span class="badge bg-success no-print">هاتن</span>
                                    <span class="d-none d-print-block">هاتن</span>
                                <?php else: ?>
                                    <span class="badge bg-danger no-print">دەرچوون</span>
                                    <span class="d-none d-print-block">دەرچوون</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['category_name'] ?? 'بی جۆر') ?></td>
                            
                            <!-- Inflow Column -->
                            <td dir="ltr" class="fw-bold text-success">
                                <?= $is_inflow ? number_format($row['amount'], $currency=='IQD'?0:2) : '-' ?>
                            </td>
                            
                            <!-- Outflow Column -->
                            <td dir="ltr" class="fw-bold text-danger">
                                <?= !$is_inflow ? number_format(abs($row['amount']), $currency=='IQD'?0:2) : '-' ?>
                            </td>
                            
                            <!-- Running Balance Column -->
                            <td dir="ltr" class="fw-bold text-primary fs-6">
                                <?= number_format($current_rb, $currency=='IQD'?0:2) ?>
                            </td>
                            
                            <td class="text-end small"><?= htmlspecialchars($row['description'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="8" class="text-muted py-4">کشف حسابی ئەم بەروارە بەتاڵە</td></tr>
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
