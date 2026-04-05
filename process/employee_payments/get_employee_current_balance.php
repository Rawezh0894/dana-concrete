<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    $employee_id = intval($_GET['employee_id'] ?? 0);
    $use_daily_calculation = isset($_GET['daily']) && $_GET['daily'] == '1'; // New parameter for daily calculation
    $month = $_GET['month'] ?? ''; // Format: YYYY-MM
    
    if ($employee_id <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'IDی کارمەند پێویستە'
        ]);
        exit;
    }
    
    // If daily calculation is requested, use the daily balance calculation
    if ($use_daily_calculation) {
        $month_param = $month ? '&month=' . urlencode($month) : '';
        header('Location: get_daily_balance.php?employee_id=' . $employee_id . $month_param);
        exit;
    }
    
    // Get current date for daily calculation
    $current_date = date('Y-m-d');
    $current_year = date('Y');
    $current_month = date('m');
    
    // Get employee info (with resignation_date)
    $employee_query = "SELECT id, name, salary, COALESCE(bonus, 0) as bonus, join_date, resignation_date FROM employees WHERE id = ?";
    $stmt = $pdo->prepare($employee_query);
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo json_encode([
            'success' => false,
            'error' => 'کارمەند نەدۆزرایەوە'
        ]);
        exit;
    }
    
    $current_salary = floatval($employee['salary']);
    $current_bonus = floatval($employee['bonus']);
    $join_date = $employee['join_date'];
    
    // Calculate for a specific month
    $month_to_calculate = $month ?: date('Y-m');
    $last_day_of_month = date('Y-m-t', strtotime($month_to_calculate . '-01'));

    // Try to get historical salary for this period
    $histStmt = $pdo->prepare("SELECT salary, bonus FROM employee_salary_history 
                               WHERE employee_id = ? AND effective_date <= ? 
                               ORDER BY effective_date DESC, id DESC LIMIT 1");
    $histStmt->execute([$employee_id, $last_day_of_month]);
    $history = $histStmt->fetch(PDO::FETCH_ASSOC);

    if ($history) {
        $monthly_salary = floatval($history['salary']);
        $monthly_bonus = floatval($history['bonus']);
    } else {
        $monthly_salary = $current_salary;
        $monthly_bonus = $current_bonus;
    }

    $expense_date = $month_to_calculate . '-01'; // First of month
    $year = intval(substr($month_to_calculate, 0, 4));
    $month_num = intval(substr($month_to_calculate, 5, 2));
    
    // Get days in month
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_num, $year);
    
    // Calculate days used
    if ($year == $current_year && $month_num == $current_month) {
        $end_date = $current_date;
    } else {
        $end_date = date('Y-m-t', strtotime($expense_date));
    }
    
    // Cap end_date at resignation_date if it exists
    $resignation_date = $employee['resignation_date'];
    if ($resignation_date && $resignation_date < $end_date) {
        $end_date = $resignation_date;
    }
    
    $calc_start_date = $expense_date;
    if ($join_date && $join_date > $expense_date) {
        $calc_start_date = $join_date;
    }
    
    if ($join_date && $join_date > $end_date) {
        $days_used = 0;
    } else {
        $days_used = (strtotime($end_date) - strtotime($calc_start_date)) / (60 * 60 * 24) + 1;
        if ($days_used < 0) $days_used = 0;
    }
    
    if ($days_used > $days_in_month) {
        $days_used = $days_in_month;
    }
    
    // Get expenses for this month
    $expenses_query = "
        SELECT 
            expense_type,
            amount,
            expense_date
        FROM employee_expenses
        WHERE employee_id = ? 
        AND expense_date LIKE ?
        ORDER BY created_at ASC
    ";
    
    $stmt = $pdo->prepare($expenses_query);
    $stmt->execute([$employee_id, $month_to_calculate . '%']);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize totals with daily calculation
    $total_earned_salary = 0;
    $total_earned_bonus = 0;
    $total_overtime = 0;
    $total_advance = 0;
    $total_deduction = 0;
    $total_penalty = 0;
    
    // Check if salary and bonus exist in expenses for this month
    $has_salary_in_expenses = false;
    $has_bonus_in_expenses = false;
    foreach ($expenses as $expense) {
        if ($expense['expense_type'] == 'salary') $has_salary_in_expenses = true;
        if ($expense['expense_type'] == 'bonus') $has_bonus_in_expenses = true;
    }
    
    // If no salary in expenses, use monthly salary from employees table
    if (!$has_salary_in_expenses && $monthly_salary > 0) {
        // Calculate salary based on days used in current month
        $daily_salary_rate = $days_in_month > 0 ? $monthly_salary / $days_in_month : 0;
        $total_earned_salary = $daily_salary_rate * $days_used;
    }

    // If no bonus in expenses, use monthly bonus from employees table
    if (!$has_bonus_in_expenses && $monthly_bonus > 0) {
        $daily_bonus_rate = $days_in_month > 0 ? $monthly_bonus / $days_in_month : 0;
        $total_earned_bonus = $daily_bonus_rate * $days_used;
    }
    
    // Process expenses with daily calculation
    foreach ($expenses as $expense) {
        $amount = floatval($expense['amount']);
        $expense_type = $expense['expense_type'];
        $expense_date_str = $expense['expense_date'];
        
        $expense_year = intval(substr($expense_date_str, 0, 4));
        $expense_month = intval(substr($expense_date_str, 5, 2));
        $expense_days_in_month = cal_days_in_month(CAL_GREGORIAN, $expense_month, $expense_year);
        
        if ($expense_year == $current_year && $expense_month == $current_month && $expense_date_str <= $current_date) {
            $expense_end_date = $current_date;
        } else {
            $expense_end_date = date('Y-m-t', strtotime($expense_date_str));
        }
        
        $expense_days_used = (strtotime($expense_end_date) - strtotime($expense_date_str)) / (60 * 60 * 24) + 1;
        if ($expense_days_used > $expense_days_in_month) {
            $expense_days_used = $expense_days_in_month;
        }
        
        switch ($expense_type) {
            case 'salary':
                $daily_amount = ($amount / $expense_days_in_month) * $expense_days_used;
                $total_earned_salary += $daily_amount;
                break;
            case 'bonus':
                $daily_amount = ($amount / $expense_days_in_month) * $expense_days_used;
                $total_earned_bonus += $daily_amount;
                break;
            case 'overtime':
                $total_overtime += $amount;
                break;
            case 'advance':
                $total_advance += $amount;
                break;
            case 'deduction':
                $total_deduction += $amount;
                break;
            case 'penalty':
                $total_penalty += $amount;
                break;
            case 'overtime_payment':
                $total_deduction += $amount;
                break;
        }
    }
    
    // Calculate totals
    $total_income = $total_earned_salary + $total_earned_bonus + $total_overtime;
    $total_deductions = $total_advance + $total_deduction + $total_penalty;
    $net_balance = $total_income - $total_deductions;
    
    // Calculate payable and receivable balances
    $payable_balance = max(0, $net_balance);
    $receivable_balance = max(0, -$net_balance);
    
    // Also get stored balance for comparison
    $stored_query = "
        SELECT 
            COALESCE(payable_balance, 0) as payable_balance,
            COALESCE(receivable_balance, 0) as receivable_balance,
            (COALESCE(payable_balance, 0) - COALESCE(receivable_balance, 0)) as net_balance
        FROM employees
        WHERE id = ?
    ";
    
    $stmt = $pdo->prepare($stored_query);
    $stmt->execute([$employee_id]);
    $stored = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'employee_id' => intval($employee['id']),
            'employee_name' => $employee['name'],
            'month' => $month_to_calculate,
            'days_in_month' => $days_in_month,
            'days_used' => intval($days_used),
            'payable_balance' => round($payable_balance, 2),
            'receivable_balance' => round($receivable_balance, 2),
            'net_balance' => round($net_balance, 2),
            'stored_payable_balance' => floatval($stored['payable_balance']),
            'stored_receivable_balance' => floatval($stored['receivable_balance']),
            'stored_net_balance' => floatval($stored['net_balance']),
            'total_earned_salary' => round($total_earned_salary, 2),
            'total_earned_bonus' => round($total_earned_bonus, 2),
            'total_advance' => round($total_advance, 2),
            'balance_message' => $net_balance >= 0 
                ? 'کۆمپانیا قەرزی کارمەندە: ' . number_format($net_balance, 2) . ' د.ع'
                : 'کارمەند قەرزی کۆمپانیایە: ' . number_format(abs($net_balance), 2) . ' د.ع',
            'calculation_method' => 'daily',
            'calculation_details' => [
                'monthly_salary' => number_format($monthly_salary, 2) . ' د.ع',
                'monthly_bonus' => number_format($monthly_bonus, 2) . ' د.ع',
                'days_in_month' => $days_in_month . ' ڕۆژ',
                'days_used' => intval($days_used) . ' ڕۆژ',
                'earned_salary' => number_format($total_earned_salary, 2) . ' د.ع',
                'earned_bonus' => number_format($total_earned_bonus, 2) . ' د.ع',
                'total_earned' => number_format($total_earned_salary + $total_earned_bonus, 2) . ' د.ع'
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}

