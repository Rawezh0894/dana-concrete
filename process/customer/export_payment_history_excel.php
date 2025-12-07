<?php
session_start();

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

if (!hasPermission('view_customer')) {
    http_response_code(403);
    echo 'Permission denied';
    exit;
}

$filename = 'customer_debt_payments_history_' . date('Y-m-d_H-i-s') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
echo "<meta charset=\"UTF-8\">";
echo "<table border=\"1\">";
echo "<thead>";
echo "<tr>";
echo "<th>#</th>";
echo "<th>ناوی کڕیار</th>";
echo "<th>بەروار</th>";
echo "<th>نرخی دۆلار</th>";
echo "<th>بڕی داوە (USD)</th>";
echo "<th>بڕی داوە (IQD)</th>";
echo "<th>داشکاندن (USD)</th>";
echo "<th>لە قەرزی سەرەتایی (USD)</th>";
echo "<th>لە فرۆشتن (USD)</th>";
echo "<th>جۆری پارەدان</th>";
echo "<th>تێبینی</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

try {
    $query = "
        SELECT 
            cdp.id,
            c.name AS customer_name,
            cdp.date,
            cdp.dolar_rate,
            cdp.paid_usd,
            cdp.paid_iqd,
            cdp.discount,
            cdp.from_opening_debt_usd,
            cdp.from_sales_usd,
            cdp.payment_type,
            cdp.note
        FROM customer_debt_payments cdp
        INNER JOIN customers c ON cdp.customer_id = c.id
        ORDER BY cdp.date DESC, cdp.id DESC
    ";

    $stmt = $pdo->query($query);

    $hasRows = false;
    $rowNum = 1;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hasRows = true;

        $safeCustomerName = htmlspecialchars($row['customer_name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $date = htmlspecialchars($row['date'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $dolarRate = number_format((float)($row['dolar_rate'] ?? 0), 2, '.', '');
        $paidUsd = number_format((float)($row['paid_usd'] ?? 0), 2, '.', '');
        $paidIqd = number_format((float)($row['paid_iqd'] ?? 0), 2, '.', '');
        $discount = number_format((float)($row['discount'] ?? 0), 2, '.', '');
        $fromOpeningDebtUsd = number_format((float)($row['from_opening_debt_usd'] ?? 0), 2, '.', '');
        $fromSalesUsd = number_format((float)($row['from_sales_usd'] ?? 0), 2, '.', '');
        
        $paymentType = '';
        switch ($row['payment_type'] ?? '') {
            case 'fifo':
                $paymentType = 'FIFO (یەکەم دەرچوو - یەکەم داهات)';
                break;
            case 'opening_debt_only':
                $paymentType = 'تەنها قەرزی سەرەتایی';
                break;
            case 'specific_sales':
                $paymentType = 'فرۆشتنێکی دیاریکراو';
                break;
            default:
                $paymentType = htmlspecialchars($row['payment_type'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        
        $note = htmlspecialchars($row['note'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        echo "<tr>";
        echo "<td>{$rowNum}</td>";
        echo "<td>{$safeCustomerName}</td>";
        echo "<td>{$date}</td>";
        echo "<td>{$dolarRate}</td>";
        echo "<td>{$paidUsd}</td>";
        echo "<td>{$paidIqd}</td>";
        echo "<td>{$discount}</td>";
        echo "<td>{$fromOpeningDebtUsd}</td>";
        echo "<td>{$fromSalesUsd}</td>";
        echo "<td>{$paymentType}</td>";
        echo "<td>{$note}</td>";
        echo "</tr>";
        
        $rowNum++;
    }

    if (!$hasRows) {
        echo "<tr><td colspan=\"11\">هیچ داتایەک نەدۆزرایەوە</td></tr>";
    }
} catch (Exception $e) {
    error_log('Customer payment history Excel export failed: ' . $e->getMessage());
    echo "<tr><td colspan=\"11\">هەڵە ڕوویدا لە دروستکردنی فایلەکە</td></tr>";
}

echo "</tbody>";
echo "</table>";
exit;

