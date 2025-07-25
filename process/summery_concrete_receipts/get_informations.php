<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!hasPermission('view_concrete_receipts')) {
    echo json_encode(['success' => false, 'message' => 'No permission']);
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
        $where_conditions[] = "DATE(cr.created_at) >= ?";
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $where_conditions[] = "DATE(cr.created_at) <= ?";
        $params[] = $date_to;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get summary statistics
    $summary_query = "
        SELECT 
            COUNT(DISTINCT cr.id) as total_receipts,
            SUM(cr.meter_amount) as total_meter_cubic,
            COUNT(DISTINCT cr.customer_id) as total_customers,
            AVG(cr.meter_amount) as average_meter_amount
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
            c.mobile1 as customer_phone,
            COUNT(cr.id) as receipt_count,
            SUM(cr.meter_amount) as total_meter_cubic,
            GROUP_CONCAT(DISTINCT cf.name) as formulas_used,
            GROUP_CONCAT(DISTINCT cr.location) as locations,
            GROUP_CONCAT(DISTINCT cr.receiver_name) as receivers
        FROM customers c
        LEFT JOIN concrete_receipts cr ON c.id = cr.customer_id
        LEFT JOIN concrete_formulas cf ON cr.formulas_id = cf.id
        $where_clause
        GROUP BY c.id, c.name, c.mobile1
        HAVING receipt_count > 0
        ORDER BY total_meter_cubic DESC
    ";

    $customer_summary_stmt = $pdo->prepare($customer_summary_query);
    $customer_summary_stmt->execute($params);
    $customer_summary = $customer_summary_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get detailed customer information
    $customer_details_query = "
        SELECT 
            cr.id,
            cr.receipt_number,
            cr.location,
            cr.receiver_name,
            cr.meter_amount,
            cr.created_at,
            cf.name as formula_name
        FROM concrete_receipts cr
        LEFT JOIN concrete_formulas cf ON cr.formulas_id = cf.id
        WHERE cr.customer_id = ?
        ORDER BY cr.created_at DESC
    ";

    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'customer_summary' => $customer_summary,
        'customer_details_query' => $customer_details_query
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
