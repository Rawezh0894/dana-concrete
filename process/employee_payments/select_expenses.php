<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    $month_filter = $_GET['month'] ?? '';
    $employee_filter = $_GET['employee'] ?? '';
    
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if ($start_date || $end_date) {
        if ($start_date) {
            $start_month = date('Y-m', strtotime($start_date));
            $where_conditions[] = "ee.expense_date >= ?";
            $params[] = $start_month;
        }
        if ($end_date) {
            $end_month = date('Y-m', strtotime($end_date));
            $where_conditions[] = "ee.expense_date <= ?";
            $params[] = $end_month;
        }
    } elseif ($month_filter) {
        $where_conditions[] = "ee.expense_date LIKE ?";
        $params[] = $month_filter . '%';
    }
    
    if ($employee_filter) {
        $where_conditions[] = "ee.employee_id = ?";
        $params[] = $employee_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "
        SELECT 
            ee.id,
            ee.employee_id,
            e.name as employee_name,
            e.salary as monthly_salary,
            ee.expense_type,
            ee.amount,
            ee.notes,
            ee.expense_date,
            ee.created_at,
            u.username as created_by_name,
            COALESCE(e.payable_balance, 0) as employee_payable_balance,
            COALESCE(e.receivable_balance, 0) as employee_receivable_balance
        FROM employee_expenses ee
        LEFT JOIN employees e ON ee.employee_id = e.id
        LEFT JOIN users u ON ee.created_by = u.id
        $where_clause
        ORDER BY ee.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current date for daily calculation
    $current_date = date('Y-m-d');
    $current_year = date('Y');
    $current_month = date('m');
    
    // Translate expense types to Kurdish and calculate daily balance
    $expense_types_kurdish = [
        'salary' => 'مووچە',
        'bonus' => 'بەخشیش',
        'overtime' => 'کاروانحیسابی',
        'advance' => 'پێشەکی',
        'deduction' => 'کەمکردنەوە',
        'penalty' => 'سزا',
        'overtime_payment' => 'کاروان حیسابی (پێدان)'
    ];
    
    // Group expenses by employee_id and expense_date to calculate cumulative balance
    $employee_balances = [];
    
    foreach ($expenses as &$expense) {
        $expense['expense_type_kurdish'] = $expense_types_kurdish[$expense['expense_type']] ?? $expense['expense_type'];
        
        $employee_id = $expense['employee_id'];
        $expense_date_str = $expense['expense_date'];
        $expense_year = intval(substr($expense_date_str, 0, 4));
        $expense_month = intval(substr($expense_date_str, 5, 2));
        
        // Initialize employee balance if not exists
        if (!isset($employee_balances[$employee_id])) {
            $employee_balances[$employee_id] = [
                'monthly_salary' => floatval($expense['monthly_salary'] ?? 0),
                'expenses' => []
            ];
        }
        
        // Calculate days for this expense
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
        
        // Store expense for cumulative calculation
        $employee_balances[$employee_id]['expenses'][] = [
            'type' => $expense['expense_type'],
            'amount' => floatval($expense['amount']),
            'expense_date' => $expense_date_str,
            'days_in_month' => $expense_days_in_month,
            'days_used' => $expense_days_used,
            'created_at' => $expense['created_at']
        ];
    }
    
    // Calculate daily balance for each employee up to each expense
    foreach ($expenses as &$expense) {
        $employee_id = $expense['employee_id'];
        $expense_date_str = $expense['expense_date'];
        $expense_created_at = $expense['created_at'];
        
        if (!isset($employee_balances[$employee_id])) {
            continue;
        }
        
        $monthly_salary = $employee_balances[$employee_id]['monthly_salary'];
        $expense_list = $employee_balances[$employee_id]['expenses'];
        
        // Calculate balance up to this expense (including this expense)
        $total_earned_salary = 0;
        $total_bonus = 0;
        $total_overtime = 0;
        $total_advance = 0;
        $total_deduction = 0;
        $total_penalty = 0;
        
        // Check if salary exists in expenses before this one
        $has_salary = false;
        foreach ($expense_list as $exp) {
            if ($exp['type'] == 'salary' && $exp['created_at'] <= $expense_created_at) {
                $has_salary = true;
                break;
            }
        }
        
        // If no salary in expenses, use monthly salary
        if (!$has_salary && $monthly_salary > 0) {
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
            
            $daily_salary_rate = $expense_days_in_month > 0 ? $monthly_salary / $expense_days_in_month : 0;
            $total_earned_salary = $daily_salary_rate * $expense_days_used;
        }
        
        // Process expenses up to this one
        foreach ($expense_list as $exp) {
            if ($exp['created_at'] > $expense_created_at) {
                continue; // Skip expenses after this one
            }
            
            $amount = $exp['amount'];
            $exp_type = $exp['type'];
            $exp_days_in_month = $exp['days_in_month'];
            $exp_days_used = $exp['days_used'];
            
            switch ($exp_type) {
                case 'salary':
                    $daily_amount = ($amount / $exp_days_in_month) * $exp_days_used;
                    $total_earned_salary += $daily_amount;
                    break;
                case 'bonus':
                    $total_bonus += $amount;
                    break;
                case 'overtime':
                    $total_overtime += $amount;
                    break;
                case 'advance':
                    // وەرگرتن بە تەواوی
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
        
        // Calculate net balance
        $total_income = $total_earned_salary + $total_bonus + $total_overtime;
        $total_deductions = $total_advance + $total_deduction + $total_penalty;
        $net_balance = $total_income - $total_deductions;
        
        // Calculate payable and receivable
        $payable_balance = max(0, $net_balance);
        $receivable_balance = max(0, -$net_balance);
        
        // Update expense with calculated balance
        $expense['employee_payable_balance'] = round($payable_balance, 2);
        $expense['employee_receivable_balance'] = round($receivable_balance, 2);
    }
    
    echo json_encode($expenses);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}

