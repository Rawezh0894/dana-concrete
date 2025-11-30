<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if (!hasPermission('view_other_expenses')) {
    echo 'ڕێگەت پێنەدراوە!';
    exit;
}

// Get filter parameters
$dateFrom = $_POST['dateFrom'] ?? '';
$dateTo = $_POST['dateTo'] ?? '';
$monthFilter = $_POST['monthFilter'] ?? '';
$carFilter = $_POST['carFilter'] ?? '';
$employeeFilter = $_POST['employeeFilter'] ?? '';
$personFilter = $_POST['personFilter'] ?? '';
$paymentTypeFilter = $_POST['paymentTypeFilter'] ?? '';
$expenseTypes = $_POST['expenseTypes'] ?? [];
$export_type = $_POST['export_type'] ?? 'detailed';

// Build WHERE clause
$where = [];
$params = [];

if ($dateFrom) {
    $where[] = "oe.date >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "oe.date <= ?";
    $params[] = $dateTo;
}

if ($monthFilter) {
    $where[] = "DATE_FORMAT(oe.date, '%Y-%m') = ?";
    $params[] = $monthFilter;
}

if ($carFilter) {
    $where[] = "oe.car_id = ?";
    $params[] = $carFilter;
}

if ($employeeFilter) {
    $where[] = "oe.employee_id = ?";
    $params[] = $employeeFilter;
}

if ($personFilter) {
    $where[] = "oe.person_id = ?";
    $params[] = $personFilter;
}

if ($paymentTypeFilter) {
    $where[] = "oe.payment_type = ?";
    $params[] = $paymentTypeFilter;
}

