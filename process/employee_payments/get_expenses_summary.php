<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    // Get filter parameters
    $month_filter = $_GET['month'] ?? '';
    $employee_filter = $_GET['employee'] ?? '';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    
    // 1. Determine Date Range
    $current_date = date('Y-m-d');
    if ($start_date && $end_date) {
        $period_start = $start_date;
        $period_end = $end_date;
    } elseif ($month_filter) {
        $period_start = $month_filter . '-01';
        $period_end = date('Y-m-t', strtotime($period_start));
    } else {
        // Default to current month
        $period_start = date('Y-m-01');
        $period_end = date('Y-m-t');
    }

    $start_ts = strtotime($period_start);
    $end_ts = strtotime($period_end);
    $days_in_period = max(0, ($end_ts - $start_ts) / 86400 + 1);
    
    // For accrued calculation (Daily Balance)
    $today_ts = strtotime($current_date);
    $accrued_end_ts = min($end_ts, $today_ts);
    
    // For monthly salary calculation, we use the days in the specific month
    $days_in_month_basis = 30;
    $days_in_month_basis = cal_days_in_month(CAL_GREGORIAN, date('m', $start_ts), date('Y', $start_ts));
    
    // 2. Get Overtime Rate
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'overtime_rate'");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);
    $overtime_rate = floatval($setting['value'] ?? 0);
    
    // 3. Calculate Fixed Salary and Bonus from Employees table
    
    // Check if status column exists
    $status_exists = false;
    try {
        $check_status = $pdo->query("SHOW COLUMNS FROM employees LIKE 'status'");
        $status_exists = $check_status->rowCount() > 0;
    } catch (Exception $e) {}

    $emp_params = [];
    $emp_sql = "SELECT id, salary, COALESCE(bonus, 0) as bonus, join_date FROM employees WHERE 1=1";
    
    // Filter active employees if status column exists
    if ($status_exists) {
        $emp_sql .= " AND status = 'active'";
    }

    if ($employee_filter) {
        $emp_sql .= " AND id = ?";
        $emp_params[] = $employee_filter;
    }
    $stmt = $pdo->prepare($emp_sql);
    $stmt->execute($emp_params);
    $employees_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_salary = 0;
    $total_bonus = 0;
    
    $accrued_salary = 0;
    $accrued_bonus = 0;
    
    $employee_ids = [];
    
    foreach ($employees_data as $emp) {
        $employee_ids[] = $emp['id'];
        $emp_salary = floatval($emp['salary']);
        $emp_bonus = floatval($emp['bonus']);
        $join_date = $emp['join_date'] ?? null;
        
        // --- Full Period Calculation ---
        $emp_period_start = $period_start;
        if ($join_date && $join_date > $period_start) {
            if ($join_date > $period_end) {
                $emp_days = 0;
            } else {
                $emp_period_start = $join_date;
                $emp_days = max(0, ($end_ts - strtotime($emp_period_start)) / 86400 + 1);
            }
        } else {
            $emp_days = $days_in_period;
        }
        
        $total_salary += ($emp_salary / $days_in_month_basis) * $emp_days;
        $total_bonus += ($emp_bonus / $days_in_month_basis) * $emp_days;
        
        // --- Accrued Calculation (Up to Today) ---
        if ($accrued_end_ts >= $start_ts) {
            $emp_accrued_start = $period_start;
            if ($join_date && $join_date > $period_start) {
                if ($join_date > $current_date) {
                    $emp_accrued_days = 0;
                } else {
                    $emp_accrued_start = $join_date;
                    $emp_accrued_days = max(0, ($accrued_end_ts - strtotime($emp_accrued_start)) / 86400 + 1);
                }
            } else {
                $emp_accrued_days = max(0, ($accrued_end_ts - $start_ts) / 86400 + 1);
            }
            
            $accrued_salary += ($emp_salary / $days_in_month_basis) * $emp_accrued_days;
            $accrued_bonus += ($emp_bonus / $days_in_month_basis) * $emp_accrued_days;
        }
    }
    
    // 4. Calculate Overtime from concrete_receipts (Only for employees with role "شۆفێری میکسەر")
    $total_overtime = 0;
    $accrued_overtime = 0;
    
    // Filter employees to only those with role "شۆفێری میکسەر"
    $mixer_driver_ids = [];
    if (!empty($employee_ids)) {
        $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
        $role_check_sql = "SELECT id FROM employees WHERE id IN ($placeholders) AND role LIKE '%شۆفێری میکسەر%'";
        $stmt = $pdo->prepare($role_check_sql);
        $stmt->execute($employee_ids);
        $mixer_drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mixer_driver_ids = array_column($mixer_drivers, 'id');
    }
    
    // Only calculate if we have mixer driver employees
    if (!empty($mixer_driver_ids)) {
        $placeholders = implode(',', array_fill(0, count($mixer_driver_ids), '?'));
        
        // Full Period Overtime
        $overtime_sql = "SELECT COUNT(*) as count FROM concrete_receipts 
                         WHERE mixer_driver_id IN ($placeholders) 
                         AND COALESCE(`date`, DATE(created_at)) BETWEEN ? AND ?";
        $overtime_params = array_merge($mixer_driver_ids, [$period_start, $period_end]);
        $stmt = $pdo->prepare($overtime_sql);
        $stmt->execute($overtime_params);
        $overtime_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_overtime = intval($overtime_result['count']) * $overtime_rate;
        
        // Accrued Overtime (Up to Today)
        $accrued_overtime_sql = "SELECT COUNT(*) as count FROM concrete_receipts 
                         WHERE mixer_driver_id IN ($placeholders) 
                         AND COALESCE(`date`, DATE(created_at)) BETWEEN ? AND ?";
        $accrued_overtime_params = array_merge($mixer_driver_ids, [$period_start, $current_date]);
        $stmt = $pdo->prepare($accrued_overtime_sql);
        $stmt->execute($accrued_overtime_params);
        $accrued_overtime_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $accrued_overtime = intval($accrued_overtime_result['count']) * $overtime_rate;
    }
    
    // 5. Get Expenses (Advance, Deduction, Penalty) from employee_expenses
    // Logic: Convert start/end to Year-Months.
    $start_month = date('Y-m', strtotime($period_start));
    $end_month = date('Y-m', strtotime($period_end));
    
    $expense_where = "expense_date BETWEEN ? AND ?";
    $expense_params = [$start_month, $end_month];
    
    if ($employee_filter) {
        $expense_where .= " AND employee_id = ?";
        $expense_params[] = $employee_filter;
    }
    
    $expense_sql = "SELECT 
        SUM(CASE WHEN expense_type = 'advance' THEN amount ELSE 0 END) as total_advance,
        SUM(CASE WHEN expense_type = 'deduction' THEN amount ELSE 0 END) as total_deduction,
        SUM(CASE WHEN expense_type = 'penalty' THEN amount ELSE 0 END) as total_penalty
    FROM employee_expenses WHERE $expense_where";
    
    $stmt = $pdo->prepare($expense_sql);
    $stmt->execute($expense_params);
    $expense_summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // Accrued Deductions (Up to current month)
    $current_month_str = date('Y-m');
    $accrued_expense_where = "expense_date BETWEEN ? AND ?";
    $accrued_expense_params = [$start_month, min($end_month, $current_month_str)];
    
    if ($employee_filter) {
        $accrued_expense_where .= " AND employee_id = ?";
        $accrued_expense_params[] = $employee_filter;
    }

    $accrued_expense_sql = "SELECT 
        SUM(CASE WHEN expense_type = 'advance' THEN amount ELSE 0 END) as total_advance,
        SUM(CASE WHEN expense_type = 'deduction' THEN amount ELSE 0 END) as total_deduction,
        SUM(CASE WHEN expense_type = 'penalty' THEN amount ELSE 0 END) as total_penalty
    FROM employee_expenses WHERE $accrued_expense_where";
    
    $stmt = $pdo->prepare($accrued_expense_sql);
    $stmt->execute($accrued_expense_params);
    $accrued_expense_summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 6. Return Data
    // Get filter lists
    $employees = $pdo->query("SELECT id, name FROM employees ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $months = $pdo->query("SELECT DISTINCT expense_date FROM employee_expenses ORDER BY expense_date DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'total_salary' => round($total_salary, 2),
                'total_bonus' => round($total_bonus, 2),
                'total_overtime' => round($total_overtime, 2),
                'total_advance' => floatval($expense_summary['total_advance'] ?? 0),
                'total_deduction' => floatval($expense_summary['total_deduction'] ?? 0),
                'total_penalty' => floatval($expense_summary['total_penalty'] ?? 0),
                'days_in_period' => $days_in_period
            ],
            'accrued_summary' => [
                'total_salary' => round($accrued_salary, 2),
                'total_bonus' => round($accrued_bonus, 2),
                'total_overtime' => round($accrued_overtime, 2),
                'total_advance' => floatval($accrued_expense_summary['total_advance'] ?? 0),
                'total_deduction' => floatval($accrued_expense_summary['total_deduction'] ?? 0),
                'total_penalty' => floatval($accrued_expense_summary['total_penalty'] ?? 0)
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

