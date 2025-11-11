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

$filename = 'customers_debt_' . date('Y-m-d_H-i-s') . '.xls';

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
echo "<th>ناو</th>";
echo "<th>بڕی قەرز (USD)</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

try {
    $usdRate = 139250;

    try {
        $rateStmt = $pdo->query("SELECT value FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1");
        $rateRow = $rateStmt->fetch(PDO::FETCH_ASSOC);
        if ($rateRow && is_numeric($rateRow['value'])) {
            $usdRate = (float)$rateRow['value'];
        }
    } catch (Exception $e) {
        error_log('Failed to read usd_iqd_rate setting: ' . $e->getMessage());
    }

    $query = "
        SELECT 
            c.id,
            c.name,
            IFNULL(c.opening_debt_usd, 0) AS opening_debt_usd,
            IFNULL(c.opening_debt_iqd, 0) AS opening_debt_iqd,
            COALESCE(SUM(CASE WHEN s.payment_type = 'قەرز' THEN s.remaining_amount ELSE 0 END), 0) AS remaining_amount
        FROM customers c
        LEFT JOIN sales s 
            ON c.id = s.customer_id 
            AND s.payment_type = 'قەرز' 
            AND s.remaining_amount > 0
        GROUP BY c.id, c.name, c.opening_debt_usd, c.opening_debt_iqd
        ORDER BY c.name ASC
    ";

    $stmt = $pdo->query($query);

    $hasRows = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $openingDebtUsd = (float)($row['opening_debt_usd'] ?? 0);
        $openingDebtIqd = (float)($row['opening_debt_iqd'] ?? 0);
        $remainingAmount = (float)($row['remaining_amount'] ?? 0);

        $iqdToUsdRate = ($usdRate > 0) ? ($usdRate / 100) : 0;
        $openingDebtIqdUsd = ($iqdToUsdRate > 0) ? ($openingDebtIqd / $iqdToUsdRate) : 0;

        $totalDebtUsd = $openingDebtUsd + $remainingAmount + $openingDebtIqdUsd;

        if ($totalDebtUsd <= 0) {
            continue;
        }

        $hasRows = true;

        $safeName = htmlspecialchars($row['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $formattedDebt = number_format($totalDebtUsd, 2, '.', '');

        echo "<tr>";
        echo "<td>{$safeName}</td>";
        echo "<td>{$formattedDebt}</td>";
        echo "</tr>";
    }

    if (!$hasRows) {
        echo "<tr><td colspan=\"2\">هیچ داتایەک نەدۆزرایەوە</td></tr>";
    }
} catch (Exception $e) {
    error_log('Customer Excel export failed: ' . $e->getMessage());
    echo "<tr><td colspan=\"2\">هەڵە ڕوویدا لە دروستکردنی فایلەکە</td></tr>";
}

echo "</tbody>";
echo "</table>";
exit;