if (!empty($expenseTypes)) {
    $placeholders = str_repeat('?,', count($expenseTypes) - 1) . '?';
    $where[] = "oe.expense_type IN ($placeholders)";
    $params = array_merge($params, $expenseTypes);
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Get data
$sql = "SELECT 
    oe.purpose,
    p.name AS person_name,
    e.name AS employee_name,
    c.name AS car_name,
    oe.gas_liters,
    oe.expense_type,
    m.name AS material_name,
    oe.material_quantity,
    oe.material_purchase_price_iqd,
    oe.material_purchase_price_usd,
    oe.material_total_cost,
    oe.gas_purchase_price_input,
    oe.gas_total_cost,
    oe.payment_type,
    oe.currency_type,
    oe.invoice_number,
    oe.amount_iqd,
    oe.amount_usd,
    oe.exchange_rate,
    oe.paid_iqd,
    oe.paid_usd,
    oe.remaining_iqd,
    oe.remaining_usd,
    oe.date
FROM other_expenses oe
LEFT JOIN other_expense_persons p ON oe.person_id = p.id
LEFT JOIN employees e ON oe.employee_id = e.id
LEFT JOIN cars c ON oe.car_id = c.id
LEFT JOIN materials m ON oe.material_id = m.id
$where_sql
ORDER BY oe.date ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for Excel download with proper UTF-8 encoding
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Transfer-Encoding: binary');
    
    if ($export_type === 'summary') {
        // Export summary data
        header('Content-Disposition: attachment; filename*=UTF-8\'\'کورتەی_خەرجی_تر_' . date('Y-m-d') . '.xls');
        
        // Get summary data
        $summary_sql = "SELECT 
            SUM(CASE WHEN oe.expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN oe.material_total_cost ELSE 0 END) as total_car_material_cost,
            SUM(CASE WHEN oe.expense_type = 'بەکارهێنانی گاز' THEN oe.gas_total_cost ELSE 0 END) as total_car_gas_cost,
            SUM(CASE WHEN oe.expense_type NOT IN ('بەکارهێنانی کاڵای کۆگا', 'بەکارهێنانی گاز') THEN COALESCE(oe.amount_usd, 0) ELSE 0 END) as total_other_expenses,
            COUNT(*) as total_expenses
        FROM other_expenses oe
        LEFT JOIN other_expense_persons p ON oe.person_id = p.id
        LEFT JOIN employees e ON oe.employee_id = e.id
        LEFT JOIN cars c ON oe.car_id = c.id
        LEFT JOIN materials m ON oe.material_id = m.id
        $where_sql";
        
        $summary_stmt = $pdo->prepare($summary_sql);
        $summary_stmt->execute($params);
        $summary_data = $summary_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Start Excel content for summary with UTF-8 BOM
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo '<!DOCTYPE html>';
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }';
        echo 'th, td { border: 1px solid #000; padding: 8px; text-align: center; }';
        echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
        echo '.number { text-align: right; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<table border="1">';
        
        // Summary header
        echo '<tr><th colspan="2" style="background-color: #2196F3; color: white; font-size: 16px;">کورتەی خەرجی تر</th></tr>';
        echo '<tr><th>بەروار</th><th>' . date('Y-m-d') . '</th></tr>';
        echo '<tr><th>خەرجی سەیارەکان (کاڵا)</th><td class="number">' . number_format($summary_data['total_car_material_cost'] ?? 0, 2) . '</td></tr>';
        echo '<tr><th>خەرجی سەیارەکان (گاز)</th><td class="number">' . number_format($summary_data['total_car_gas_cost'] ?? 0, 2) . '</td></tr>';
        echo '<tr><th>خەرجی تر</th><td class="number">' . number_format($summary_data['total_other_expenses'] ?? 0, 2) . '</td></tr>';
        echo '<tr><th>کۆی گشتی</th><td class="number">' . number_format(($summary_data['total_car_material_cost'] ?? 0) + ($summary_data['total_car_gas_cost'] ?? 0) + ($summary_data['total_other_expenses'] ?? 0), 2) . '</td></tr>';
        echo '<tr><th>کۆی خەرجی</th><td class="number">' . number_format($summary_data['total_expenses'] ?? 0, 0) . '</td></tr>';
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
        
    } else {
        // Export detailed data
        header('Content-Disposition: attachment; filename*=UTF-8\'\'خەرجی_تر_' . date('Y-m-d') . '.xls');
        
        // Start Excel content for detailed export with UTF-8 BOM
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo '<!DOCTYPE html>';
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }';
        echo 'th, td { border: 1px solid #000; padding: 8px; text-align: center; }';
        echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
        echo '.number { text-align: right; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<table border="1">';
        
        // Header row
        echo '<tr>';
        echo '<th>#</th>';
        echo '<th>مەبەست</th>';
        echo '<th>کەس</th>';
        echo '<th>کارمەند</th>';
        echo '<th>سەیارە</th>';
        echo '<th>بڕی گاز (لیتر)</th>';
        echo '<th>جۆری خەرجی</th>';
        echo '<th>کاڵا لە کۆگا</th>';
        echo '<th>بڕی عەدەدی کاڵا</th>';
        echo '<th>نرخی کڕینی کاڵا بە دینار</th>';
        echo '<th>نرخی کڕینی کاڵا بە دۆلار</th>';
        echo '<th>کۆی نرخی کاڵای بەکارهاتوو</th>';
        echo '<th>ئینپوتی نرخی کڕینی گاز</th>';
        echo '<th>کۆی نرخی گازی بەکارهاتوو</th>';
        echo '<th>جۆری مامەڵە</th>';
        echo '<th>جۆری پارە</th>';
        echo '<th>ژمارەی وەسڵ</th>';
        echo '<th>بڕی دینار</th>';
        echo '<th>بڕی دۆلار</th>';
        echo '<th>پارەی دراو دینار</th>';
        echo '<th>پارەی دراو دۆلار</th>';
        echo '<th>نرخی 100 دۆلار</th>';
        echo '<th>ماوە دینار</th>';
        echo '<th>ماوە دۆلار</th>';
        echo '<th>بەروار</th>';
        echo '</tr>';
        
        // Data rows
        foreach ($data as $index => $row) {
            echo '<tr>';
            echo '<td>' . ($index + 1) . '</td>';
            echo '<td>' . htmlspecialchars($row['purpose'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['person_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['employee_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['car_name'] ?? '') . '</td>';
            echo '<td class="number">' . number_format($row['gas_liters'] ?? 0, 2) . '</td>';
            echo '<td>' . htmlspecialchars($row['expense_type'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['material_name'] ?? '') . '</td>';
            echo '<td class="number">' . number_format($row['material_quantity'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['material_purchase_price_iqd'] ?? 0, 0) . '</td>';
            echo '<td class="number">' . number_format($row['material_purchase_price_usd'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['material_total_cost'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['gas_purchase_price_input'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['gas_total_cost'] ?? 0, 2) . '</td>';
            echo '<td>' . htmlspecialchars($row['payment_type'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['currency_type'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['invoice_number'] ?? '') . '</td>';
            echo '<td class="number">' . number_format($row['amount_iqd'] ?? 0, 0) . '</td>';
            echo '<td class="number">' . number_format($row['amount_usd'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['paid_iqd'] ?? 0, 0) . '</td>';
            echo '<td class="number">' . number_format($row['paid_usd'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['exchange_rate'] ?? 0, 0) . '</td>';
            echo '<td class="number">' . number_format($row['remaining_iqd'] ?? 0, 0) . '</td>';
            echo '<td class="number">' . number_format($row['remaining_usd'] ?? 0, 2) . '</td>';
            echo '<td>' . htmlspecialchars($row['date'] ?? '') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
    }
    
} catch (Exception $e) {
    echo 'هەڵە: ' . $e->getMessage();
}
