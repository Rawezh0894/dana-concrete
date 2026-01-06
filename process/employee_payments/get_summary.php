<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    // Check if table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'employee_transactions'");
    if ($checkTable->rowCount() == 0) {
        echo json_encode(['success' => true, 'data' => ['summary' => ['total_payments' => 0, 'total_salary' => 0, 'total_bonus' => 0, 'total_karwanhisabi' => 0, 'payment_count' => 0], 'filters' => ['employees' => [], 'months' => []]]]);
        exit;
    }

    $month_filter = $_GET['month'] ?? '';
    $employee_filter = $_GET['employee'] ?? '';
    
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
    
    // Summary by transaction types
    $query = "SELECT 
        SUM(CASE WHEN type IN ('مووچە (Accrual)', 'ئۆڤەر تایم (Overtime)') THEN amount_iqd ELSE 0 END) as total_salary,
        SUM(CASE WHEN type = 'پاداشت (Bonus)' THEN amount_iqd ELSE 0 END) as total_bonus,
        SUM(CASE WHEN type = 'وەصڵ کردن (Payment)' THEN amount_iqd ELSE 0 END) as total_payments,
        SUM(CASE WHEN type = 'پێشەکی (Advance)' THEN amount_iqd ELSE 0 END) as total_advance,
        COUNT(*) as transaction_count
        FROM employee_transactions $where_clause";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get employees for filter dropdown
    $employees = $pdo->query("SELECT id, name FROM employees ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique months for filter dropdown
    $months = $pdo->query("SELECT DISTINCT pay_month FROM employee_transactions ORDER BY pay_month DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'total_payments' => floatval($summary['total_payments'] ?? 0),
                'total_salary' => floatval($summary['total_salary'] ?? 0),
                'total_bonus' => floatval($summary['total_bonus'] ?? 0),
                'total_karwanhisabi' => floatval($summary['total_advance'] ?? 0), // Mapping Advance to KarwanHisabi card for now
                'payment_count' => intval($summary['transaction_count'] ?? 0)
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