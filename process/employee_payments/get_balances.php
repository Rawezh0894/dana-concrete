<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    $employee_filter = $_GET['employee'] ?? '';
    $where_clause = '';
    $params = [];
    
    if ($employee_filter) {
        $where_clause = 'WHERE e.id = ?';
        $params[] = $employee_filter;
    }
    
    // Get employee balances summary
    $query = "
        SELECT 
            SUM(COALESCE(e.payable_balance, 0)) as total_payable,
            SUM(COALESCE(e.receivable_balance, 0)) as total_receivable,
            COUNT(DISTINCT e.id) as employee_count,
            SUM(CASE WHEN COALESCE(e.payable_balance, 0) > 0 THEN 1 ELSE 0 END) as employees_with_payable,
            SUM(CASE WHEN COALESCE(e.receivable_balance, 0) > 0 THEN 1 ELSE 0 END) as employees_with_receivable
        FROM employees e
        $where_clause
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get current month expenses summary
    $current_month = date('Y-m');
    $expense_query = "
        SELECT 
            expense_type,
            SUM(amount) as total_amount
        FROM employee_expenses
        WHERE expense_date = ?
    ";
    
    if ($employee_filter) {
        $expense_query .= " AND employee_id = ?";
        $expense_params = [$current_month, $employee_filter];
    } else {
        $expense_params = [$current_month];
    }
    
    $expense_query .= " GROUP BY expense_type";
    
    $stmt = $pdo->prepare($expense_query);
    $stmt->execute($expense_params);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $expense_summary = [
        'salary' => 0,
        'bonus' => 0,
        'overtime' => 0,
        'advance' => 0,
        'deduction' => 0,
        'penalty' => 0
    ];
    
    foreach ($expenses as $exp) {
        $expense_summary[$exp['expense_type']] = floatval($exp['total_amount']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'balances' => [
                'total_payable' => floatval($summary['total_payable'] ?? 0),
                'total_receivable' => floatval($summary['total_receivable'] ?? 0),
                'net_balance' => floatval($summary['total_payable'] ?? 0) - floatval($summary['total_receivable'] ?? 0),
                'employee_count' => intval($summary['employee_count'] ?? 0),
                'employees_with_payable' => intval($summary['employees_with_payable'] ?? 0),
                'employees_with_receivable' => intval($summary['employees_with_receivable'] ?? 0)
            ],
            'current_month_expenses' => $expense_summary
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}

