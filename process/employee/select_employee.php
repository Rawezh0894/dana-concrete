<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for employees retrieval');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('view_employee')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to view employees');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    // Check if bonus and status columns exist
    $bonusExists = false;
    $statusExists = false;
    
    try {
        $checkColumns = $pdo->query("SHOW COLUMNS FROM employees LIKE 'bonus'");
        $bonusExists = $checkColumns->rowCount() > 0;
    } catch (Exception $e) {
        error_log('Error checking bonus column: ' . $e->getMessage());
    }
    
    try {
        $checkColumns = $pdo->query("SHOW COLUMNS FROM employees LIKE 'status'");
        $statusExists = $checkColumns->rowCount() > 0;
    } catch (Exception $e) {
        error_log('Error checking status column: ' . $e->getMessage());
    }
    
    // Build query based on column existence
    if ($bonusExists && $statusExists) {
        $query = 'SELECT id, name, mobile, role, salary, COALESCE(bonus, 0) as bonus, COALESCE(status, "active") as status FROM employees ORDER BY id DESC';
    } elseif ($bonusExists) {
        $query = 'SELECT id, name, mobile, role, salary, COALESCE(bonus, 0) as bonus, "active" as status FROM employees ORDER BY id DESC';
    } elseif ($statusExists) {
        $query = 'SELECT id, name, mobile, role, salary, 0 as bonus, COALESCE(status, "active") as status FROM employees ORDER BY id DESC';
    } else {
        $query = 'SELECT id, name, mobile, role, salary, 0 as bonus, "active" as status FROM employees ORDER BY id DESC';
    }
    
    error_log('Query: ' . $query);
    
    // Get employees data
    $stmt = $pdo->query($query);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Employees fetched: ' . count($employees));
    
    // Get summary statistics
    $summary_stmt = $pdo->query('SELECT COUNT(*) as total_employees, SUM(salary) as total_salary FROM employees');
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log('Employees retrieved successfully: Count=' . count($employees));
    echo json_encode([
        'employees' => $employees,
        'summary' => [
            'total_employees' => (int)($summary['total_employees'] ?? 0),
            'total_salary' => (float)($summary['total_salary'] ?? 0)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('PDOException in select_employee.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage(),
        'employees' => [],
        'summary' => ['total_employees' => 0, 'total_salary' => 0]
    ]);
} catch (Exception $e) {
    error_log('Exception in select_employee.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage(),
        'employees' => [],
        'summary' => ['total_employees' => 0, 'total_salary' => 0]
    ]);
}
