<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and GET data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('get_customer_debt.php GET: ' . print_r($_GET, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for customer debt retrieval');
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە!']);
    exit;
}

try {
    $customer_id = $_GET['customer_id'] ?? null;
    if (!$customer_id) {
        error_log('No customer ID provided for debt retrieval');
        echo json_encode(['success' => false, 'msg' => 'ناسنامەی کڕیار پێویستە!']);
        exit;
    }

    // Get total remaining amount from sales (USD only - same logic as select_sale.php)
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(remaining_amount), 0) as total_remaining 
                          FROM sales 
                          WHERE customer_id = ? 
                          AND payment_type = "قەرز" 
                          AND dolar_rate IS NOT NULL 
                          AND dolar_rate > 0');
    $stmt->execute([$customer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $total_remaining = floatval($row['total_remaining'] ?? 0);
        error_log('Customer debt retrieved: Customer=' . $customer_id . ', Debt=' . $total_remaining);
        echo json_encode(['success' => true, 'debt_usd' => $total_remaining]);
    } else {
        error_log('No debt data found for customer: ID=' . $customer_id);
        echo json_encode(['success' => false, 'msg' => 'کڕیار نەدۆزرایەوە!']);
    }
} catch (PDOException $e) {
    error_log('PDOException in get_customer_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in get_customer_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
?> 