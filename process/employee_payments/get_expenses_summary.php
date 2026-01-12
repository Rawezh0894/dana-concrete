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
    
    // For monthly salary calculation, use the days in the specific month of the period start.
    $days_in_month_basis = cal_days_in_month(CAL_GREGORIAN, date('m', $start_ts), date('Y', $start_ts));
    
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

    // Check if join_date column exists
    $join_date_exists = false;
    try {
        $check_join = $pdo->query("SHOW COLUMNS FROM employees LIKE 'join_date'");
        $join_date_exists = $check_join->rowCount() > 0;
    } catch (Exception $e) {}

    $emp_params = [];
    $columns = "id, salary, COALESCE(bonus, 0) as bonus";
    if ($join_date_exists) {
        $columns .= ", join_date";
    } else {
        $columns .= ", NULL as join_date";
    }
    
    $emp_sql = "SELECT $columns FROM employees WHERE 1=1";
    
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
    
    $today = date('Y-m-d');
    
    foreach ($employees_data as $emp) {
        $employee_ids[] = $emp['id'];
        $emp_salary = floatval($emp['salary']);
        $emp_bonus = floatval($emp['bonus']);
        $join_date = $emp['join_date'];
        
        // Calculate work days for THIS employee in THIS period
        $work_start = $period_start;
        if ($join_date && $join_date > $period_start) {
            $work_start = $join_date;
        }
        
        // If join_date is after period_end, they earned 0
        if ($join_date && $join_date > $period_end) {
            continue;
        }
        
        $work_end = $period_end;
        // If it's the current month and we want "earned so far", we might want to cap at today?
        // But usually "Monthly Summary" means full month expectation if looking at future.
        // However, the user's "Daily Balance" request suggests they want real-time accrual.
        // For the main "Total Salary" card, let's keep it for the whole selected period.
        
        $emp_start_ts = strtotime($work_start);
        $emp_end_ts = strtotime($work_end);
        
        if ($emp_start_ts <= $emp_end_ts) {
            $emp_days = ($emp_end_ts - $emp_start_ts) / 86400 + 1;
            
            // Daily rate based on month basis (already calculated $days_in_month_basis)
            $emp_prorate = $emp_days / $days_in_month_basis;
            
            $total_salary += $emp_salary * $emp_prorate;
            $total_bonus += $emp_bonus * $emp_prorate;
        }
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
    $expense_conditions = ["expense_date BETWEEN ? AND ?"];
    $expense_params = [$period_start, $period_end]; // Note: expense_date is YYYY-MM usually? 
    // Wait, employee_expenses 'expense_date' in database is usually 'YYYY-MM-DD' or 'YYYY-MM'?
    // In add_expense.js (previous turn), it was input type="month". 
    // Let's check the database structure or previous code.
    // In employee_expenses.php: <input type="month" ... name="expense_date">
    // This implies expense_date is YYYY-MM string in DB?
    // BUT `get_employee_current_balance.php` uses: `WHERE expense_date LIKE ?` with `month%`.
    // And `select_expenses.php` uses `expense_date = ?` for month filter.
    // This strongly suggests `expense_date` is stored as `YYYY-MM`.
    
    // IF `expense_date` is YYYY-MM, we cannot filter by specific days (start/end date).
    // The user added Start Date / End Date.
    // If the data is only stored as YYYY-MM, we can only filter by Month.
    // However, the `select_expenses.php` I just updated used logic `expense_date >= ?`.
    // If `expense_date` is `2024-01`, and start date is `2024-01-15`, `2024-01 >= 2024-01-15` string comparison? 
    // "2024-01" is NOT >= "2024-01-15".
    // This implies my previous edit to `select_expenses.php` might be flawed if `expense_date` is YYYY-MM.
    
    // Let's check `process/employee_payments/select_expenses.php` line 33: `ee.expense_date`.
    // In `add_income_expense_modal`: input type="month".
    // It seems `expense_date` stores strings like "2024-01".
    
    // ISSUE: The user wants precise date range filtering.
    // But if expenses are stored by Month, we can't filter by Day.
    // Unless we use `created_at`.
    // `select_expenses.php` select also has `ee.created_at`.
    // The new requirement uses `start_date` and `end_date`.
    // If I use `expense_date` (YYYY-MM), I can only match months.
    
    // But wait, "Advance/Deduction/Penalty" cards.
    // If I select "Jan 1 to Jan 15".
    // And I have an Advance recorded for "Jan 2024" (stored as 2024-01).
    // Should it be included?
    // Usually yes.
    // But if I want to filter *transactions*, maybe `created_at` date?
    // User Instructions: "Filter by date from - to".
    // If the system was designed with Month-based expenses, adding Day-based filtering is tricky.
    // However, I will check if `expense_date` can store full dates.
    // In `employee_expenses.php`: `input type="month"`.
    // This forces YYYY-MM.
    // So expenses are Monthly.
    
    // Decision: For "Advance/Deduction/Penalty", if the requested range overlaps with the month, include it?
    // Or just filter based on `expense_date` matching the months in the range?
    // E.g. Start: 2024-01-15, End: 2024-02-10.
    // Include 2024-01 and 2024-02.
    // This is the best reasonable interpretation.
    
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
    
    // 6. Calculate Daily Balance (Earned so far this month/period)
    $total_earned_to_date = 0;
    foreach ($employees_data as $emp) {
        $emp_salary = floatval($emp['salary']);
        $emp_bonus = floatval($emp['bonus']);
        $join_date = $emp['join_date'];
        
        $month_start = date('Y-m-01', $start_ts);
        $month_end_actual = date('Y-m-t', $start_ts);
        $calc_until = min($today, $month_end_actual);
        
        $work_start = $month_start;
        if ($join_date && $join_date > $month_start) {
            $work_start = $join_date;
        }
        
        if ($join_date && $join_date > $calc_until) {
            continue;
        }
        
        $emp_start_ts = strtotime($work_start);
        $emp_end_ts = strtotime($calc_until);
        
        if ($emp_start_ts <= $emp_end_ts) {
            $emp_days = ($emp_end_ts - $emp_start_ts) / 86400 + 1;
            $month_days = cal_days_in_month(CAL_GREGORIAN, date('m', $start_ts), date('Y', $start_ts));
            
            $rate = ($emp_salary + $emp_bonus) / $month_days;
            $total_earned_to_date += $rate * $emp_days;
        }
    }
    // Add overtime to earned to date? Yes, overtime is usually earned per task.
    // The $total_overtime is already filtered by date range.
    // If range is current month, it might include future? No, receipts are in past/present.
    $total_earned_to_date += $total_overtime;

    // 7. Return Data
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
                'total_earned_to_date' => round($total_earned_to_date, 2),
                'days_in_period' => $days_in_period
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

