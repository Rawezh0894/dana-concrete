<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_employee_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

try {
    // Check if table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'employee_transactions'");
    if ($checkTable->rowCount() == 0) {
        echo json_encode([]); // Return empty array if table doesn't exist yet
        exit;
    }

    $month_filter = $_GET['month'] ?? '';
    $employee_filter = $_GET['employee'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if ($month_filter) {
        $where_conditions[] = "t.pay_month = ?";
        $params[] = $month_filter;
    }
    
    if ($employee_filter) {
        $where_conditions[] = "t.employee_id = ?";
        $params[] = $employee_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT t.*, e.name as employee_name, u.username as created_by_name
            FROM employee_transactions t
            JOIN employees e ON t.employee_id = e.id
            LEFT JOIN users u ON t.created_by = u.id
            $where_clause
            ORDER BY t.date DESC, t.id DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($transactions);
    
} catch (Exception $e) {
    echo json_encode([]);
}
