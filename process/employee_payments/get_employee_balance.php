<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    $employee_id = intval($_GET['employee_id'] ?? 0);
    
    if ($employee_id <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'IDی کارمەند پێویستە'
        ]);
        exit;
    }
    
    $query = "
        SELECT 
            id,
            name,
            COALESCE(payable_balance, 0) as payable_balance,
            COALESCE(receivable_balance, 0) as receivable_balance,
            (COALESCE(payable_balance, 0) - COALESCE(receivable_balance, 0)) as net_balance
        FROM employees
        WHERE id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo json_encode([
            'success' => false,
            'error' => 'کارمەند نەدۆزرایەوە'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'employee_id' => intval($employee['id']),
            'employee_name' => $employee['name'],
            'payable_balance' => floatval($employee['payable_balance']),
            'receivable_balance' => floatval($employee['receivable_balance']),
            'net_balance' => floatval($employee['net_balance'])
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}

