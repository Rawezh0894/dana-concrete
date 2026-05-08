<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !hasPermission('view_customer')) {
    error_log('Permission denied for user: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown') . ' to view customer debt');
    http_response_code(403);
    echo json_encode([]);
    exit;
}

try {
    if (isset($_GET['debt_id'])) {
        $debt_id = intval($_GET['debt_id']);
        $stmt = $pdo->prepare("SELECT * FROM customer_debt_payments WHERE id = ?");
        $stmt->execute([$debt_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $allocStmt = $pdo->prepare('SELECT sale_id, allocated_amount FROM customer_payment_allocations WHERE debt_payment_id = ? ORDER BY id ASC');
            $allocStmt->execute([$debt_id]);
            $row['allocations'] = $allocStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode($row ?: []);
        exit;
    }
    
    $customer_id = $_GET['customer_id'] ?? null;
    if (!$customer_id) {
        error_log('No customer ID provided for debt payments retrieval');
        echo json_encode([]);
        exit;
    }
    
    $stmt = $pdo->prepare('SELECT id, date, dolar_rate, paid_usd, paid_iqd, discount, note, payment_type, change_back_usd, change_back_iq FROM customer_debt_payments WHERE customer_id = ? ORDER BY date DESC, id DESC');
    $stmt->execute([$customer_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Debt payments retrieved: Customer=' . $customer_id . ', Count=' . count($rows));
    echo json_encode($rows);
    
} catch (PDOException $e) {
    error_log('PDOException in select_return_debt.php: ' . $e->getMessage());
    echo json_encode([]);
} catch (Exception $e) {
    error_log('Exception in select_return_debt.php: ' . $e->getMessage());
    echo json_encode([]);
}
