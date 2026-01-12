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
    
    try {
        $checkColumns = $pdo->query("SHOW COLUMNS FROM employees LIKE 'join_date'");
        $joinDateExists = $checkColumns->rowCount() > 0;
    } catch (Exception $e) {
        error_log('Error checking join_date column: ' . $e->getMessage());
    }
    
    // Build query based on column existence
    $columns = 'id, name, mobile, role, salary';
    
    if ($bonusExists) {
        $columns .= ', COALESCE(bonus, 0) as bonus';
    } else {
        $columns .= ', 0 as bonus';
    }
    
    if ($statusExists) {
        $columns .= ', COALESCE(status, "active") as status';
    } else {
        $columns .= ', "active" as status';
    }

    if ($joinDateExists) {
        $columns .= ', join_date';
    } else {
        $columns .= ', NULL as join_date';
    }
    
    $query = "SELECT $columns FROM employees ORDER BY id DESC";
    
    error_log('Query: ' . $query);
    
    $query = "SELECT $columns FROM employees ORDER BY id DESC";
    
    error_log('Query: ' . $query);
    
    // Get employees data
    $stmt = $pdo->query($query);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total expenses for each employee
    $expenses_map = [];
    try {
        $exp_stmt = $pdo->query("SELECT employee_id, SUM(amount) as total_expenses FROM employee_expenses GROUP BY employee_id");
        while ($row = $exp_stmt->fetch(PDO::FETCH_ASSOC)) {
            $expenses_map[$row['employee_id']] = floatval($row['total_expenses']);
        }
    } catch (Exception $e) {
        error_log('Error fetching expenses: ' . $e->getMessage());
    }

    // Helper function to calculate accrued amount
    function calculateAccruedAmount($joinDate, $monthlyAmount) {
        if (!$joinDate || $monthlyAmount <= 0) return 0;
        
        try {
            $start = new DateTime($joinDate);
            $end = new DateTime(); // Today
            $end->setTime(0, 0, 0); // Normalize to start of day
            $start->setTime(0, 0, 0);
            
            if ($start > $end) return 0;
            
            $accrued = 0;
            $current = clone $start;

            // Optimization: If start and end are in the same month/year
            if ($start->format('Y-m') === $end->format('Y-m')) {
                $daysInMonth = (int)$start->format('t');
                $daysWorked = $end->diff($start)->days + 1;
                return ($monthlyAmount / $daysInMonth) * $daysWorked;
            }
            
            while ($current <= $end) {
                $daysInMonth = (int)$current->format('t');
                $currentMonthStr = $current->format('Y-m');
                $endMonthStr = $end->format('Y-m');
                
                if ($currentMonthStr === $endMonthStr) {
                    // Last month (current month)
                    // Days from 1st (or current postion) to End Date (Today)
                    // Since we loop, $current will be 1st of this month
                    $dayOfMonth = (int)$current->format('j'); // Should be 1
                    $lastDay = (int)$end->format('j');
                    $daysToCount = $lastDay - $dayOfMonth + 1;
                    $accrued += ($monthlyAmount / $daysInMonth) * $daysToCount;
                    break;
                } else {
                    // Full month or partial start month
                    // If current is start month, taking remaining days
                    $dayOfMonth = (int)$current->format('j');
                    $daysToCount = $daysInMonth - $dayOfMonth + 1;
                    $accrued += ($monthlyAmount / $daysInMonth) * $daysToCount;
                    
                    // Move to next month
                    $current->modify('first day of next month');
                }
            }
            return $accrued;
        } catch (Exception $e) {
            error_log('Calculation error: ' . $e->getMessage());
            return 0;
        }
    }
    
    // Process each employee to add calculated fields
    foreach ($employees as &$emp) {
        $join_date = $emp['join_date'] ?? null;
        $salary = floatval($emp['salary'] ?? 0);
        $bonus = floatval($emp['bonus'] ?? 0);
        
        // Calculate Accrued Earnings
        $accrued_salary = calculateAccruedAmount($join_date, $salary);
        $accrued_bonus = calculateAccruedAmount($join_date, $bonus);
        $total_accrued = $accrued_salary + $accrued_bonus;
        
        // Get Expenses (Payments)
        $total_expenses = $expenses_map[$emp['id']] ?? 0;
        
        // Net Pay = Accrued - Expenses
        $emp['net_pay'] = round($total_accrued - $total_expenses, 2);
        
        // Calculate Daily Rate (based on current month)
        $days_in_current_month = (int)date('t');
        $daily_salary = $salary / $days_in_current_month;
        $daily_bonus = $bonus / $days_in_current_month;
        $emp['daily_pay'] = round($daily_salary + $daily_bonus, 2);
        
        // Debug for the specific example case
        if ($emp['salary'] == 600000 && $emp['bonus'] == 150000 && $join_date == date('Y-m-d')) {
             // Just verifying logic match
        }
    }
    unset($emp); // Break reference
    
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
