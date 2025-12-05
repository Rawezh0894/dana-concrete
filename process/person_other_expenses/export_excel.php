<?php
session_start();

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once '../person_other_expenses_profile/debt_helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

if (!hasPermission('view_person_other_expenses')) {
    http_response_code(403);
    echo 'Permission denied';
    exit;
}

// Generate unique code for this export file
$exportUniqueCode = 'EXP-' . date('YmdHis') . '-' . uniqid();

$filename = 'persons_debt_' . date('Y-m-d_H-i-s') . '.xls';

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
echo "<th>کۆد</th>";
echo "<th>ناوی کەس</th>";
echo "<th>قەرزی سەرەتایی (USD)</th>";
echo "<th>قەرزی سەرەتایی (IQD)</th>";
echo "<th>ماوەی خەرجیەکان (USD)</th>";
echo "<th>ماوەی خەرجیەکان (IQD)</th>";
echo "<th>ماوەی کڕینەکان (USD)</th>";
echo "<th>ماوەی کڕینەکان (IQD)</th>";
echo "<th>کۆی قەرز (USD)</th>";
echo "<th>کۆی قەرز (IQD)</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

try {
    // Get only persons who have debt
    $query = "
        SELECT DISTINCT
            p.id,
            p.name,
            p.opening_debt_usd,
            p.opening_debt_iqd
        FROM other_expense_persons p
        WHERE
            (p.opening_debt_usd > 0 OR p.opening_debt_iqd > 0)
            OR EXISTS (
                SELECT 1 FROM other_expenses oe
                WHERE oe.person_id = p.id
                AND oe.payment_type = 'قەرز'
                AND (oe.remaining_usd > 0 OR oe.remaining_iqd > 0)
            )
            OR EXISTS (
                SELECT 1 FROM purchase_materials pm
                WHERE pm.person_id = p.id
                AND pm.payment_type = 'قەرز'
                AND (pm.remaining_amount_usd > 0 OR pm.remaining_amount_iqd > 0)
            )
        ORDER BY p.name ASC
    ";

    $stmt = $pdo->query($query);
    $hasRows = false;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $personId = (int)$row['id'];

        // Get debt snapshot using the helper function
        try {
            $snapshot = getPersonDebtSnapshot($pdo, $personId);
        } catch (Exception $e) {
            error_log('Failed to get debt snapshot for person ' . $personId . ': ' . $e->getMessage());
            continue;
        }

        $totalDebtUsd = $snapshot['total_debt_usd'];
        $totalDebtIqd = $snapshot['total_debt_iqd'];

        // Only include persons with debt > 0
        if ($totalDebtUsd <= 0 && $totalDebtIqd <= 0) {
            continue;
        }

        $hasRows = true;

        // Generate unique code for this row (combination of export code + person ID + timestamp)
        $rowUniqueCode = $exportUniqueCode . '-P' . $personId;

        $safeName = htmlspecialchars($row['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $openingDebtUsd = number_format((float)$snapshot['opening_debt_usd'], 2, '.', '');
        $openingDebtIqd = number_format((float)$snapshot['opening_debt_iqd'], 2, '.', '');
        $expensesRemainingUsd = number_format((float)$snapshot['remaining_expenses_usd'], 2, '.', '');
        $expensesRemainingIqd = number_format((float)$snapshot['remaining_expenses_iqd'], 2, '.', '');
        $purchasesRemainingUsd = number_format((float)$snapshot['remaining_purchases_usd'], 2, '.', '');
        $purchasesRemainingIqd = number_format((float)$snapshot['remaining_purchases_iqd'], 2, '.', '');
        $totalDebtUsdFormatted = number_format($totalDebtUsd, 2, '.', '');
        $totalDebtIqdFormatted = number_format($totalDebtIqd, 2, '.', '');

        echo "<tr>";
        echo "<td>{$rowUniqueCode}</td>";
        echo "<td>{$safeName}</td>";
        echo "<td>{$openingDebtUsd}</td>";
        echo "<td>{$openingDebtIqd}</td>";
        echo "<td>{$expensesRemainingUsd}</td>";
        echo "<td>{$expensesRemainingIqd}</td>";
        echo "<td>{$purchasesRemainingUsd}</td>";
        echo "<td>{$purchasesRemainingIqd}</td>";
        echo "<td>{$totalDebtUsdFormatted}</td>";
        echo "<td>{$totalDebtIqdFormatted}</td>";
        echo "</tr>";
    }

    if (!$hasRows) {
        echo "<tr><td colspan=\"10\">هیچ کەسێکی قەرزدار نەدۆزرایەوە</td></tr>";
    } else {
        // Add summary row
        echo "<tr style=\"background-color: #f0f0f0; font-weight: bold;\">";
        echo "<td colspan=\"2\">کۆی گشتی</td>";

        // Calculate totals - re-execute query to get all persons again
        $stmt2 = $pdo->query($query);
        $totalOpeningUsd = 0;
        $totalOpeningIqd = 0;
        $totalExpensesUsd = 0;
        $totalExpensesIqd = 0;
        $totalPurchasesUsd = 0;
        $totalPurchasesIqd = 0;
        $grandTotalUsd = 0;
        $grandTotalIqd = 0;

        while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $personId = (int)$row2['id'];
            try {
                $snapshot = getPersonDebtSnapshot($pdo, $personId);
                $totalDebtUsd = $snapshot['total_debt_usd'];
                $totalDebtIqd = $snapshot['total_debt_iqd'];
                // Only include persons with debt > 0 in totals
                if ($totalDebtUsd > 0 || $totalDebtIqd > 0) {
                    $totalOpeningUsd += $snapshot['opening_debt_usd'];
                    $totalOpeningIqd += $snapshot['opening_debt_iqd'];
                    $totalExpensesUsd += $snapshot['remaining_expenses_usd'];
                    $totalExpensesIqd += $snapshot['remaining_expenses_iqd'];
                    $totalPurchasesUsd += $snapshot['remaining_purchases_usd'];
                    $totalPurchasesIqd += $snapshot['remaining_purchases_iqd'];
                    $grandTotalUsd += $snapshot['total_debt_usd'];
                    $grandTotalIqd += $snapshot['total_debt_iqd'];
                }
            } catch (Exception $e) {
                continue;
            }
        }

        echo "<td>" . number_format($totalOpeningUsd, 2, '.', '') . "</td>";
        echo "<td>" . number_format($totalOpeningIqd, 2, '.', '') . "</td>";
        echo "<td>" . number_format($totalExpensesUsd, 2, '.', '') . "</td>";
        echo "<td>" . number_format($totalExpensesIqd, 2, '.', '') . "</td>";
        echo "<td>" . number_format($totalPurchasesUsd, 2, '.', '') . "</td>";
        echo "<td>" . number_format($totalPurchasesIqd, 2, '.', '') . "</td>";
        echo "<td>" . number_format($grandTotalUsd, 2, '.', '') . "</td>";
        echo "<td>" . number_format($grandTotalIqd, 2, '.', '') . "</td>";
        echo "</tr>";

        // Add export info row
        echo "<tr style=\"background-color: #e8f4f8;\">";
        echo "<td colspan=\"10\" style=\"text-align: center; font-size: 10px; color: #666;\">";
        echo "کۆدی Export: {$exportUniqueCode} | بەروار: " . date('Y-m-d H:i:s');
        echo "</td>";
        echo "</tr>";
    }
} catch (Exception $e) {
    error_log('Person Other Expenses Excel export failed: ' . $e->getMessage());
    echo "<tr><td colspan=\"10\">هەڵە ڕوویدا لە دروستکردنی فایلەکە: " . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</td></tr>";
}

echo "</tbody>";
echo "</table>";
exit;

