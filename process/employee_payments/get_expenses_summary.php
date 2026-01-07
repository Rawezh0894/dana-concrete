<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    // Get filter parameters
    $month_filter = $_GET['month'] ?? '';
    $employee_filter = $_GET['employee'] ?? '';
    
    // Build WHERE conditions
    $where_conditions = [];
    $params = [];
    
    if ($month_filter) {
        $where_conditions[] = "expense_date = ?";
        $params[] = $month_filter;
    }
    
    if ($employee_filter) {
        $where_conditions[] = "employee_id = ?";
        $params[] = $employee_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Prepare base query for summary
    $base_query = "
        SELECT 
            SUM(CASE WHEN expense_type = 'salary' THEN amount ELSE 0 END) as total_salary,
            SUM(CASE WHEN expense_type = 'bonus' THEN amount ELSE 0 END) as total_bonus,
            SUM(CASE WHEN expense_type = 'overtime' THEN amount ELSE 0 END) as total_overtime,
            SUM(CASE WHEN expense_type = 'advance' THEN amount ELSE 0 END) as total_advance,
            SUM(CASE WHEN expense_type = 'deduction' THEN amount ELSE 0 END) as total_deduction,
            SUM(CASE WHEN expense_type = 'penalty' THEN amount ELSE 0 END) as total_penalty,
            COUNT(*) as expense_count
        FROM employee_expenses 
        $where_clause
    ";
    
    $stmt = $pdo->prepare($base_query);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get employees for filter dropdown
    $employees_query = "SELECT id, name FROM employees ORDER BY name";
    $stmt = $pdo->query($employees_query);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique months for filter dropdown
    $months_query = "SELECT DISTINCT expense_date FROM employee_expenses ORDER BY expense_date DESC";
    $stmt = $pdo->query($months_query);
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'total_salary' => floatval($summary['total_salary'] ?? 0),
                'total_bonus' => floatval($summary['total_bonus'] ?? 0),
                'total_overtime' => floatval($summary['total_overtime'] ?? 0),
                'total_advance' => floatval($summary['total_advance'] ?? 0),
                'total_deduction' => floatval($summary['total_deduction'] ?? 0),
                'total_penalty' => floatval($summary['total_penalty'] ?? 0),
                'expense_count' => intval($summary['expense_count'] ?? 0)
            ],
            'filters' => [
                'employees' => $employees,
                'months' => $months
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}
?>

