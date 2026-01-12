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
    
    // Calculate days in period
    $start_ts = strtotime($period_start);
    $end_ts = strtotime($period_end);
    $days_in_period = max(1, ($end_ts - $start_ts) / 86400 + 1);
    
    // For monthly salary calculation, we generally assume 30 days for normalization
    // or use the days in the specific month if a single month is selected.
    $days_in_month_basis = 30;
    if ($month_filter) {
       $days_in_month_basis = cal_days_in_month(CAL_GREGORIAN, date('m', $start_ts), date('Y', $start_ts));
    } elseif (!$start_date && !$end_date) {
       $days_in_month_basis = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
    }
    
    $prorate_factor = $days_in_period / $days_in_month_basis;
    
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
    $emp_sql = "SELECT id, salary, COALESCE(bonus, 0) as bonus FROM employees WHERE 1=1";
    
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
    $employee_ids = [];
    
    foreach ($employees_data as $emp) {
        $employee_ids[] = $emp['id'];
        $total_salary += floatval($emp['salary']) * $prorate_factor;
        $total_bonus += floatval($emp['bonus']) * $prorate_factor;
    }
    
    // 4. Calculate Overtime from concrete_receipts (Only for employees with role "شۆفێری میکسەر")
    $total_overtime = 0;
    
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
         // Build employee ID list for IN clause
        $placeholders = implode(',', array_fill(0, count($mixer_driver_ids), '?'));
        
        // Check if 'date' column exists, otherwise fallback to 'created_at'
        // We assume 'date' exists based on pages/concrete_receipts.php
        $overtime_sql = "SELECT COUNT(*) as count FROM concrete_receipts 
                         WHERE mixer_driver_id IN ($placeholders) 
                         AND `date` BETWEEN ? AND ?";
                         
        $overtime_params = array_merge($mixer_driver_ids, [$period_start, $period_end]);
        
        try {
            // Use COALESCE to fallback to created_at date if date column is NULL
            // This handles cases where receipts are added without an explicit date (defaulting to created_at)
            $overtime_sql = "SELECT COUNT(*) as count FROM concrete_receipts 
                         WHERE mixer_driver_id IN ($placeholders) 
                         AND COALESCE(`date`, DATE(created_at)) BETWEEN ? AND ?";
                         
            $overtime_params = array_merge($mixer_driver_ids, [$period_start, $period_end]);
            
            $stmt = $pdo->prepare($overtime_sql);
            $stmt->execute($overtime_params);
            $overtime_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_overtime = intval($overtime_result['count']) * $overtime_rate;
        } catch (Exception $e) {
            // Fallback to created_at if date column fails
             $overtime_sql = "SELECT COUNT(*) as count FROM concrete_receipts 
                         WHERE mixer_driver_id IN ($placeholders) 
                         AND created_at BETWEEN ? AND ?";
             // Adjust end date to cover the full day
             $overtime_params = array_merge($mixer_driver_ids, [$period_start . ' 00:00:00', $period_end . ' 23:59:59']);
             $stmt = $pdo->prepare($overtime_sql);
             $stmt->execute($overtime_params);
             $overtime_result = $stmt->fetch(PDO::FETCH_ASSOC);
             $total_overtime = intval($overtime_result['count']) * $overtime_rate;
        }
    }
    
    // 5. Get Expenses (Advance, Deduction, Penalty) from employee_expenses
    
    // --- STANDARD FILTERED EXPENSES (Existing Logic) ---
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

    // --- LIFETIME / JOIN_DATE BASED CALCULATION (New Request) ---
    // Calculate total accrued salary based on join_date for "Net Pay" and "Daily Balance"
    
    // Get join_date column existence
    $join_date_exists = false;
    try {
        $check_join = $pdo->query("SHOW COLUMNS FROM employees LIKE 'join_date'");
        $join_date_exists = $check_join->rowCount() > 0;
    } catch (Exception $e) {}

    // Get created_at column existence
    $created_at_exists = false;
    try {
        $check_created = $pdo->query("SHOW COLUMNS FROM employees LIKE 'created_at'");
        $created_at_exists = $check_created->rowCount() > 0;
    } catch (Exception $e) {}

    $lifetime_salary = 0;
    $lifetime_bonus = 0;
    
    // Re-fetch employees with join_date
    $emp_lifetime_sql = "SELECT id, salary, COALESCE(bonus, 0) as bonus";
    
    if ($join_date_exists) {
        $emp_lifetime_sql .= ", join_date";
    } else {
        $emp_lifetime_sql .= ", NULL as join_date";
    }

    if ($created_at_exists) {
        $emp_lifetime_sql .= ", created_at";
    } else {
        $emp_lifetime_sql .= ", NULL as created_at";
    }
    
    $emp_lifetime_sql .= " FROM employees WHERE 1=1";
    
    if ($status_exists) {
        $emp_lifetime_sql .= " AND status = 'active'";
    }
    if ($employee_filter) {
        $emp_lifetime_sql .= " AND id = " . intval($employee_filter);
    }
    
    $stmt = $pdo->query($emp_lifetime_sql);
    $all_employees_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_employees_data as $emp) {
        // Determine start date for calculation
        $start_calculation_date = $emp['join_date'];
        
        if (empty($start_calculation_date) && !empty($emp['created_at'])) {
            // Fallback to created_at if join_date is empty and created_at exists
             $start_calculation_date = date('Y-m-d', strtotime($emp['created_at']));
        }
        
        if ($start_calculation_date) {
            $days_worked = max(0, (time() - strtotime($start_calculation_date)) / 86400); // Days since join
            
            // Calculate daily rate (Salary / 30)
            $daily_salary = floatval($emp['salary']) / 30;
            $daily_bonus = floatval($emp['bonus']) / 30;
            
            $lifetime_salary += $daily_salary * $days_worked;
            $lifetime_bonus += $daily_bonus * $days_worked;
        }
    }
    
    // Calculate Total LIFETIME Expenses (Total Advance + Deduction + Penalty EVER)
    $lifetime_expense_where = "1=1";
    $lifetime_expense_params = [];
    
    if ($employee_filter) {
        $lifetime_expense_where .= " AND employee_id = ?";
        $lifetime_expense_params[] = $employee_filter;
    }
    
    $lifetime_expense_sql = "SELECT 
        SUM(amount) as total_expenses
    FROM employee_expenses WHERE $lifetime_expense_where";
    
    $stmt = $pdo->prepare($lifetime_expense_sql);
    $stmt->execute($lifetime_expense_params);
    $lifetime_expenses_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_lifetime_expenses = floatval($lifetime_expenses_result['total_expenses'] ?? 0);
    
    // Calculate Net Lifetime Balance
    // Note: We should strictly add 'Lifetime Overtime' too if it's significant. 
    // Calculating lifetime overtime might be heavy (scanning all receipts forever), but let's try.
    
    $lifetime_overtime = 0;
    // ... logic for lifetime overtime (similar to above but without date restriction)
    // For simplicity/speed, let's reuse the logic but with wide date range if safe, OR assume overtime is small part.
    // However, for correctness, we should include it.
    
    if (!empty($mixer_driver_ids) && $employee_filter) { // Optimize: only if specific employee or we query efficiently
         // Re-using mixer driver IDs logic from earlier
        $placeholders = implode(',', array_fill(0, count($mixer_driver_ids), '?'));
        // Wide range: 2000-01-01 to NOW
        $ot_sql = "SELECT COUNT(*) as count FROM concrete_receipts WHERE mixer_driver_id IN ($placeholders)";
        $ot_stmt = $pdo->prepare($ot_sql);
        $ot_stmt->execute($mixer_driver_ids);
        $ot_res = $ot_stmt->fetch(PDO::FETCH_ASSOC);
        $lifetime_overtime = intval($ot_res['count']) * $overtime_rate;
    } else if (!empty($mixer_driver_ids)) {
         // All mixer drivers
         $placeholders = implode(',', array_fill(0, count($mixer_driver_ids), '?'));
         $ot_sql = "SELECT COUNT(*) as count FROM concrete_receipts WHERE mixer_driver_id IN ($placeholders)";
         $ot_stmt = $pdo->prepare($ot_sql);
         $ot_stmt->execute($mixer_driver_ids);
         $ot_res = $ot_stmt->fetch(PDO::FETCH_ASSOC);
         $lifetime_overtime = intval($ot_res['count']) * $overtime_rate;
    }
    
    $lifetime_balance = ($lifetime_salary + $lifetime_bonus + $lifetime_overtime) - $total_lifetime_expenses;
    $lifetime_daily_balance = $lifetime_balance; // Since it's calculated based on daily rates
    
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
                'days_in_period' => $days_in_period,
                'net_pay_lifetime' => round($lifetime_balance, 2),
                'daily_balance_lifetime' => round($lifetime_daily_balance, 2)
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

