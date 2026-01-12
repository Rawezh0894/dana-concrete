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
    
    // Get employee info
    $employee_query = "SELECT id, name, salary, join_date FROM employees WHERE id = ?";
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
    
    $monthly_salary = floatval($employee['salary']);
    $join_date = $employee['join_date'] ?? null;
    
    // Calculate daily balance for current month
    $month_to_calculate = $month ?: date('Y-m');
    $expense_date = $month_to_calculate . '-01';
    
    // ADJUST START DATE BASED ON JOIN DATE
    $calc_start_date = $expense_date;
    if ($join_date && $join_date > $expense_date) {
        // If employee joined in this month or later
        if (substr($join_date, 0, 7) == $month_to_calculate) {
            $calc_start_date = $join_date;
        } elseif ($join_date > date('Y-m-t', strtotime($expense_date))) {
            // Joined after this month
            $calc_start_date = null; 
        }
    }

    if ($calc_start_date === null) {
        $days_used = 0;
    } else {
        $year = intval(substr($month_to_calculate, 0, 4));
        $month_num = intval(substr($month_to_calculate, 5, 2));
        
        // Get days in month
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_num, $year);
        
        // Calculate days used
        if ($year == $current_year && $month_num == $current_month) {
            $current_month_end = $current_date;
        } else {
            $current_month_end = date('Y-m-t', strtotime($expense_date));
        }
        
        // Days used = (End Date - Start Date) + 1
        $days_used = (strtotime($current_month_end) - strtotime($calc_start_date)) / (60 * 60 * 24) + 1;
        if ($days_used < 0) $days_used = 0;
        if ($days_used > $days_in_month) {
            $days_used = $days_in_month;
        }
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
    $total_bonus = 0;
    $total_overtime = 0;
    $total_advance = 0;
    $total_deduction = 0;
    $total_penalty = 0;
    
    // Check if salary exists in expenses for this month
    $has_salary_in_expenses = false;
    foreach ($expenses as $expense) {
        if ($expense['expense_type'] == 'salary') {
            $has_salary_in_expenses = true;
            break;
        }
    }
    
    // If no salary in expenses, use monthly salary from employees table
    if (!$has_salary_in_expenses && $monthly_salary > 0) {
        // Calculate salary based on days used in current month
        $daily_salary_rate = $days_in_month > 0 ? $monthly_salary / $days_in_month : 0;
        $total_earned_salary = $daily_salary_rate * $days_used;
    }
    
    // Process expenses with daily calculation
    foreach ($expenses as $expense) {
        $amount = floatval($expense['amount']);
        $expense_type = $expense['expense_type'];
        $expense_date_str = $expense['expense_date'];
        
        // Calculate days used for this specific expense
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
                // Calculate proportional salary based on days used
                // مووچە بە شێوەی ڕۆژانە هەژمار دەکرێت
                $daily_amount = ($amount / $expense_days_in_month) * $expense_days_used;
                $total_earned_salary += $daily_amount;
                break;
            case 'bonus':
                $total_bonus += $amount;
                break;
            case 'overtime':
                $total_overtime += $amount;
                break;
            case 'advance':
                // وەرگرتن/پێشەکی بە تەواوی وەردەگیرێت (نەک بە شێوەی ڕۆژانە)
                // چونکە وەرگرتن بە تەواوی دەدرێت بە کارمەند
                $total_advance += $amount;
                break;
            case 'deduction':
                // کەمکردنەوە بە تەواوی
                $total_deduction += $amount;
                break;
            case 'penalty':
                // سزا بە تەواوی
                $total_penalty += $amount;
                break;
        }
    }
    
    // Calculate totals
    $total_income = $total_earned_salary + $total_bonus + $total_overtime;
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
            'total_advance' => round($total_advance, 2),
            'balance_message' => $net_balance >= 0 
                ? 'کۆمپانیا قەرزی کارمەندە: ' . number_format($net_balance, 2) . ' د.ع'
                : 'کارمەند قەرزی کۆمپانیایە: ' . number_format(abs($net_balance), 2) . ' د.ع',
            'calculation_method' => 'daily',
            'calculation_details' => [
                'monthly_salary' => number_format($monthly_salary, 2) . ' د.ع',
                'days_in_month' => $days_in_month . ' ڕۆژ',
                'days_used' => intval($days_used) . ' ڕۆژ',
                'daily_salary_rate' => number_format($monthly_salary / $days_in_month, 2) . ' د.ع/ڕۆژ',
                'earned_salary' => number_format($total_earned_salary, 2) . ' د.ع',
                'earned_salary_formula' => number_format($monthly_salary, 2) . ' ÷ ' . $days_in_month . ' × ' . intval($days_used) . ' = ' . number_format($total_earned_salary, 2),
                'advance_taken' => number_format($total_advance, 2) . ' د.ع',
                'advance_note' => 'وەرگرتن/پێشەکی بە تەواوی وەردەگیرێت (نەک بە شێوەی ڕۆژانە)'
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}

