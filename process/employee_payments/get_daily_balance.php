<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    $employee_id = intval($_GET['employee_id'] ?? 0);
    $month = $_GET['month'] ?? ''; // Format: YYYY-MM
    
    if ($employee_id <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'IDی کارمەند پێویستە'
        ]);
        exit;
    }
    
    // Get current date
    $current_date = date('Y-m-d');
    
    // If month is provided, use it; otherwise use current month
    if ($month) {
        $expense_date = $month . '-01';
        $year = intval(substr($month, 0, 4));
        $month_num = intval(substr($month, 5, 2));
        
        // If it's current month, use current date; otherwise use last day of month
        if ($year == date('Y') && $month_num == date('m')) {
            $end_date = $current_date;
        } else {
            $end_date = date('Y-m-t', strtotime($expense_date)); // Last day of month
        }
    } else {
        $expense_date = date('Y-m-01');
        $year = date('Y');
        $month_num = date('m');
        $end_date = $current_date;
    }
    
    // Get days in month
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_num, $year);
    
    // Calculate days used (from first day of month to end_date)
    $start_date = $expense_date;
    $days_used = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
    
    // Ensure days_used doesn't exceed days_in_month
    if ($days_used > $days_in_month) {
        $days_used = $days_in_month;
    }
    
    // Get employee info
    $employee_query = "SELECT id, name, salary FROM employees WHERE id = ?";
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
    $stmt->execute([$employee_id, $month ? $month . '%' : date('Y-m') . '%']);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate daily rates
    $daily_salary_rate = $days_in_month > 0 ? $monthly_salary / $days_in_month : 0;
    
    // Initialize totals
    $total_earned_salary = 0;
    $total_bonus = 0;
    $total_overtime = 0;
    $total_advance = 0;
    $total_deduction = 0;
    $total_penalty = 0;
    
    // Process expenses
    foreach ($expenses as $expense) {
        $amount = floatval($expense['amount']);
        $expense_type = $expense['expense_type'];
        $expense_date_str = $expense['expense_date'];
        
        // Calculate days used for this specific expense
        // If expense is in current month and we're calculating for current month, use current date
        $expense_year = intval(substr($expense_date_str, 0, 4));
        $expense_month = intval(substr($expense_date_str, 5, 2));
        
        if ($expense_year == date('Y') && $expense_month == date('m') && $expense_date_str <= $current_date) {
            $expense_end_date = $current_date;
        } else {
            $expense_end_date = date('Y-m-t', strtotime($expense_date_str));
        }
        
        $expense_days_used = (strtotime($expense_end_date) - strtotime($expense_date_str)) / (60 * 60 * 24) + 1;
        $expense_days_in_month = cal_days_in_month(CAL_GREGORIAN, $expense_month, $expense_year);
        
        if ($expense_days_used > $expense_days_in_month) {
            $expense_days_used = $expense_days_in_month;
        }
        
        switch ($expense_type) {
            case 'salary':
                // Calculate proportional salary based on days used
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
                // Calculate proportional advance based on days used
                $daily_amount = ($amount / $expense_days_in_month) * $expense_days_used;
                $total_advance += $daily_amount;
                break;
            case 'deduction':
                $total_deduction += $amount;
                break;
            case 'penalty':
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
    
    echo json_encode([
        'success' => true,
        'data' => [
            'employee_id' => $employee_id,
            'employee_name' => $employee['name'],
            'month' => $month ?: date('Y-m'),
            'monthly_salary' => $monthly_salary,
            'days_in_month' => $days_in_month,
            'days_used' => intval($days_used),
            'daily_salary_rate' => round($daily_salary_rate, 2),
            'total_earned_salary' => round($total_earned_salary, 2),
            'total_bonus' => round($total_bonus, 2),
            'total_overtime' => round($total_overtime, 2),
            'total_advance' => round($total_advance, 2),
            'total_deduction' => round($total_deduction, 2),
            'total_penalty' => round($total_penalty, 2),
            'total_income' => round($total_income, 2),
            'total_deductions' => round($total_deductions, 2),
            'net_balance' => round($net_balance, 2),
            'payable_balance' => round($payable_balance, 2),
            'receivable_balance' => round($receivable_balance, 2),
            'balance_message' => $net_balance >= 0 
                ? 'کۆمپانیا قەرزی کارمەندە: ' . number_format($net_balance, 2) . ' د.ع'
                : 'کارمەند قەرزی کۆمپانیایە: ' . number_format(abs($net_balance), 2) . ' د.ع',
            'calculation_details' => [
                'salary_calculation' => $total_earned_salary > 0 
                    ? number_format($monthly_salary, 2) . ' د.ع ÷ ' . $days_in_month . ' ڕۆژ × ' . intval($days_used) . ' ڕۆژ = ' . number_format($total_earned_salary, 2) . ' د.ع'
                    : '0 د.ع',
                'advance_calculation' => $total_advance > 0
                    ? 'پێشەکی بە پێی ڕۆژەکان: ' . number_format($total_advance, 2) . ' د.ع'
                    : '0 د.ع'
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

