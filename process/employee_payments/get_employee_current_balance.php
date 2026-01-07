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
    
    // Get current balance
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
    
    // Calculate expected balance from expenses for verification
    $expected_query = "
        SELECT 
            COALESCE(SUM(CASE WHEN expense_type IN ('salary', 'bonus', 'overtime') THEN amount ELSE 0 END), 0) as total_income,
            COALESCE(SUM(CASE WHEN expense_type IN ('advance', 'deduction', 'penalty') THEN amount ELSE 0 END), 0) as total_deductions
        FROM employee_expenses
        WHERE employee_id = ?
    ";
    
    $stmt = $pdo->prepare($expected_query);
    $stmt->execute([$employee_id]);
    $expected = $stmt->fetch(PDO::FETCH_ASSOC);
    $expected_net = floatval($expected['total_income'] ?? 0) - floatval($expected['total_deductions'] ?? 0);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'employee_id' => intval($employee['id']),
            'employee_name' => $employee['name'],
            'payable_balance' => floatval($employee['payable_balance']),
            'receivable_balance' => floatval($employee['receivable_balance']),
            'net_balance' => floatval($employee['net_balance']),
            'expected_net_balance' => $expected_net,
            'balance_message' => $employee['net_balance'] >= 0 
                ? 'کۆمپانیا قەرزی کارمەندە: ' . number_format($employee['net_balance'], 2) . ' د.ع'
                : 'کارمەند قەرزی کۆمپانیایە: ' . number_format(abs($employee['net_balance']), 2) . ' د.ع',
            'balance_correct' => abs(floatval($employee['net_balance']) - $expected_net) < 0.01
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}

