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
    // Check if status column exists for filtering active employees
    if ($statusExists) {
        // Count all employees
        $total_employees_stmt = $pdo->query('SELECT COUNT(*) as total_employees FROM employees');
        $total_employees = $total_employees_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate salary and bonus for active employees only
        $active_summary_stmt = $pdo->query("SELECT 
            COUNT(*) as active_employees,
            SUM(salary) as total_salary_active,
            SUM(COALESCE(bonus, 0)) as total_bonus_active
            FROM employees 
            WHERE COALESCE(status, 'active') = 'active'");
        $active_summary = $active_summary_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate total salary and bonus (all employees)
        $all_summary_stmt = $pdo->query("SELECT 
            SUM(salary) as total_salary_all,
            SUM(COALESCE(bonus, 0)) as total_bonus_all
            FROM employees");
        $all_summary = $all_summary_stmt->fetch(PDO::FETCH_ASSOC);
        
        $summary = [
            'total_employees' => (int)($total_employees['total_employees'] ?? 0),
            'active_employees' => (int)($active_summary['active_employees'] ?? 0),
            'total_salary' => (float)($active_summary['total_salary_active'] ?? 0), // Only active employees
            'total_bonus' => (float)($active_summary['total_bonus_active'] ?? 0), // Only active employees
            'total_salary_all' => (float)($all_summary['total_salary_all'] ?? 0),
            'total_bonus_all' => (float)($all_summary['total_bonus_all'] ?? 0),
            'total_salary_plus_bonus' => (float)($active_summary['total_salary_active'] ?? 0) + (float)($active_summary['total_bonus_active'] ?? 0)
        ];
    } else {
        // If status column doesn't exist, treat all as active
        $summary_stmt = $pdo->query("SELECT 
            COUNT(*) as total_employees,
            SUM(salary) as total_salary,
            SUM(COALESCE(bonus, 0)) as total_bonus
            FROM employees");
        $summary_data = $summary_stmt->fetch(PDO::FETCH_ASSOC);
        
        $summary = [
            'total_employees' => (int)($summary_data['total_employees'] ?? 0),
            'active_employees' => (int)($summary_data['total_employees'] ?? 0),
            'total_salary' => (float)($summary_data['total_salary'] ?? 0),
            'total_bonus' => (float)($summary_data['total_bonus'] ?? 0),
            'total_salary_all' => (float)($summary_data['total_salary'] ?? 0),
            'total_bonus_all' => (float)($summary_data['total_bonus'] ?? 0),
            'total_salary_plus_bonus' => (float)($summary_data['total_salary'] ?? 0) + (float)($summary_data['total_bonus'] ?? 0)
        ];
    }
    
    // Calculate role statistics for active employees only
    $role_stats = [];
    $all_roles = [
        'حەرەس(پاسەوان)',
        'شۆفێری میکسەر',
        'شۆفێری پەمپ',
        'مساعید پەمپ',
        'مەسوول سایەق',
        'جۆکەر',
        'سێنتڕاڵ',
        'فیتەر',
        'مساعید مەعمەل',
        'شێف (چێشتلێنەر)',
        'بەڕێوەبەر',
        'ژمێریار',
        'وەکیل',
        'سایەق شۆفڵ',
        'موکەعيب'
    ];
    
    foreach ($all_roles as $role) {
        $count = 0;
        foreach ($employees as $emp) {
            $emp_status = $emp['status'] ?? 'active';
            if ($emp_status === 'active') {
                $emp_roles = $emp['role'] ?? '';
                // Check if employee has this role (supports multiple roles as comma-separated)
                if (strpos($emp_roles, $role) !== false) {
                    $count++;
                }
            }
        }
        $role_stats[$role] = $count;
    }
    
    error_log('Employees retrieved successfully: Count=' . count($employees));
    echo json_encode([
        'employees' => $employees,
        'summary' => $summary,
        'role_stats' => $role_stats
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
