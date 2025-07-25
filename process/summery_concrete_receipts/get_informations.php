<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'نەتەوەت لۆگین بکەیت']);
    exit;
}

// Check if user has permission
if (!hasPermission('view_concrete_receipts')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'توانای دەست گەیشتنت نییە']);
    exit;
}

try {
    // Get filter parameters
    $customer_id = $_POST['customer_id'] ?? '';
    $formula_id = $_POST['formula_id'] ?? '';
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    
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
    
    // Get customer summary
    $customer_summary_query = "
        SELECT 
            c.id as customer_id,
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
    
    // Clean up formulas, locations, and receivers (remove duplicates and nulls)
    foreach ($customer_summary as &$customer) {
        $customer['formulas'] = cleanGroupConcat($customer['formulas']);
        $customer['locations'] = cleanGroupConcat($customer['locations']);
        $customer['receivers'] = cleanGroupConcat($customer['receivers']);
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'customerSummary' => $customer_summary
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'هەڵە لە وەرگرتنی داتا: ' . $e->getMessage()
    ]);
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
