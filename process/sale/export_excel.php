<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

if (!hasPermission('view_sale')) {
    echo 'ڕێگەت پێنەدراوە!';
    exit;
}

// Get filter parameters
$customer_id = $_POST['customer_id'] ?? '';
$from_date = $_POST['from_date'] ?? '';
$to_date = $_POST['to_date'] ?? '';
$export_type = $_POST['export_type'] ?? 'detailed';
$quantity_range = $_POST['quantity_range'] ?? '';
$min_quantity = $_POST['min_quantity'] ?? '';
$max_quantity = $_POST['max_quantity'] ?? '';

// Build WHERE clause
$where = [];
$params = [];

if ($customer_id) {
    $where[] = "s.customer_id = ?";
    $params[] = $customer_id;
}

if ($from_date) {
    $where[] = "s.order_date >= ?";
    $params[] = $from_date;
}

if ($to_date) {
    $where[] = "s.order_date <= ?";
    $params[] = $to_date;
}

// Handle quantity filters (min_quantity and max_quantity take priority over quantity_range)
if ($min_quantity !== '' || $max_quantity !== '') {
    if ($min_quantity !== '') {
        $where[] = "s.quantity >= ?";
        $params[] = $min_quantity;
    }
    if ($max_quantity !== '') {
        $where[] = "s.quantity <= ?";
        $params[] = $max_quantity;
    }
} elseif ($quantity_range) {
    switch ($quantity_range) {
        case '<5':
            $where[] = "s.quantity < ?";
            $params[] = 5;
            break;
        case '5-10':
            $where[] = "(s.quantity BETWEEN ? AND ?)";
            $params[] = 5;
            $params[] = 10;
            break;
        case '>10':
            $where[] = "s.quantity > ?";
            $params[] = 10;
            break;
    }
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Get data
$sql = "SELECT 
    c.name AS customer_name,
    s.recipient,
    s.location,
    s.invoice_number,
    f.name AS formula_name,
    s.order_date,
    s.payment_type,
    s.quantity,
    s.price_per_unit,
    s.total_price,
    s.amount_paid_iq,
    s.amount_paid_usd,
    s.remaining_amount,
    s.dolar_rate,
    s.discount,
    s.notes
FROM sales s
LEFT JOIN customers c ON s.customer_id = c.id
LEFT JOIN concrete_formulas f ON s.formula_id = f.id
$where_sql
ORDER BY s.order_date ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for Excel download with proper UTF-8 encoding
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Transfer-Encoding: binary');
    
    // Output UTF-8 BOM for proper encoding
    echo "\xEF\xBB\xBF";
    
    if ($export_type === 'summary') {
        // Export summary data
        header('Content-Disposition: attachment; filename="کورتەی_فرۆشتنەکان_' . date('Y-m-d') . '.xls"');
        
        // Get summary data
        $summary_sql = "SELECT 
            COUNT(*) as total_sales,
            SUM(CASE WHEN s.payment_type = 'قەرز' THEN s.remaining_amount ELSE 0 END) as total_customer_debt,
            COUNT(DISTINCT CASE WHEN s.payment_type = 'قەرز' AND s.remaining_amount > 0 THEN s.customer_id END) as customers_with_debt
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN concrete_formulas f ON s.formula_id = f.id
        $where_sql";
        
        $summary_stmt = $pdo->prepare($summary_sql);
        $summary_stmt->execute($params);
        $summary_data = $summary_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Start Excel content for summary
        echo '<!DOCTYPE html>';
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th, td { border: 1px solid #000; padding: 8px; text-align: center; }';
        echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
        echo '.number { text-align: right; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<table border="1">';
        
        // Summary header
        echo '<tr><th colspan="2" style="background-color: #2196F3; color: white; font-size: 16px;">کورتەی فرۆشتنەکان</th></tr>';
        echo '<tr><th>بەروار</th><th>' . date('Y-m-d') . '</th></tr>';
        echo '<tr><th>کۆی قەرزی کڕیاران</th><td class="number">$' . number_format($summary_data['total_customer_debt'] ?? 0, 2) . '</td></tr>';
        echo '<tr><th>کۆی فرۆشتنەکان</th><td class="number">' . number_format($summary_data['total_sales'] ?? 0, 0) . '</td></tr>';
        echo '<tr><th>کڕیارانی قەرزدار</th><td class="number">' . number_format($summary_data['customers_with_debt'] ?? 0, 0) . '</td></tr>';
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
        
    } else {
        // Export detailed data
        header('Content-Disposition: attachment; filename="فرۆشتنەکان_' . date('Y-m-d') . '.xls"');
        
        // Start Excel content for detailed export
        echo '<!DOCTYPE html>';
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
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
        echo '<th>کڕیار</th>';
        echo '<th>وەرگر</th>';
        echo '<th>شوێن</th>';
        echo '<th>ژمارەی پسوڵە</th>';
        echo '<th>فۆرمۆلا</th>';
        echo '<th>بەروار</th>';
        echo '<th>جۆری پارەدان</th>';
        echo '<th>بڕ (م³)</th>';
        echo '<th>نرخی یەکە</th>';
        echo '<th>کۆی نرخ</th>';
        echo '<th>پارەی دراو بە دینار</th>';
        echo '<th>پارەی دراو بە دۆلار</th>';
        echo '<th>پارەی ماوە</th>';
        echo '<th>نرخی ١٠٠ دۆلار</th>';
        echo '<th>داشکاندن</th>';
        echo '<th>تێبینی</th>';
        echo '</tr>';
        
        // Data rows
        foreach ($data as $index => $row) {
            echo '<tr>';
            echo '<td>' . ($index + 1) . '</td>';
            echo '<td>' . htmlspecialchars($row['customer_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['recipient'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['location'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['invoice_number'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['formula_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['order_date'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['payment_type'] ?? '') . '</td>';
            echo '<td class="number">' . number_format($row['quantity'] ?? 0, 4) . '</td>';
            echo '<td class="number">' . number_format($row['price_per_unit'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['total_price'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['amount_paid_iq'] ?? 0, 0) . '</td>';
            echo '<td class="number">' . number_format($row['amount_paid_usd'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['remaining_amount'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['dolar_rate'] ?? 0, 0) . '</td>';
            echo '<td class="number">' . number_format($row['discount'] ?? 0, 2) . '</td>';
            echo '<td>' . htmlspecialchars($row['notes'] ?? '') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
    }
    
} catch (Exception $e) {
    echo 'هەڵە: ' . $e->getMessage();
}
