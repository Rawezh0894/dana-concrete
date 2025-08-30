<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    // Get total customer debt
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(remaining_amount), 0) as total_debt 
        FROM sales 
        WHERE remaining_amount > 0
    ");
    $total_debt = $stmt->fetch(PDO::FETCH_ASSOC)['total_debt'];

    // Get count of customers with debt
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT customer_id) as customers_with_debt 
        FROM sales 
        WHERE remaining_amount > 0
    ");
    $customers_with_debt = $stmt->fetch(PDO::FETCH_ASSOC)['customers_with_debt'];

    // Get total sales count
    $stmt = $pdo->query("SELECT COUNT(*) as total_sales FROM sales");
    $total_sales = $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'];

    // Get total cubic meters sold
    $stmt = $pdo->query("SELECT COALESCE(SUM(quantity), 0) as total_cubic_meters FROM sales");
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