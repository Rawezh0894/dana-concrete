<?php
// Export Customer Debt Report to Excel
require_once 'config/db_conected.php';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="customer_debt_report_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Query to get customer debt data
$sql = "
SELECT
    c.id AS customer_id,
    c.name AS customer_name,
    c.mobile1,
    COALESCE(SUM(s.remaining_amount), 0) AS sales_remaining,
    c.opening_debt_usd,
    c.opening_debt_iqd,
    (c.opening_debt_usd + c.opening_debt_iqd) AS total_opening_debt,
    (COALESCE(SUM(s.remaining_amount), 0) + c.opening_debt_usd + c.opening_debt_iqd) AS total_debt,
    COUNT(CASE WHEN s.remaining_amount > 0 THEN 1 END) AS unpaid_sales_count,
    MAX(s.order_date) AS last_sale_date
FROM customers c
LEFT JOIN sales s ON c.id = s.customer_id
GROUP BY c.id, c.name, c.mobile1, c.opening_debt_usd, c.opening_debt_iqd
HAVING (COALESCE(SUM(s.remaining_amount), 0) + c.opening_debt_usd + c.opening_debt_iqd) > 0
ORDER BY total_debt DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_sales_remaining = 0;
$total_opening_debt_usd = 0;
$total_opening_debt_iqd = 0;
$total_combined_debt = 0;

foreach ($results as $row) {
    $total_sales_remaining += $row['sales_remaining'];
    $total_opening_debt_usd += $row['opening_debt_usd'];
    $total_opening_debt_iqd += $row['opening_debt_iqd'];
    $total_combined_debt += $row['total_debt'];
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Customer Debt Report</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .total-row { background-color: #e6f3ff; font-weight: bold; }
        .header-row { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h2>ڕاپۆرتی قەرزی کڕیارەکان</h2>
    <p>بەرواری بەرهەمهێنان: <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <table>
        <tr class="header-row">
            <th>ناسنامەی کڕیار</th>
            <th>ناوی کڕیار</th>
            <th>ژمارەی مۆبایل</th>
            <th>پارەی ماوەی فرۆشتنەکان</th>
            <th>قەرزی سەرەتایی (دۆلار)</th>
            <th>قەرزی سەرەتایی (دینار)</th>
            <th>کۆی قەرزی سەرەتایی</th>
            <th>کۆی گشتی قەرز</th>
            <th>ژمارەی فرۆشتنە نەدراوەکان</th>
            <th>دوایین فرۆشتن</th>
        </tr>
        
        <?php foreach ($results as $row): ?>
        <tr>
            <td><?php echo $row['customer_id']; ?></td>
            <td><?php echo $row['customer_name']; ?></td>
            <td><?php echo $row['mobile1']; ?></td>
            <td><?php echo number_format($row['sales_remaining'], 2); ?></td>
            <td><?php echo number_format($row['opening_debt_usd'], 2); ?></td>
            <td><?php echo number_format($row['opening_debt_iqd'], 2); ?></td>
            <td><?php echo number_format($row['total_opening_debt'], 2); ?></td>
            <td><?php echo number_format($row['total_debt'], 2); ?></td>
            <td><?php echo $row['unpaid_sales_count']; ?></td>
            <td><?php echo $row['last_sale_date']; ?></td>
        </tr>
        <?php endforeach; ?>
        
        <tr class="total-row">
            <td colspan="3"><strong>کۆی گشتی</strong></td>
            <td><strong><?php echo number_format($total_sales_remaining, 2); ?></strong></td>
            <td><strong><?php echo number_format($total_opening_debt_usd, 2); ?></strong></td>
            <td><strong><?php echo number_format($total_opening_debt_iqd, 2); ?></strong></td>
            <td><strong><?php echo number_format($total_opening_debt_usd + $total_opening_debt_iqd, 2); ?></strong></td>
            <td><strong><?php echo number_format($total_combined_debt, 2); ?></strong></td>
            <td colspan="2"></td>
        </tr>
    </table>
    
    <br>
    <h3>کورتەی ڕاپۆرت:</h3>
    <ul>
        <li>کۆی پارەی ماوەی فرۆشتنەکان: <?php echo number_format($total_sales_remaining, 2); ?></li>
        <li>کۆی قەرزی سەرەتایی (دۆلار): <?php echo number_format($total_opening_debt_usd, 2); ?></li>
        <li>کۆی قەرزی سەرەتایی (دینار): <?php echo number_format($total_opening_debt_iqd, 2); ?></li>
        <li>کۆی گشتی قەرز: <?php echo number_format($total_combined_debt, 2); ?></li>
        <li>ژمارەی کڕیارەکان: <?php echo count($results); ?></li>
    </ul>
</body>
</html> 