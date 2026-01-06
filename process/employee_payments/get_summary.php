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
        $where_conditions[] = "pay_month = ?";
        $params[] = $month_filter;
    }
    
    if ($employee_filter) {
        $where_conditions[] = "employee_id = ?";
        $params[] = $employee_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Prepare base query
    $base_query = "SELECT 
        SUM(salary) as total_salary,
        SUM(CAST(REPLACE(karwanhisabi, ',', '') AS DECIMAL(15,2))) as total_karwanhisabi,
        SUM(bonus) as total_bonus,
        SUM(total) as total_payments,
        COUNT(*) as payment_count
        FROM employee_payments $where_clause";
    
    $stmt = $pdo->prepare($base_query);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get employees for filter dropdown
    $employees_query = "SELECT id, name FROM employees ORDER BY name";
    $stmt = $pdo->query($employees_query);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique months for filter dropdown
    $months_query = "SELECT DISTINCT pay_month FROM employee_payments ORDER BY pay_month DESC";
    $stmt = $pdo->query($months_query);
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'total_payments' => floatval($summary['total_payments'] ?? 0),
                'total_salary' => floatval($summary['total_salary'] ?? 0),
                'total_bonus' => floatval($summary['total_bonus'] ?? 0),
                'total_karwanhisabi' => floatval($summary['total_karwanhisabi'] ?? 0),
                'payment_count' => intval($summary['payment_count'] ?? 0)
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