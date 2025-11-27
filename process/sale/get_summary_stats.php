<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

// Get filter parameters
$customer_id = $_GET['customer_id'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$min_quantity = $_GET['min_quantity'] ?? '';
$max_quantity = $_GET['max_quantity'] ?? '';

// Build WHERE clause for filters
$where_conditions = [];
$params = [];

if ($customer_id) {
    $where_conditions[] = "customer_id = ?";
    $params[] = $customer_id;
}

if ($from_date) {
    $where_conditions[] = "order_date >= ?";
    $params[] = $from_date;
}

if ($to_date) {
    $where_conditions[] = "order_date <= ?";
    $params[] = $to_date;
}

if ($min_quantity !== '') {
    $where_conditions[] = "quantity >= ?";
    $params[] = $min_quantity;
}

if ($max_quantity !== '') {
    $where_conditions[] = "quantity <= ?";
    $params[] = $max_quantity;
}

$where_sql = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Get total customer debt with filters
    $debt_where_conditions = $where_conditions;
    $debt_where_conditions[] = "remaining_amount > 0";
    $debt_where_sql = $debt_where_conditions ? 'WHERE ' . implode(' AND ', $debt_where_conditions) : 'WHERE remaining_amount > 0';
    
    $sql = "
        SELECT COALESCE(SUM(remaining_amount), 0) as total_debt 
        FROM sales 
        $debt_where_sql
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $total_debt = $stmt->fetch(PDO::FETCH_ASSOC)['total_debt'];

    // Get count of customers with debt with filters
    $sql = "
        SELECT COUNT(DISTINCT customer_id) as customers_with_debt 
        FROM sales 
        $debt_where_sql
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers_with_debt = $stmt->fetch(PDO::FETCH_ASSOC)['customers_with_debt'];

    // Get total sales count with filters
    $sql = "SELECT COUNT(*) as total_sales FROM sales $where_sql";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $total_sales = $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'];

    // Get total cubic meters sold with filters
    $sql = "SELECT COALESCE(SUM(quantity), 0) as total_cubic_meters FROM sales $where_sql";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $total_cubic_meters = $stmt->fetch(PDO::FETCH_ASSOC)['total_cubic_meters'];

    echo json_encode([
        'success' => true,
        'data' => [
            'total_customer_debt' => floatval($total_debt),
            'customers_with_debt' => intval($customers_with_debt),
            'total_sales' => intval($total_sales),
            'total_cubic_meters' => floatval($total_cubic_meters)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 