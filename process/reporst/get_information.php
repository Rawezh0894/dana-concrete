<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    // Get exchange rate from API
    $usd_iqd_rate = 139250; // Default fallback value
    
    // Function to fetch dollar rate from API
    function fetchDollarRateFromAPI() {
        $apiUrl = 'https://dinarapi.hediworks.site/api/get-price';
        $apiToken = 'S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
        $id = '8'; // 100 dollar ID
        
        $url = $apiUrl . '?id=' . $id . '&api_token=' . $apiToken;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'DanaConcrete/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['value']) && is_numeric($data['value'])) {
                return floatval($data['value']);
            }
        }
        
        return null;
    }
    
    // Try to get rate from API
    $api_rate = fetchDollarRateFromAPI();
    if ($api_rate !== null) {
        $usd_iqd_rate = $api_rate;
    }
    
    // Debug: Log the rate being used
    error_log("Using USD/IQD rate: " . $usd_iqd_rate);

    // Customers - Calculate debt using new method (opening_debt + remaining from sales)
    $customer_debt_query = "
        SELECT 
            SUM(c.opening_debt_usd) as opening_debt_usd,
            SUM(c.opening_debt_iqd) as opening_debt_iqd,
            COALESCE(SUM(s.remaining_amount), 0) as remaining_from_sales
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_type = 'قەرز'
    ";
    $stmt = $pdo->query($customer_debt_query);
    $row = $stmt->fetch();
    $customer_debt_usd = floatval($row['opening_debt_usd'] ?? 0) + floatval($row['remaining_from_sales'] ?? 0);
    $customer_debt_iqd = floatval($row['opening_debt_iqd'] ?? 0);
    $customer_debt_iqd_converted = ($usd_iqd_rate > 0) ? ($customer_debt_iqd / ($usd_iqd_rate / 100)) : 0;
    $customer_debt_total_usd = $customer_debt_usd + $customer_debt_iqd_converted;

    // Companies - Calculate debt using same method as get_summary_stats.php
    // Get opening debt from companies
    $openingDebt = $pdo->query("SELECT SUM(opening_debt_usd) as usd, SUM(opening_debt_iqd) as iqd FROM company")->fetch();
    
    // Get remaining debt from purchases with their individual exchange rates
    $remainingDebt = $pdo->query("
        SELECT 
            SUM(remaining_usd) as usd, 
            SUM(remaining_iqd) as iqd,
            SUM(remaining_iqd / NULLIF(exchange_rate / 100, 0)) as iqd_converted
        FROM purchases 
        WHERE payment_type = 'قەرز'
    ")->fetch();
    
    // Calculate total debt
    $company_debt_total_usd = floatval($openingDebt['usd'] ?? 0) + floatval($remainingDebt['usd'] ?? 0);
    $company_debt_total_usd += floatval($remainingDebt['iqd_converted'] ?? 0); // Add converted IQD amount
    
    // For opening debt IQD, use the latest exchange rate from purchases
    $latestRate = $pdo->query("
        SELECT exchange_rate 
        FROM purchases 
        WHERE exchange_rate > 0 
        ORDER BY date DESC, id DESC 
        LIMIT 1
    ")->fetchColumn();
    
    $usdRate = $latestRate ?: 139250; // Fallback to default if no purchases exist
    $company_debt_total_usd += (floatval($openingDebt['iqd'] ?? 0) / ($usdRate / 100));

    // Other expense persons - Calculate total debt (opening debt + expenses - payments)
    $person_debt_query = "
        SELECT 
            SUM(p.opening_debt_usd) as opening_debt_usd,
            SUM(p.opening_debt_iqd) as opening_debt_iqd,
            COALESCE(SUM(oe.amount_usd), 0) as total_expenses_usd,
            COALESCE(SUM(oe.amount_iqd), 0) as total_expenses_iqd,
            COALESCE(SUM(oe.amount_iqd / NULLIF(oe.exchange_rate / 100, 0)), 0) as total_expenses_iqd_converted,
            COALESCE(SUM(pedp.amount_usd), 0) as total_payments_usd,
            COALESCE(SUM(pedp.amount_iqd), 0) as total_payments_iqd
        FROM other_expense_persons p
        LEFT JOIN other_expenses oe ON p.id = oe.person_id
        LEFT JOIN person_other_expenses_debt_payments pedp ON p.id = pedp.person_id
    ";
    $stmt = $pdo->query($person_debt_query);
    $row = $stmt->fetch();
    
    $opening_debt_usd = floatval($row['opening_debt_usd'] ?? 0);
    $opening_debt_iqd = floatval($row['opening_debt_iqd'] ?? 0);
    $total_expenses_usd = floatval($row['total_expenses_usd'] ?? 0);
    $total_expenses_iqd = floatval($row['total_expenses_iqd'] ?? 0);
    $total_expenses_iqd_converted = floatval($row['total_expenses_iqd_converted'] ?? 0);
    $total_payments_usd = floatval($row['total_payments_usd'] ?? 0);
    $total_payments_iqd = floatval($row['total_payments_iqd'] ?? 0);
    
    // Calculate total debt: opening debt + expenses - payments
    $person_debt_usd = $opening_debt_usd + $total_expenses_usd + $total_expenses_iqd_converted - $total_payments_usd;
    $person_debt_iqd = $opening_debt_iqd + $total_expenses_iqd - $total_payments_iqd;
    $person_debt_iqd_converted = ($usd_iqd_rate > 0) ? ($person_debt_iqd / ($usd_iqd_rate / 100)) : 0;
    $person_debt_total_usd = $person_debt_usd + $person_debt_iqd_converted;

    // Purchases (کڕین)
    $purchases = [
        'cash' => ['usd' => 0, 'iqd' => 0],
        'credit' => ['usd' => 0, 'iqd' => 0]
    ];
    // دینار
    $filter = $_GET['filter'] ?? 'year';
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $use_range = ($from_date || $to_date);
    // Date conditions for different tables
    $date_condition_sales = "";
    $date_condition_employee_payments = "";
    $date_condition_date = "";
    
    if ($use_range) {
        $from = $from_date ? $from_date : '1000-01-01';
        $to = $to_date ? $to_date : '9999-12-31';
        $date_condition_sales = " AND order_date >= '$from' AND order_date <= '$to'";
        $date_condition_employee_payments = " AND DATE(created_at) >= '$from' AND DATE(created_at) <= '$to'";
        $date_condition_date = " AND date >= '$from' AND date <= '$to'";
    } else {
        if ($filter === 'today') {
            $date_condition_sales = " AND order_date = CURDATE()";
            $date_condition_employee_payments = " AND DATE(created_at) = CURDATE()";
            $date_condition_date = " AND date = CURDATE()";
        } elseif ($filter === 'week') {
            $date_condition_sales = " AND YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1)";
            $date_condition_employee_payments = " AND YEARWEEK(DATE(created_at), 1) = YEARWEEK(CURDATE(), 1)";
            $date_condition_date = " AND YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($filter === 'month') {
            $date_condition_sales = " AND YEAR(order_date) = YEAR(CURDATE()) AND MONTH(order_date) = MONTH(CURDATE())";
            $date_condition_employee_payments = " AND YEAR(DATE(created_at)) = YEAR(CURDATE()) AND MONTH(DATE(created_at)) = MONTH(CURDATE())";
            $date_condition_date = " AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())";
        } elseif ($filter === 'year') {
            $date_condition_sales = " AND YEAR(order_date) = YEAR(CURDATE())";
            $date_condition_employee_payments = " AND YEAR(DATE(created_at)) = YEAR(CURDATE())";
            $date_condition_date = " AND YEAR(date) = YEAR(CURDATE())";
        }
    }
    $purchases_query = "SELECT payment_type, SUM(price) as iqd, SUM(amount_iqd) as amount_iqd, SUM(amount_iqd / NULLIF(exchange_rate / 100, 0)) as iqd_converted FROM purchases WHERE type='دینار' $date_condition_date GROUP BY payment_type";
    $stmt = $pdo->query($purchases_query);
    while ($row = $stmt->fetch()) {
        if ($row['payment_type'] === 'نەقد') {
            $purchases['cash']['iqd'] = $row['iqd'] ?? 0;
            $purchases['cash']['iqd_converted'] = $row['iqd_converted'] ?? 0;
        } elseif ($row['payment_type'] === 'قەرز') {
            $purchases['credit']['iqd'] = $row['iqd'] ?? 0;
            $purchases['credit']['iqd_converted'] = $row['iqd_converted'] ?? 0;
        }
    }
    // دۆلار
    $stmt = $pdo->query("SELECT payment_type, SUM(price) as usd FROM purchases WHERE type='دۆلار' GROUP BY payment_type");
    while ($row = $stmt->fetch()) {
        if ($row['payment_type'] === 'نەقد') {
            $purchases['cash']['usd'] = $row['usd'] ?? 0;
        } elseif ($row['payment_type'] === 'قەرز') {
            $purchases['credit']['usd'] = $row['usd'] ?? 0;
        }
    }
    // کۆی گشتی بە USD
    $total_usd = ($purchases['cash']['usd'] ?? 0) + ($purchases['credit']['usd'] ?? 0);
    $total_iqd_converted = ($purchases['cash']['iqd_converted'] ?? 0) + ($purchases['credit']['iqd_converted'] ?? 0);

    // Remaining Purchases
    $stmt = $pdo->query("SELECT SUM(remaining_usd) as usd, SUM(remaining_iqd) as iqd, SUM(remaining_iqd / NULLIF(exchange_rate / 100, 0)) as iqd_converted FROM purchases");
    $row = $stmt->fetch();
    $remaining_purchases_usd = $row['usd'] ?? 0;
    $remaining_purchases_iqd = $row['iqd'] ?? 0;
    $remaining_purchases_iqd_converted = $row['iqd_converted'] ?? 0;
    $remaining_purchases_total_usd = $remaining_purchases_usd + $remaining_purchases_iqd_converted;

    // Sales (فرۆشتن) - Only USD
    $sales = [
        'cash' => ['usd' => 0],
        'credit' => ['usd' => 0]
    ];
    $sales_query = "SELECT payment_type, SUM(total_price) as usd FROM sales WHERE 1=1 $date_condition_sales GROUP BY payment_type";
    $stmt = $pdo->query($sales_query);
    while ($row = $stmt->fetch()) {
        if ($row['payment_type'] === 'نەقد') {
            $sales['cash']['usd'] = $row['usd'] ?? 0;
        } elseif ($row['payment_type'] === 'قەرز') {
            $sales['credit']['usd'] = $row['usd'] ?? 0;
        }
    }

    // Remaining Sales
    $stmt = $pdo->query("SELECT SUM(remaining_amount) as usd, SUM(amount_paid_iq) as iqd, SUM(amount_paid_iq / NULLIF(dolar_rate, 0)) as iqd_converted FROM sales");
    $row = $stmt->fetch();
    $remaining_sales_usd = $row['usd'] ?? 0;
    $remaining_sales_iqd = $row['iqd'] ?? 0;
    $remaining_sales_iqd_converted = $row['iqd_converted'] ?? 0;
    $remaining_sales_total_usd = $remaining_sales_usd + $remaining_sales_iqd_converted;

    // Other Expenses (خەرجی تر)
    $stmt = $pdo->query("SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd FROM other_expenses");
    $row = $stmt->fetch();
    $other_expenses_usd = $row['usd'] ?? 0;
    $other_expenses_iqd = $row['iqd'] ?? 0;
    $other_expenses_iqd_converted = ($usd_iqd_rate > 0) ? ($other_expenses_iqd / ($usd_iqd_rate / 100)) : 0;
    $other_expenses_total_usd = $other_expenses_usd + $other_expenses_iqd_converted;

    // Employee Expenses (خەرجی کارمەند)
    $employee_expenses_query = "SELECT SUM(total) as total_expenses FROM employee_payments WHERE 1=1 $date_condition_employee_payments";
    $stmt = $pdo->query($employee_expenses_query);
    $row = $stmt->fetch();
    $employee_expenses = $row['total_expenses'] ?? 0;
    $employee_expenses_usd = ($usd_iqd_rate > 0) ? ($employee_expenses / ($usd_iqd_rate / 100)) : 0;

    // Discounts (کۆی داشکاندن) - From sales + customer debt payments
    $sales_discounts_query = "SELECT SUM(discount) as total_discount FROM sales WHERE 1=1 $date_condition_sales";
    $stmt = $pdo->query($sales_discounts_query);
    $row = $stmt->fetch();
    $sales_discounts = $row['total_discount'] ?? 0;
    
    // Customer debt payments discounts
    $customer_debt_discounts_query = "SELECT SUM(discount) as total_discount FROM customer_debt_payments WHERE 1=1 $date_condition_date";
    $stmt = $pdo->query($customer_debt_discounts_query);
    $row = $stmt->fetch();
    $customer_debt_discounts = $row['total_discount'] ?? 0;
    
    // Total discounts = sales discounts + customer debt payment discounts
    $total_discount = $sales_discounts + $customer_debt_discounts;
    
    // Debug: Log discount breakdown for first calculation
    error_log("Debug - First discount calculation: sales_discounts=" . $sales_discounts . 
              ", customer_debt_discounts=" . $customer_debt_discounts . 
              ", total_discount=" . $total_discount);

    // Debt payments (company)
    $debt_payments_query = "SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd FROM debt_payments WHERE 1=1 $date_condition_date";
    $stmt = $pdo->query($debt_payments_query);
    $row = $stmt->fetch();
    $debt_payments_usd = $row['usd'] ?? 0;
    $debt_payments_iqd = $row['iqd'] ?? 0;
    $debt_payments_iqd_converted = ($usd_iqd_rate > 0) ? ($debt_payments_iqd / ($usd_iqd_rate / 100)) : 0;
    $debt_payments_total_usd = $debt_payments_usd + $debt_payments_iqd_converted;

    // Customer debt payments
    $customer_debt_payments_query = "SELECT SUM(paid_usd) as usd, SUM(paid_iqd) as iqd FROM customer_debt_payments WHERE 1=1 $date_condition_date";
    $stmt = $pdo->query($customer_debt_payments_query);
    $row = $stmt->fetch();
    $customer_debt_payments_usd = $row['usd'] ?? 0;
    $customer_debt_payments_iqd = $row['iqd'] ?? 0;
    $customer_debt_payments_iqd_converted = ($usd_iqd_rate > 0) ? ($customer_debt_payments_iqd / ($usd_iqd_rate / 100)) : 0;
    $customer_debt_payments_total_usd = $customer_debt_payments_usd + $customer_debt_payments_iqd_converted;

    // Person other expenses debt payments
    $person_debt_payments_query = "SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd FROM person_other_expenses_debt_payments WHERE 1=1 $date_condition_date";
    $stmt = $pdo->query($person_debt_payments_query);
    $row = $stmt->fetch();
    $person_debt_payments_usd = $row['usd'] ?? 0;
    $person_debt_payments_iqd = $row['iqd'] ?? 0;
    $person_debt_payments_iqd_converted = ($usd_iqd_rate > 0) ? ($person_debt_payments_iqd / ($usd_iqd_rate / 100)) : 0;
    $person_debt_payments_total_usd = $person_debt_payments_usd + $person_debt_payments_iqd_converted;

    // Net Profit (قازانجی خاوێن)
    $total_sales = 0;
    $stmt = $pdo->query("SELECT SUM(total_price) as total_sales FROM sales");
    $row = $stmt->fetch();
    $total_sales = $row['total_sales'] ?? 0;

    $total_expenses = $other_expenses_usd; // already in USD
    $total_purchases = 0;
    $stmt = $pdo->query("SELECT SUM(paid_usd + IFNULL(remaining_usd,0)) as total_purchases FROM purchases WHERE type='دۆلار'");
    $row = $stmt->fetch();
    $total_purchases = $row['total_purchases'] ?? 0;
    // Add IQD purchases converted to USD using API rate
    $stmt = $pdo->query("SELECT SUM(amount_iqd) as iqd FROM purchases WHERE type='دینار'");
    $row = $stmt->fetch();
    $iqd = $row['iqd'] ?? 0;
    $total_purchases += ($usd_iqd_rate > 0 ? ($iqd / ($usd_iqd_rate / 100)) : 0);

    $total_employee_expenses = 0;
    $stmt = $pdo->query("SELECT SUM(total) as total_expenses FROM employee_payments");
    $row = $stmt->fetch();
    $total_employee_expenses = $row['total_expenses'] ?? 0;
    // Convert IQD to USD for employee expenses using API rate
    $total_employee_expenses_usd = ($usd_iqd_rate > 0 ? ($total_employee_expenses / ($usd_iqd_rate / 100)) : 0);

    // Calculate total expenses (کۆی خەرجی) with date filter
    $total_expenses_breakdown = [
        'employee_payments' => 0,
        'other_expenses' => 0,
        'purchases' => 0,
        'purchase_materials' => 0
    ];

    // Employee payments with date filter
    $employee_payments_query = "SELECT SUM(total) as total_expenses FROM employee_payments WHERE 1=1 $date_condition_employee_payments";
    $stmt = $pdo->query($employee_payments_query);
    $row = $stmt->fetch();
    $total_employee_expenses = $row['total_expenses'] ?? 0;
    $total_employee_expenses_usd = ($usd_iqd_rate > 0 ? ($total_employee_expenses / ($usd_iqd_rate / 100)) : 0);
    $total_expenses_breakdown['employee_payments'] = $total_employee_expenses_usd;

    // Other expenses - including all expense types with date filter - includes cash box operations
    $other_expenses_query = "SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd FROM other_expenses WHERE expense_type IN ('خەرجی تر', 'خواردنگە', 'ئۆفیس') $date_condition_date";
    $stmt = $pdo->query($other_expenses_query);
    $row = $stmt->fetch();
    $other_expenses_usd = $row['usd'] ?? 0;
    $other_expenses_iqd = $row['iqd'] ?? 0;
    $other_expenses_total_usd = $other_expenses_usd + (($usd_iqd_rate > 0) ? ($other_expenses_iqd / ($usd_iqd_rate / 100)) : 0);
    $total_expenses_breakdown['other_expenses'] = $other_expenses_total_usd;

    // Material usage from warehouse (بەکارهێنانی کاڵای کۆگا) with date filter
    $material_usage_query = "SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd FROM other_expenses WHERE expense_type = 'بەکارهێنانی کاڵای کۆگا' $date_condition_date";
    $stmt = $pdo->query($material_usage_query);
    $row = $stmt->fetch();
    $material_usage_usd = $row['usd'] ?? 0;
    $material_usage_iqd = $row['iqd'] ?? 0;
    $material_usage_total_usd = $material_usage_usd + (($usd_iqd_rate > 0) ? ($material_usage_iqd / ($usd_iqd_rate / 100)) : 0);
    $total_expenses_breakdown['material_usage'] = $material_usage_total_usd;

    // Gas usage (بەکارهێنانی گاز) with date filter
    $gas_usage_query = "SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd FROM other_expenses WHERE expense_type = 'بەکارهێنانی گاز' $date_condition_date";
    $stmt = $pdo->query($gas_usage_query);
    $row = $stmt->fetch();
    $gas_usage_usd = $row['usd'] ?? 0;
    $gas_usage_iqd = $row['iqd'] ?? 0;
    $gas_usage_total_usd = $gas_usage_usd + (($usd_iqd_rate > 0) ? ($gas_usage_iqd / ($usd_iqd_rate / 100)) : 0);
    $total_expenses_breakdown['gas_usage'] = $gas_usage_total_usd;
    
    // Gas Income (داهاتی گاز) - Calculate based on specific employees
    $gas_income_query = "
        SELECT 
            SUM(oe.amount_usd) as usd, 
            SUM(oe.amount_iqd) as iqd,
            SUM(oe.gas_total_cost) as gas_cost
        FROM other_expenses oe
        INNER JOIN employees e ON oe.employee_id = e.id
        WHERE oe.expense_type = 'بەکارهێنانی گاز' 
        AND e.name IN ('سانکۆ', 'کمال باوکی سانکۆ', 'تڕێلەکە', 'کەسارەکە/ئەرکان', 'کەسارەکە/سامی')
        $date_condition_date
    ";
    
    // Debug: Log the actual SQL query
    error_log("Debug - Gas income SQL query: " . $gas_income_query);
    
    $stmt = $pdo->query($gas_income_query);
    $row = $stmt->fetch();
    $gas_income_usd = $row['usd'] ?? 0;
    $gas_income_iqd = $row['iqd'] ?? 0;
    $gas_income_total_usd = $gas_income_usd + (($usd_iqd_rate > 0) ? ($gas_income_iqd / ($usd_iqd_rate / 100)) : 0);
    $gas_income_gas_cost = $row['gas_cost'] ?? 0;
    
    // Debug: Log gas income calculation
    error_log("Debug - Gas income calculation: usd=" . $gas_income_usd . 
              ", iqd=" . $gas_income_iqd . 
              ", gas_cost=" . $gas_income_gas_cost . 
              ", total_usd=" . $gas_income_total_usd);
    
    // Debug: Log date condition
    error_log("Debug - Date condition: filter=" . $filter . 
              ", use_range=" . ($use_range ? 'true' : 'false') . 
              ", date_condition_date='" . $date_condition_date . "'");
    
    // Additional debugging: Check if employees exist
    $employee_check_query = "SELECT id, name FROM employees WHERE name IN ('سانکۆ', 'کمال باوکی سانکۆ', 'تڕێلەکە', 'کەسارەکە/ئەرکان', 'کەسارەکە/سامی')";
    $stmt = $pdo->query($employee_check_query);
    $found_employees = [];
    while ($emp_row = $stmt->fetch()) {
        $found_employees[] = $emp_row['name'];
    }
    error_log("Debug - Found employees: " . implode(', ', $found_employees));
    
    // Check total gas expenses without employee filter
    $total_gas_query = "SELECT COUNT(*) as total, SUM(amount_usd) as total_usd, SUM(amount_iqd) as total_iqd FROM other_expenses WHERE expense_type = 'بەکارهێنانی گاز' $date_condition_date";
    $stmt = $pdo->query($total_gas_query);
    $total_gas_row = $stmt->fetch();
    error_log("Debug - Total gas expenses: count=" . ($total_gas_row['total'] ?? 0) . 
              ", usd=" . ($total_gas_row['total_usd'] ?? 0) . 
              ", iqd=" . ($total_gas_row['total_iqd'] ?? 0));
    
    // Check gas expenses with employee filter but without date filter
    $gas_with_employee_query = "
        SELECT 
            COUNT(*) as total,
            SUM(oe.amount_usd) as total_usd,
            SUM(oe.amount_iqd) as total_iqd
        FROM other_expenses oe
        INNER JOIN employees e ON oe.employee_id = e.id
        WHERE oe.expense_type = 'بەکارهێنانی گاز' 
        AND e.name IN ('سانکۆ', 'کمال باوکی سانکۆ', 'تڕێلەکە', 'کەسارەکە/ئەرکان', 'کەسارەکە/سامی')
    ";
    $stmt = $pdo->query($gas_with_employee_query);
    $gas_employee_row = $stmt->fetch();
    error_log("Debug - Gas expenses with employee filter (no date): count=" . ($gas_employee_row['total'] ?? 0) . 
              ", usd=" . ($gas_employee_row['total_usd'] ?? 0) . 
              ", iqd=" . ($gas_employee_row['total_iqd'] ?? 0));
    
    // Check all employees in the system
    $all_employees_query = "SELECT id, name FROM employees ORDER BY name";
    $stmt = $pdo->query($all_employees_query);
    $all_employees = [];
    while ($emp_row = $stmt->fetch()) {
        $all_employees[] = $emp_row['name'];
    }
    error_log("Debug - All employees in system: " . implode(', ', $all_employees));
    
    // Check sample gas expenses to see structure
    $sample_gas_query = "
        SELECT 
            oe.id,
            oe.expense_type,
            oe.amount_usd,
            oe.amount_iqd,
            oe.employee_id,
            e.name as employee_name
        FROM other_expenses oe
        LEFT JOIN employees e ON oe.employee_id = e.id
        WHERE oe.expense_type = 'بەکارهێنانی گاز'
        LIMIT 5
    ";
    $stmt = $pdo->query($sample_gas_query);
    $sample_gas = [];
    while ($sample_row = $stmt->fetch()) {
        $sample_gas[] = [
            'id' => $sample_row['id'],
            'expense_type' => $sample_row['expense_type'],
            'amount_usd' => $sample_row['amount_usd'],
            'amount_iqd' => $sample_row['amount_iqd'],
            'employee_id' => $sample_row['employee_id'],
            'employee_name' => $sample_row['employee_name']
        ];
    }
    error_log("Debug - Sample gas expenses: " . json_encode($sample_gas));
    
    // Check if there are any gas expenses at all
    $any_gas_query = "SELECT COUNT(*) as total FROM other_expenses WHERE expense_type = 'بەکارهێنانی گاز'";
    $stmt = $pdo->query($any_gas_query);
    $any_gas_count = $stmt->fetchColumn();
    error_log("Debug - Total gas expenses in system (any): " . $any_gas_count);
    
    // Check if there are any gas expenses with employee_id
    $gas_with_employee_id_query = "SELECT COUNT(*) as total FROM other_expenses WHERE expense_type = 'بەکارهێنانی گاز' AND employee_id IS NOT NULL";
    $stmt = $pdo->query($gas_with_employee_id_query);
    $gas_with_employee_id_count = $stmt->fetchColumn();
    error_log("Debug - Gas expenses with employee_id: " . $gas_with_employee_id_count);
    
    // Check if there are any gas expenses with employee_id = 0
    $gas_with_zero_employee_id_query = "SELECT COUNT(*) as total FROM other_expenses WHERE expense_type = 'بەکارهێنانی گاز' AND employee_id = 0";
    $stmt = $pdo->query($gas_with_zero_employee_id_query);
    $gas_with_zero_employee_id_count = $stmt->fetchColumn();
    error_log("Debug - Gas expenses with employee_id = 0: " . $gas_with_zero_employee_id_count);
    
    // Check if there are any gas expenses with employee_id = 0
    $gas_with_zero_employee_id_query = "SELECT COUNT(*) as total FROM other_expenses WHERE expense_type = 'بەکارهێنانی گاز' AND employee_id = 0";
    $stmt = $pdo->query($gas_with_zero_employee_id_query);
    $gas_with_zero_employee_id_count = $stmt->fetchColumn();
    error_log("Debug - Gas expenses with employee_id = 0: " . $gas_with_zero_employee_id_count);

    // Purchases (کڕین) - only cash payments with date filter
    $purchases_query = "SELECT SUM(amount_iqd) as iqd, SUM(amount_iqd / NULLIF(exchange_rate / 100, 0)) as iqd_converted FROM purchases WHERE payment_type = 'نەقد' AND type = 'دینار' $date_condition_date";
    $stmt = $pdo->query($purchases_query);
    $row = $stmt->fetch();
    $purchases_cash_iqd = $row['iqd'] ?? 0;
    $purchases_cash_usd = $row['iqd_converted'] ?? 0;
    $total_expenses_breakdown['purchases'] = $purchases_cash_usd;

    // Purchase materials (کڕینی مەواد) with date filter
    $purchase_materials_query = "SELECT SUM(total_price_usd) as usd, SUM(total_price_iqd) as iqd FROM purchase_materials WHERE 1=1";
    if ($use_range) {
        $from = $from_date ? $from_date : '1000-01-01';
        $to = $to_date ? $to_date : '9999-12-31';
        $purchase_materials_query .= " AND purchase_date >= '$from' AND purchase_date <= '$to'";
    } else {
        if ($filter === 'today') {
            $purchase_materials_query .= " AND purchase_date = CURDATE()";
        } elseif ($filter === 'week') {
            $purchase_materials_query .= " AND YEARWEEK(purchase_date, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($filter === 'month') {
            $purchase_materials_query .= " AND YEAR(purchase_date) = YEAR(CURDATE()) AND MONTH(purchase_date) = MONTH(CURDATE())";
        } elseif ($filter === 'year') {
            $purchase_materials_query .= " AND YEAR(purchase_date) = YEAR(CURDATE())";
        }
    }
    $stmt = $pdo->query($purchase_materials_query);
    $row = $stmt->fetch();
    $purchase_materials_usd = $row['usd'] ?? 0;
    $purchase_materials_iqd = $row['iqd'] ?? 0;
    $purchase_materials_total_usd = $purchase_materials_usd + (($usd_iqd_rate > 0) ? ($purchase_materials_iqd / ($usd_iqd_rate / 100)) : 0);
    $total_expenses_breakdown['purchase_materials'] = $purchase_materials_total_usd;

    // Total expenses
    $total_expenses_usd = array_sum($total_expenses_breakdown);

    $total_discounts = 0;
    // Sales discounts
    $stmt = $pdo->query("SELECT SUM(discount) as total_discount FROM sales WHERE 1=1 $date_condition_sales");
    $row = $stmt->fetch();
    $sales_discounts_total = $row['total_discount'] ?? 0;
    
    // Customer debt payment discounts
    $stmt = $pdo->query("SELECT SUM(discount) as total_discount FROM customer_debt_payments WHERE 1=1 $date_condition_date");
    $row = $stmt->fetch();
    $customer_debt_discounts_total = $row['total_discount'] ?? 0;
    
        // Total discounts = sales + customer debt payments
    $total_discounts = $sales_discounts_total + $customer_debt_discounts_total;
    
    // Debug: Log discount breakdown
    error_log("Debug - Discounts breakdown: sales_discounts=" . $sales_discounts_total . 
              ", customer_debt_discounts=" . $customer_debt_discounts_total . 
              ", total_discounts=" . $total_discounts);
    
    // Calculate net profit: کۆی فرۆشتن - کۆی خەرجی - داشکاندن
    $total_sales_amount = ($sales['cash']['usd'] ?? 0) + ($sales['credit']['usd'] ?? 0);
    $net_profit = $total_sales_amount - $total_expenses_usd - $total_discounts;

    // Additional Professional Reports Data
    
    // Employee Reports
    $employee_stats = [];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
    $employee_stats['total'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(salary) as total_salary FROM employees");
    $employee_stats['total_salary'] = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as drivers FROM employees WHERE role = 'شۆفێر'");
    $employee_stats['drivers'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as accountants FROM employees WHERE role = 'موحاسیب'");
    $employee_stats['accountants'] = $stmt->fetchColumn();
    
    // Car Reports
    $car_stats = [];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cars");
    $car_stats['total'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(gas_liters) as total_gas_used FROM other_expenses WHERE expense_type = 'بەکارهێنانی گاز'");
    $car_stats['total_gas_used'] = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->query("SELECT SUM(gas_total_cost) as total_gas_expense FROM other_expenses WHERE expense_type = 'بەکارهێنانی گاز'");
    $car_stats['total_gas_expense'] = $stmt->fetchColumn() ?: 0;
    
    $car_stats['avg_expense'] = $car_stats['total'] > 0 ? ($car_stats['total_gas_expense'] / $car_stats['total']) : 0;
    
    // Stock Reports
    $stock_stats = [];
    $stmt = $pdo->query("SELECT COUNT(*) as total_bins FROM bins_silos");
    $stock_stats['total_bins'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_materials FROM materials");
    $stock_stats['total_materials'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as low_stock_items FROM bins_silos WHERE amount < 1000");
    $stock_stats['low_stock_items'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(total_value) as total_value FROM bins_silos");
    $stock_stats['total_value'] = $stmt->fetchColumn() ?: 0;
    
    // Activity Reports
    $activity_stats = [];
    $stmt = $pdo->query("SELECT COUNT(*) as concrete_receipts FROM concrete_receipts");
    $activity_stats['concrete_receipts'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as notes FROM notes");
    $activity_stats['notes'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as notifications FROM notifications WHERE seen = 0");
    $activity_stats['notifications'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as stock_adjustments FROM stock_adjustments");
    $activity_stats['stock_adjustments'] = $stmt->fetchColumn();

    // Chart Data - Real Database Queries
    
    // 1. Stock by Material Type
    $stock_by_material = [];
    $stmt = $pdo->query("
        SELECT 
            material_type,
            SUM(amount) as total_amount,
            SUM(total_value) as total_value
        FROM bins_silos 
        GROUP BY material_type
    ");
    while ($row = $stmt->fetch()) {
        $stock_by_material[$row['material_type']] = $row['total_value'] ?: 0;
    }
    
    // 2. Monthly Income/Expenses (last 6 months)
    $monthly_data = [];
    $current_month = date('Y-m');
    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m', strtotime("-$i months"));
        $year = date('Y', strtotime("-$i months"));
        $month = date('m', strtotime("-$i months"));
        
        // Monthly sales - using order_date instead of date
        $stmt = $pdo->prepare("
            SELECT SUM(total_price) as total_sales 
        FROM sales
            WHERE DATE_FORMAT(order_date, '%Y-%m') = ?
        ");
        $stmt->execute([$date]);
        $monthly_sales = $stmt->fetchColumn() ?: 0;
        
        // Monthly expenses - using date column
        $stmt = $pdo->prepare("
            SELECT SUM(amount_usd) as total_expenses 
            FROM other_expenses 
            WHERE DATE_FORMAT(date, '%Y-%m') = ?
        ");
        $stmt->execute([$date]);
        $monthly_expenses = $stmt->fetchColumn() ?: 0;
        
        // Employee payments - using created_at instead of date
        $stmt = $pdo->prepare("
            SELECT SUM(total) as total_employee_payments 
            FROM employee_payments 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
        ");
        $stmt->execute([$date]);
        $employee_payments = $stmt->fetchColumn() ?: 0;
        
        $monthly_data[$year][$month] = [
            'sales' => $monthly_sales,
            'expenses' => $monthly_expenses + ($employee_payments / ($usd_iqd_rate / 100)),
            'income' => $monthly_sales - ($monthly_expenses + ($employee_payments / ($usd_iqd_rate / 100)))
        ];
    }
    
    // 3. Employee Performance Data
    $employee_performance = [];
    $stmt = $pdo->query("
        SELECT 
            e.name,
            COUNT(DISTINCT ep.id) as payment_count,
            AVG(ep.total) as avg_salary,
            COUNT(DISTINCT oe.id) as expense_count
        FROM employees e
        LEFT JOIN employee_payments ep ON e.id = ep.employee_id
        LEFT JOIN other_expenses oe ON e.id = oe.employee_id
        GROUP BY e.id, e.name
        ORDER BY avg_salary DESC
        LIMIT 10
    ");
    while ($row = $stmt->fetch()) {
        $performance_score = 0;
        if ($row['payment_count'] > 0) $performance_score += 30;
        if ($row['avg_salary'] > 500000) $performance_score += 40;
        if ($row['expense_count'] > 0) $performance_score += 30;
        
        $employee_performance[$row['name']] = min(100, $performance_score);
    }
    
    // 4. Car Expenses Data
    $car_expenses = [];
    $stmt = $pdo->query("
        SELECT 
            c.name as car_name,
            SUM(oe.gas_liters) as total_gas_used,
            SUM(oe.gas_total_cost) as total_gas_cost,
            COUNT(oe.id) as expense_count
        FROM cars c
        LEFT JOIN other_expenses oe ON c.id = oe.car_id AND oe.expense_type = 'بەکارهێنانی گاز'
        GROUP BY c.id, c.name
        ORDER BY total_gas_cost DESC
        LIMIT 10
    ");
    while ($row = $stmt->fetch()) {
        $car_expenses[$row['car_name']] = [
            'gas_used' => $row['total_gas_used'] ?: 0,
            'gas_cost' => $row['total_gas_cost'] ?: 0,
            'expense_count' => $row['expense_count'] ?: 0
        ];
    }
    
    // 5. Sales vs Expenses vs Profit (Current Period)
    $current_period_sales = ($sales['cash']['usd'] ?? 0) + ($sales['credit']['usd'] ?? 0);
    $current_period_expenses = $total_expenses_usd;
    $current_period_profit = $current_period_sales - $current_period_expenses;
    
    // Ensure all required variables are defined
    if (!isset($net_profit)) $net_profit = 0;
    if (!isset($total_discount)) $total_discount = 0;
    if (!isset($remaining_purchases_total_usd)) $remaining_purchases_total_usd = 0;
    if (!isset($remaining_purchases_iqd)) $remaining_purchases_iqd = 0;
    
    // 6. Debt Analysis
    $debt_analysis = [
        'customer_debt' => $customer_debt_total_usd,
        'company_debt' => $company_debt_total_usd,
        'person_debt' => $person_debt_usd
    ];

    // Debug: Log key variables
    error_log("Debug - Key variables: customer_debt_total_usd=" . $customer_debt_total_usd . 
              ", company_debt_total_usd=" . $company_debt_total_usd . 
              ", person_debt_usd=" . $person_debt_usd . 
              ", net_profit=" . $net_profit . 
              ", total_expenses_usd=" . $total_expenses_usd);
    
    // Prepare response data
    $response_data = [
        'success' => true,
        'data' => [
            'usd_iqd_rate' => $usd_iqd_rate,
            'customer' => ['usd' => $customer_debt_total_usd, 'iqd' => 0],
            'company' => ['usd' => $company_debt_total_usd, 'iqd' => 0],
            'person' => ['usd' => $person_debt_usd, 'iqd' => 0],
            'purchases' => $purchases,
            'sales' => $sales,
            'remaining_purchases' => ['usd' => $remaining_purchases_total_usd, 'iqd' => $remaining_purchases_iqd],
            'discounts' => ['usd' => $total_discount],
            'net_profit' => ['usd' => $net_profit],
            'gas_income' => [
                'usd' => $gas_income_total_usd,
                'gas_cost' => $gas_income_gas_cost,
                'breakdown' => [
                    'usd' => $gas_income_usd,
                    'iqd' => $gas_income_iqd,
                    'iqd_converted' => $gas_income_total_usd - $gas_income_usd
                ]
            ],
            'total_expenses' => [
                'usd' => $total_expenses_usd,
                'breakdown' => $total_expenses_breakdown
            ],
            // Additional professional reports data
            'employees' => $employee_stats,
            'cars' => $car_stats,
            'stock' => $stock_stats,
            'activity' => $activity_stats,
            // Chart data
            'charts' => [
                'stock_by_material' => $stock_by_material,
                'monthly_data' => $monthly_data,
                'employee_performance' => $employee_performance,
                'car_expenses' => $car_expenses,
                'sales_vs_expenses' => [
                    'sales' => $current_period_sales,
                    'expenses' => $current_period_expenses,
                    'profit' => $current_period_profit
                ],
                'debt_analysis' => $debt_analysis
            ]
        ]
    ];
    
    // Debug: Log response structure
    error_log("Debug - Response structure: " . json_encode($response_data));
    
    // 2. Debts by type - Updated to use new calculation method
    $debts_by_type = [
        'customers' => 0,
        'companies' => 0,
        'persons' => 0
    ];
    
    // Customers debt (opening_debt + remaining from sales)
    $stmt = $pdo->query("
        SELECT 
            SUM(c.opening_debt_usd) as opening_debt_usd,
            SUM(c.opening_debt_iqd) as opening_debt_iqd,
            COALESCE(SUM(s.remaining_amount), 0) as remaining_from_sales
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_type = 'قەرز'
    ");
    $row = $stmt->fetch();
    $customer_total_debt = floatval($row['opening_debt_usd'] ?? 0) + floatval($row['remaining_from_sales'] ?? 0);
    $customer_iqd_debt = floatval($row['opening_debt_iqd'] ?? 0);
    $customer_iqd_converted = ($usd_iqd_rate > 0) ? ($customer_iqd_debt / ($usd_iqd_rate / 100)) : 0;
    $debts_by_type['customers'] = $customer_total_debt + $customer_iqd_converted;
    
    // Companies debt (opening_debt + remaining from purchases) - using same method as above
    $debts_by_type['companies'] = $company_debt_total_usd;
    
    // Persons debt
    $stmt = $pdo->query('SELECT SUM(expense_usd) as usd FROM other_expense_persons');
    $debts_by_type['persons'] = (float)($stmt->fetch()['usd'] ?? 0);
    
    // 3. Stock by material
    $stock_by_material = [];
    $stmt = $pdo->query('SELECT material_type, SUM(amount) as total FROM bins_silos GROUP BY material_type');
    while ($row = $stmt->fetch()) {
        $stock_by_material[$row['material_type']] = (float)$row['total'];
    }
    // 4. Sales by payment type
    $sales_by_payment_type = [ 'cash' => 0, 'credit' => 0 ];
    $stmt = $pdo->query("SELECT payment_type, SUM(total_price) as total FROM sales GROUP BY payment_type");
    while ($row = $stmt->fetch()) {
        if ($row['payment_type'] === 'نەقد') $sales_by_payment_type['cash'] = (float)$row['total'];
        if ($row['payment_type'] === 'قەرز') $sales_by_payment_type['credit'] = (float)$row['total'];
    }
    // 5. Income by month and year (all years, all months with sales)
    $income_by_month_year = [];
    $stmt = $pdo->query("SELECT YEAR(order_date) as year, MONTH(order_date) as month, SUM(total_price) as income FROM sales GROUP BY year, month ORDER BY year, month");
    while ($row = $stmt->fetch()) {
        $y = $row['year'];
        $m = $row['month'];
        if (!isset($income_by_month_year[$y])) $income_by_month_year[$y] = [];
        $income_by_month_year[$y][str_pad($m,2,'0',STR_PAD_LEFT)] = (float)$row['income'];
    }

    // Other expense persons breakdown by payment_type
    // نەقد
    $stmt = $pdo->query("SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd, SUM(amount_iqd / NULLIF(exchange_rate / 100, 0)) as iqd_converted FROM other_expenses WHERE payment_type='نەقد'");
    $row = $stmt->fetch();
    $person_cash_usd = $row['usd'] ?? 0;
    $person_cash_iqd = $row['iqd'] ?? 0;
    $person_cash_iqd_converted = $row['iqd_converted'] ?? 0;
    $person_cash_total_usd = $person_cash_usd + $person_cash_iqd_converted;
    
    // قەرز
    $stmt = $pdo->query("SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd, SUM(amount_iqd / NULLIF(exchange_rate / 100, 0)) as iqd_converted FROM other_expenses WHERE payment_type='قەرز'");
    $row = $stmt->fetch();
    $person_credit_usd = $row['usd'] ?? 0;
    $person_credit_iqd = $row['iqd'] ?? 0;
    $person_credit_iqd_converted = $row['iqd_converted'] ?? 0;
    $person_credit_total_usd = $person_credit_usd + $person_credit_iqd_converted;
    
    // Use the calculated total debt from above
    $person_debt_usd = $person_debt_total_usd; // This is already calculated correctly above
    // $person_debt_iqd is already calculated correctly above, no need to reassign

    echo json_encode($response_data);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}
