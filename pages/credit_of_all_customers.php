<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_customer')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// گەڕانەوەی هەموو کڕیارە قەرزارەکان
$sql = "SELECT c.id, c.name, c.mobile1, c.mobile2, c.debt_usd, c.debt_iqd,
    (
        SELECT IFNULL(SUM(quantity),0) FROM sales s WHERE s.customer_id = c.id AND s.payment_type = 'قەرز' AND s.remaining_amount > 0
    ) as total_credit_meter
FROM customers c
WHERE c.debt_usd > 0 OR c.debt_iqd > 0
ORDER BY c.name ASC";
$stmt = $pdo->query($sql);
$customers = $stmt->fetchAll();

// گەڕانەوەی هەموو مامەڵە قەرزەکان (پارەی ماوە) بۆ هەموو کڕیارە قەرزارەکان
$sales_sql = "SELECT s.*, c.name as customer_name, c.mobile1 FROM sales s JOIN customers c ON s.customer_id = c.id WHERE s.payment_type = 'قەرز' AND s.remaining_amount > 0 ORDER BY c.name ASC, s.order_date DESC";
$sales_stmt = $pdo->query($sales_sql);
$sales = $sales_stmt->fetchAll();

// گرووپکردنی مامەڵەکان بە IDی کڕیار
$sales_by_customer = [];
foreach ($sales as $sale) {
    $sales_by_customer[$sale['customer_id']][] = $sale;
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
    <title>پرینتی قەرزی کڕیارەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../assets/css/credit_of_all_customers.css" rel="stylesheet">
    <style>
        /* Remove all inline styles as they are now in the CSS file */
    </style>
</head>
<body dir="rtl">
<div class="a4-sheet">
    <div class="report-header">
        <h1>لیستی قەرزی هەموو کڕیاران</h1>
        <div class="subtitle">ڕاپۆرتی تەواوی مامەڵە قەرزەکان بە وردی</div>
        <div class="date">بەروار و کات: <?php echo date('Y-m-d H:i'); ?></div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="section-title">پرینتی قەرزی کڕیارەکان</h2>
        <button class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> پرینت</button>
    </div>
  
    <?php foreach ($customers as $c): ?>
        <?php
            $total_sales_amount = 0;
            if (!empty($sales_by_customer[$c['id']])) {
                foreach ($sales_by_customer[$c['id']] as $s) {
                    $total_sales_amount += floatval($s['total_price']);
                }
            }
        ?>
        <div class="customer-card print-break-inside-avoid">
            <div class="customer-info">
                <span class="text-primary">
                    <i class="fa fa-user-circle"></i>
                    <?= htmlspecialchars($c['name']) ?>
                </span>
                <span class="text-secondary">
                    <i class="fa fa-phone"></i>
                    <?= htmlspecialchars($c['mobile1']) ?>
                </span>
                <span>
                    <i class="fa fa-cube"></i>
                    مەتر سێجا: <?= number_format($c['total_credit_meter'], 2) ?> م٣
                </span>
                <span>
                    <i class="fa fa-calculator"></i>
                    کۆی گشتی: <?= number_format($total_sales_amount, 2) ?> $
                </span>
                <span>
                    <i class="fa fa-money-bill-wave"></i>
                    قەرز: <?= number_format($c['debt_usd'], 2) ?> $
                </span>
            </div>

            <?php if (!empty($sales_by_customer[$c['id']])): ?>
            <div class="table-container">
                <table class="credit-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>وەرگر</th>
                            <th>شوێن</th>
                            <th>مەتر</th>
                            <th>نرخ/مەتر</th>
                            <th>کۆی گشتی</th>
                            <th>دراو ($)</th>
                            <th>دراو (IQD)</th>
                            <th>ماوە</th>
                            <th>داشکاندن</th>
                            <th>ژ.فاکتور</th>
                            <th>ڕێکەوت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_by_customer[$c['id']] as $i => $s): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($s['recipient']) ?></td>
                            <td><?= htmlspecialchars($s['location']) ?></td>
                            <td><?= number_format($s['quantity'], 2) ?></td>
                            <td><?= number_format($s['price_per_unit'], 2) ?></td>
                            <td><?= number_format($s['total_price'], 2) ?></td>
                            <td><?= number_format($s['amount_paid_usd'], 2) ?></td>
                            <td><?= number_format($s['amount_paid_iq'], 0) ?></td>
                            <td><?= number_format($s['remaining_amount'], 2) ?></td>
                            <td><?= number_format($s['discount'], 2) ?></td>
                            <td><?= htmlspecialchars($s['invoice_number']) ?></td>
                            <td><?= htmlspecialchars($s['order_date']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="p-3 text-muted">هیچ مامەڵە قەرزێکی پارەی ماوە نییە.</div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="text-center mt-5 no-print">
        <button class="btn btn-secondary" onclick="window.history.back()">گەڕانەوە</button>
    </div>
</div>
</body>
</html>
