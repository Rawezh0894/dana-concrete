<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    // Get exchange rate from settings table
    $usd_iqd_rate = 150000;
    try {
        $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'usd_iqd_rate'");
        $row = $stmt->fetch();
        if ($row && is_numeric($row['value'])) {
            $usd_iqd_rate = floatval($row['value']);
        }
    } catch (Exception $e) {}

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

    // Companies - Calculate debt using new method (opening_debt + remaining from purchases)
    $company_debt_query = "
        SELECT 
            SUM(c.opening_debt_usd) as opening_debt_usd,
            SUM(c.opening_debt_iqd) as opening_debt_iqd,
            COALESCE(SUM(p.remaining_usd), 0) as remaining_from_purchases
        FROM company c
        LEFT JOIN purchases p ON c.id = p.company_id AND p.payment_type = 'قەرز'
    ";
    $stmt = $pdo->query($company_debt_query);
    $row = $stmt->fetch();
    $company_debt_usd = floatval($row['opening_debt_usd'] ?? 0) + floatval($row['remaining_from_purchases'] ?? 0);
    $company_debt_iqd = floatval($row['opening_debt_iqd'] ?? 0);
    $company_debt_iqd_converted = ($usd_iqd_rate > 0) ? ($company_debt_iqd / ($usd_iqd_rate / 100)) : 0;
    $company_debt_total_usd = $company_debt_usd + $company_debt_iqd_converted;

    // Other expense persons
    $stmt = $pdo->query('SELECT SUM(expense_usd) as usd, SUM(expense_iqd) as iqd FROM other_expense_persons');
    $row = $stmt->fetch();
    $person_debt_usd = $row['usd'] ?? 0;
    $person_debt_iqd = $row['iqd'] ?? 0;

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
    // Build date range condition for each table
    $date_condition_date = '';
    $date_condition_sales = '';
    $date_condition_employee_payments = '';
    if ($use_range) {
        $from = $from_date ? $from_date : '1000-01-01';
        $to = $to_date ? $to_date : '9999-12-31';
        $date_condition_date = " AND date >= '$from' AND date <= '$to'";
        $date_condition_sales = " AND order_date >= '$from' AND order_date <= '$to'";
        $date_condition_employee_payments = " AND DATE(created_at) >= '$from' AND DATE(created_at) <= '$to'";
    } else {
        if ($filter === 'today') {
            $date_condition_date = " AND date = CURDATE()";
            $date_condition_sales = " AND order_date = CURDATE()";
            $date_condition_employee_payments = " AND DATE(created_at) = CURDATE()";
        } elseif ($filter === 'week') {
            $date_condition_date = " AND YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
            $date_condition_sales = " AND YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1)";
            $date_condition_employee_payments = " AND YEARWEEK(DATE(created_at), 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($filter === 'month') {
            $date_condition_date = " AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())";
            $date_condition_sales = " AND YEAR(order_date) = YEAR(CURDATE()) AND MONTH(order_date) = MONTH(CURDATE())";
            $date_condition_employee_payments = " AND YEAR(DATE(created_at)) = YEAR(CURDATE()) AND MONTH(DATE(created_at)) = MONTH(CURDATE())";
        } elseif ($filter === 'year') {
            $date_condition_date = " AND YEAR(date) = YEAR(CURDATE())";
            $date_condition_sales = " AND YEAR(order_date) = YEAR(CURDATE())";
            $date_condition_employee_payments = " AND YEAR(DATE(created_at)) = YEAR(CURDATE())";
        }
    }
    $purchases_query = "SELECT payment_type, SUM(price) as iqd, SUM(amount_iqd) as amount_iqd FROM purchases WHERE type='دینار' $date_condition_date GROUP BY payment_type";
    $stmt = $pdo->query($purchases_query);
    while ($row = $stmt->fetch()) {
        if ($row['payment_type'] === 'نەقد') {
            $purchases['cash']['iqd'] = $row['iqd'] ?? 0;
            $purchases['cash']['iqd_converted'] = ($usd_iqd_rate > 0) ? (($row['amount_iqd'] ?? 0) / ($usd_iqd_rate / 100)) : 0;
        } elseif ($row['payment_type'] === 'قەرز') {
            $purchases['credit']['iqd'] = $row['iqd'] ?? 0;
            $purchases['credit']['iqd_converted'] = ($usd_iqd_rate > 0) ? (($row['amount_iqd'] ?? 0) / ($usd_iqd_rate / 100)) : 0;
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
    $stmt = $pdo->query("SELECT SUM(remaining_usd) as usd, SUM(remaining_iqd) as iqd FROM purchases");
    $row = $stmt->fetch();
    $remaining_purchases_usd = $row['usd'] ?? 0;
    $remaining_purchases_iqd = $row['iqd'] ?? 0;
    $remaining_purchases_iqd_converted = ($usd_iqd_rate > 0) ? ($remaining_purchases_iqd / ($usd_iqd_rate / 100)) : 0;
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
    $stmt = $pdo->query("SELECT SUM(remaining_amount) as usd, SUM(amount_paid_iq) as iqd FROM sales");
    $row = $stmt->fetch();
    $remaining_sales_usd = $row['usd'] ?? 0;
    $remaining_sales_iqd = $row['iqd'] ?? 0;
    $remaining_sales_iqd_converted = ($usd_iqd_rate > 0) ? ($remaining_sales_iqd / ($usd_iqd_rate / 100)) : 0;
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

    // Discounts (کۆی داشکاندن)
    $discounts_query = "SELECT SUM(discount) as total_discount FROM sales WHERE 1=1 $date_condition_sales";
    $stmt = $pdo->query($discounts_query);
    $row = $stmt->fetch();
    $total_discount = $row['total_discount'] ?? 0;

    // Debt payments (company)
    $debt_payments_query = "SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd FROM debt_payments WHERE 1=1 $date_condition_date";
    $stmt = $pdo->query($debt_payments_query);
    $row = $stmt->fetch();
    $debt_payments_usd = $row['usd'] ?? 0;
    $debt_payments_iqd = $row['iqd'] ?? 0;

    // Customer debt payments
    $customer_debt_payments_query = "SELECT SUM(paid_usd) as usd, SUM(paid_iqd) as iqd FROM customer_debt_payments WHERE 1=1 $date_condition_date";
    $stmt = $pdo->query($customer_debt_payments_query);
    $row = $stmt->fetch();
    $customer_debt_payments_usd = $row['usd'] ?? 0;
    $customer_debt_payments_iqd = $row['iqd'] ?? 0;

    // Person other expenses debt payments
    $person_debt_payments_query = "SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd FROM person_other_expenses_debt_payments WHERE 1=1 $date_condition_date";
    $stmt = $pdo->query($person_debt_payments_query);
    $row = $stmt->fetch();
    $person_debt_payments_usd = $row['usd'] ?? 0;
    $person_debt_payments_iqd = $row['iqd'] ?? 0;

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
    // Add IQD purchases converted to USD
    $stmt = $pdo->query("SELECT SUM(amount_iqd) as iqd, AVG(exchange_rate) as rate FROM purchases WHERE type='دینار'");
    $row = $stmt->fetch();
    $iqd = $row['iqd'] ?? 0;
    $rate = $row['rate'] ?? 150000;
    $total_purchases += ($rate > 0 ? ($iqd / $rate) : 0);

    $total_employee_expenses = 0;
    $stmt = $pdo->query("SELECT SUM(total) as total_expenses FROM employee_payments");
    $row = $stmt->fetch();
    $total_employee_expenses = $row['total_expenses'] ?? 0;
    // Convert IQD to USD for employee expenses
    $avg_rate = 150000;
    $stmt = $pdo->query("SELECT AVG(exchange_rate) as rate FROM purchases WHERE exchange_rate > 0");
    $row = $stmt->fetch();
    if ($row && $row['rate']) $avg_rate = $row['rate'];
    $total_employee_expenses_usd = ($avg_rate > 0 ? ($total_employee_expenses / $avg_rate) : 0);

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
    $total_employee_expenses_usd = ($avg_rate > 0 ? ($total_employee_expenses / $avg_rate) : 0);
    $total_expenses_breakdown['employee_payments'] = $total_employee_expenses_usd;

    // Other expenses - only خەرجی تر (not بەکارهێنانی کاڵای کۆگا or بەکارهێنانی گاز) with date filter
    $other_expenses_query = "SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd FROM other_expenses WHERE expense_type = 'خەرجی تر' $date_condition_date";
    $stmt = $pdo->query($other_expenses_query);
    $row = $stmt->fetch();
    $other_expenses_usd = $row['usd'] ?? 0;
    $other_expenses_iqd = $row['iqd'] ?? 0;
    $other_expenses_total_usd = $other_expenses_usd + (($usd_iqd_rate > 0) ? ($other_expenses_iqd / ($usd_iqd_rate / 100)) : 0);
    $total_expenses_breakdown['other_expenses'] = $other_expenses_total_usd;

    // Purchases (کڕین) - only cash payments with date filter
    $purchases_query = "SELECT SUM(amount_iqd) as iqd FROM purchases WHERE payment_type = 'نەقد' AND type = 'دینار' $date_condition_date";
    $stmt = $pdo->query($purchases_query);
    $row = $stmt->fetch();
    $purchases_cash_iqd = $row['iqd'] ?? 0;
    $purchases_cash_usd = ($usd_iqd_rate > 0) ? ($purchases_cash_iqd / ($usd_iqd_rate / 100)) : 0;
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
    $stmt = $pdo->query("SELECT SUM(discount) as total_discount FROM sales WHERE 1=1 $date_condition_sales");
    $row = $stmt->fetch();
    $total_discounts = $row['total_discount'] ?? 0;

    // Calculate net profit: کۆی فرۆشتن - کۆی خەرجی - داشکاندن
    $total_sales_amount = ($sales['cash']['usd'] ?? 0) + ($sales['credit']['usd'] ?? 0);
    $net_profit = $total_sales_amount - $total_expenses_usd - $total_discounts;

    // 1. Monthly Income/Expenses (last 6 months)
    $monthly_income_expenses = [];
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(order_date, '%Y-%m') as month, SUM(total_price) as income
        FROM sales
        GROUP BY month
        ORDER BY month DESC
        LIMIT 6
    ");
    while ($row = $stmt->fetch()) {
        $monthly_income_expenses[$row['month']]['income'] = (float)$row['income'];
    }
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(date, '%Y-%m') as month, SUM(amount_usd) as expenses
        FROM other_expenses
        GROUP BY month
        ORDER BY month DESC
        LIMIT 6
    ");
    while ($row = $stmt->fetch()) {
        $monthly_income_expenses[$row['month']]['expenses'] = (float)$row['expenses'];
    }
    
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
    
    // Companies debt (opening_debt + remaining from purchases)
    $stmt = $pdo->query("
        SELECT 
            SUM(c.opening_debt_usd) as opening_debt_usd,
            SUM(c.opening_debt_iqd) as opening_debt_iqd,
            COALESCE(SUM(p.remaining_usd), 0) as remaining_from_purchases
        FROM company c
        LEFT JOIN purchases p ON c.id = p.company_id AND p.payment_type = 'قەرز'
    ");
    $row = $stmt->fetch();
    $company_total_debt = floatval($row['opening_debt_usd'] ?? 0) + floatval($row['remaining_from_purchases'] ?? 0);
    $company_iqd_debt = floatval($row['opening_debt_iqd'] ?? 0);
    $company_iqd_converted = ($usd_iqd_rate > 0) ? ($company_iqd_debt / ($usd_iqd_rate / 100)) : 0;
    $debts_by_type['companies'] = $company_total_debt + $company_iqd_converted;
    
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
    $stmt = $pdo->query("SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd FROM other_expenses WHERE payment_type='نەقد'");
    $row = $stmt->fetch();
    $person_cash_usd = $row['usd'] ?? 0;
    $person_cash_iqd = $row['iqd'] ?? 0;
    $person_cash_total_usd = $person_cash_usd + (($usd_iqd_rate > 0) ? ($person_cash_iqd / ($usd_iqd_rate / 100)) : 0);
    // قەرز
    $stmt = $pdo->query("SELECT SUM(amount_usd) as usd, SUM(amount_iqd) as iqd FROM other_expenses WHERE payment_type='قەرز'");
    $row = $stmt->fetch();
    $person_credit_usd = $row['usd'] ?? 0;
    $person_credit_iqd = $row['iqd'] ?? 0;
    $person_credit_total_usd = $person_credit_usd + (($usd_iqd_rate > 0) ? ($person_credit_iqd / ($usd_iqd_rate / 100)) : 0);
    $person_debt_usd = $person_cash_total_usd + $person_credit_total_usd;
    $person_debt_iqd = $person_cash_iqd + $person_credit_iqd;

    echo json_encode([
        'success' => true,
        'data' => [
            'customer' => ['usd' => $customer_debt_total_usd, 'iqd' => $customer_debt_iqd],
            'company' => ['usd' => $company_debt_total_usd, 'iqd' => $company_debt_iqd],
            'person' => [
                'cash' => ['usd' => $person_cash_total_usd, 'iqd' => $person_cash_iqd],
                'credit' => ['usd' => $person_credit_total_usd, 'iqd' => $person_credit_iqd],
                'usd' => $person_debt_usd,
                'iqd' => $person_debt_iqd
            ],
            'purchases' => array_merge($purchases, [
                'usd' => $total_usd + $total_iqd_converted,
                'iqd' => ($purchases['cash']['iqd'] ?? 0) + ($purchases['credit']['iqd'] ?? 0)
            ]),
            'sales' => $sales,
            'other_expenses' => ['usd' => $other_expenses_total_usd, 'iqd' => $other_expenses_iqd],
            'discounts' => ['usd' => $total_discount],
            'employee_expenses' => ['usd' => $employee_expenses_usd, 'iqd' => $employee_expenses],
            'total_expenses' => [
                'usd' => $total_expenses_usd,
                'breakdown' => $total_expenses_breakdown
            ],
            'net_profit' => ['usd' => $net_profit],
            'charts' => [
                'monthly_income_expenses' => $monthly_income_expenses,
                'debts_by_type' => $debts_by_type,
                'stock_by_material' => $stock_by_material,
                'sales_by_payment_type' => $sales_by_payment_type,
                'income_by_month_year' => $income_by_month_year
            ],
            'usd_iqd_rate' => $usd_iqd_rate,
            'remaining_purchases' => ['usd' => $remaining_purchases_total_usd, 'iqd' => $remaining_purchases_iqd],
            'remaining_sales' => ['usd' => $remaining_sales_total_usd, 'iqd' => $remaining_sales_iqd],
            'debt_payments' => ['usd' => $debt_payments_usd, 'iqd' => $debt_payments_iqd],
            'customer_debt_payments' => ['usd' => $customer_debt_payments_usd, 'iqd' => $customer_debt_payments_iqd],
            'person_debt_payments' => ['usd' => $person_debt_payments_usd, 'iqd' => $person_debt_payments_iqd]
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}
