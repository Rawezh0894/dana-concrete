<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'نەتەوەت لۆگین بکەیت';
    exit;
}

// Check if user has permission
if (!hasPermission('view_concrete_receipts')) {
    http_response_code(403);
    echo 'توانای دەست گەیشتنت نییە';
    exit;
}

try {
    // Get filter parameters
    $customer_id = $_GET['customer_id'] ?? '';
    $formula_id = $_GET['formula_id'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if (!empty($customer_id)) {
        $where_conditions[] = "cr.customer_id = ?";
        $params[] = $customer_id;
    }
    
    if (!empty($formula_id)) {
        $where_conditions[] = "cr.formulas_id = ?";
        $params[] = $formula_id;
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(cr.date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(cr.date) <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get customer summary data
    $customer_summary_query = "
        SELECT 
            c.name as customer_name,
            c.mobile1,
            COUNT(cr.id) as total_receipts,
            SUM(cr.meter_amount) as total_meter_cubic,
            GROUP_CONCAT(DISTINCT cf.name ORDER BY cf.name SEPARATOR ', ') as formulas,
            GROUP_CONCAT(DISTINCT cr.location ORDER BY cr.location SEPARATOR ', ') as locations,
            GROUP_CONCAT(DISTINCT cr.receiver_name ORDER BY cr.receiver_name SEPARATOR ', ') as receivers
        FROM customers c
        INNER JOIN concrete_receipts cr ON c.id = cr.customer_id
        LEFT JOIN concrete_formulas cf ON cr.formulas_id = cf.id
        $where_clause
        GROUP BY c.id, c.name, c.mobile1
        ORDER BY total_meter_cubic DESC
    ";
    
    $customer_summary_stmt = $pdo->prepare($customer_summary_query);
    $customer_summary_stmt->execute($params);
    $customer_summary = $customer_summary_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $summary_query = "
        SELECT 
            COUNT(DISTINCT cr.id) as total_receipts,
            SUM(cr.meter_amount) as total_meter_cubic,
            COUNT(DISTINCT cr.customer_id) as total_customers,
            COUNT(DISTINCT cr.formulas_id) as total_formulas
        FROM concrete_receipts cr
        $where_clause
    ";
    
    $summary_stmt = $pdo->prepare($summary_query);
    $summary_stmt->execute($params);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Clean up data
    foreach ($customer_summary as &$customer) {
        $customer['formulas'] = cleanGroupConcat($customer['formulas']);
        $customer['locations'] = cleanGroupConcat($customer['locations']);
        $customer['receivers'] = cleanGroupConcat($customer['receivers']);
        $customer['total_meter_cubic'] = number_format($customer['total_meter_cubic'], 2);
    }
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="concrete_receipts_summary_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Create Excel content
    echo '<html dir="rtl">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 8px; text-align: center; }';
    echo 'th { background-color: #f0f0f0; font-weight: bold; }';
    echo '.summary { margin-bottom: 20px; }';
    echo '.summary table { width: auto; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Summary section
    echo '<div class="summary">';
    echo '<h2>پوختەی پسووڵەکانی کۆنکرێت</h2>';
    echo '<p>بەرواری هەناردەکردن: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<table>';
    echo '<tr><th>کۆی گشتی پسووڵەکان</th><th>کۆی گشتی بڕی مەتر سێجا</th><th>کۆی کڕیاران</th><th>کۆی فۆرمۆلاکان</th></tr>';
    echo '<tr>';
    echo '<td>' . ($summary['total_receipts'] ?? 0) . '</td>';
    echo '<td>' . number_format($summary['total_meter_cubic'] ?? 0, 2) . ' م³</td>';
    echo '<td>' . ($summary['total_customers'] ?? 0) . '</td>';
    echo '<td>' . ($summary['total_formulas'] ?? 0) . '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';
    
    // Customer summary table
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>ناوی کڕیار</th>';
    echo '<th>ژمارە تەلەفۆن</th>';
    echo '<th>کۆی پسووڵەکان</th>';
    echo '<th>کۆی بڕی مەتر سێجا</th>';
    echo '<th>فۆرمۆلاکان</th>';
    echo '<th>شوێنەکان</th>';
    echo '<th>وەرگرەکان</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if (empty($customer_summary)) {
        echo '<tr><td colspan="8">هیچ داتایەک نییە</td></tr>';
    } else {
        foreach ($customer_summary as $index => $customer) {
            echo '<tr>';
            echo '<td>' . ($index + 1) . '</td>';
            echo '<td>' . htmlspecialchars($customer['customer_name']) . '</td>';
            echo '<td>' . htmlspecialchars($customer['mobile1'] ?? '-') . '</td>';
            echo '<td>' . $customer['total_receipts'] . '</td>';
            echo '<td>' . $customer['total_meter_cubic'] . ' م³</td>';
            echo '<td>' . htmlspecialchars($customer['formulas']) . '</td>';
            echo '<td>' . htmlspecialchars($customer['locations']) . '</td>';
            echo '<td>' . htmlspecialchars($customer['receivers']) . '</td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'هەڵە لە هەناردەکردن: ' . $e->getMessage();
}

// Helper function to clean GROUP_CONCAT results
function cleanGroupConcat($string) {
    if (empty($string) || $string === 'NULL') {
        return '-';
    }
    
    // Split by comma, remove duplicates, and filter out empty/null values
    $items = array_filter(array_map('trim', explode(',', $string)));
    $items = array_unique($items);
    $items = array_filter($items, function($item) {
        return !empty($item) && $item !== 'NULL' && $item !== 'null';
    });
    
    return !empty($items) ? implode(', ', $items) : '-';
}
?> 