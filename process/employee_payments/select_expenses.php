<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    $month_filter = $_GET['month'] ?? '';
    $employee_filter = $_GET['employee'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if ($month_filter) {
        $where_conditions[] = "ee.expense_date = ?";
        $params[] = $month_filter;
    }
    
    if ($employee_filter) {
        $where_conditions[] = "ee.employee_id = ?";
        $params[] = $employee_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "
        SELECT 
            ee.id,
            ee.employee_id,
            e.name as employee_name,
            ee.expense_type,
            ee.amount,
            ee.notes,
            ee.expense_date,
            ee.created_at,
            u.username as created_by_name,
            COALESCE(e.payable_balance, 0) as employee_payable_balance,
            COALESCE(e.receivable_balance, 0) as employee_receivable_balance
        FROM employee_expenses ee
        LEFT JOIN employees e ON ee.employee_id = e.id
        LEFT JOIN users u ON ee.created_by = u.id
        $where_clause
        ORDER BY ee.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Translate expense types to Kurdish
    $expense_types_kurdish = [
        'salary' => 'مووچە',
        'bonus' => 'بەخشیش',
        'overtime' => 'کاروانحیسابی',
        'advance' => 'پێشەکی',
        'deduction' => 'کەمکردنەوە',
        'penalty' => 'سزا'
    ];
    
    foreach ($expenses as &$expense) {
        $expense['expense_type_kurdish'] = $expense_types_kurdish[$expense['expense_type']] ?? $expense['expense_type'];
    }
    
    echo json_encode($expenses);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}

