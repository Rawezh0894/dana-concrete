<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    // Get exchange rate from settings table (نرخی ١٠٠ دۆلار بە دینار)
    $rate_query = "SELECT value FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1";
    $rate_stmt = $pdo->query($rate_query);
    $rate_row = $rate_stmt->fetch(PDO::FETCH_ASSOC);
    $usd_iqd_rate = floatval($rate_row['value'] ?? 150000); // Default fallback value
    
    // Debug: Log the rate being used
    error_log("Using USD/IQD rate from settings: " . $usd_iqd_rate);

    // Customers - Calculate debt using new method (opening_debt + remaining from sales)
    // ڕاستکردنەوەی هەژمارکردنی قەرز
    // 1. قەرزی سەرەتایی (USD)
    $openingDebtUSD = $pdo->query("SELECT COALESCE(SUM(opening_debt_usd), 0) FROM customers")->fetchColumn();
    
    // 2. کۆی ماوەی قەرز لە فرۆشتنەکان (تەنها ئەوانەی amount_paid_iq = 0)
    $salesRemainingUSD = $pdo->query("
        SELECT COALESCE(SUM(remaining_amount), 0) 
        FROM sales 
        WHERE payment_type = 'قەرز' 
        AND amount_paid_iq = 0
    ")->fetchColumn();
    
    // 3. کۆی ماوەی قەرز لە فرۆشتنەکان (دینار - ئەوانەی amount_paid_iq > 0)
    $salesRemainingIQD = $pdo->query("
        SELECT COALESCE(SUM(remaining_amount), 0) 
        FROM sales 
        WHERE payment_type = 'قەرز' 
        AND amount_paid_iq > 0
    ")->fetchColumn();
    
    // 4. قەرزی سەرەتایی (IQD) - گۆڕینی بۆ دۆلار
    $openingDebtIQD = $pdo->query("SELECT COALESCE(SUM(opening_debt_iqd), 0) FROM customers")->fetchColumn();
    $openingDebtIQD_USD = $usd_iqd_rate > 0 ? ($openingDebtIQD / ($usd_iqd_rate / 100)) : 0;
    
    // 5. کۆکردنەوەی هەموو قەرزەکان بە دۆلار
    // فۆرمۆلا: کۆی قەرز = پارەی ماوەی فرۆشتنەکان + قەرزی سەرەتایی
    $customer_debt_total_usd = floatval($openingDebtUSD) +           // قەرزی سەرەتایی (USD)
                               floatval($openingDebtIQD_USD) +        // قەرزی سەرەتایی (IQD → USD)
                               floatval($salesRemainingUSD) +         // پارەی ماوەی فرۆشتنەکان (USD)
                               (floatval($salesRemainingIQD) / ($usd_iqd_rate / 100)); // پارەی ماوەی فرۆشتنەکان (IQD → USD)

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

    // Other expense persons - Calculate total debt (opening debt + remaining from expenses + remaining from purchases)
    // Fix: Use the same logic as debt_helpers.php to get current remaining debt
    
    // 1. Sum of current opening debts
    $stmt = $pdo->query("SELECT SUM(opening_debt_usd) as usd, SUM(opening_debt_iqd) as iqd FROM other_expense_persons");
    $opening = $stmt->fetch();
    
    // 2. Sum of remaining from other_expenses
    $stmt = $pdo->query("SELECT SUM(remaining_usd) as usd, SUM(remaining_iqd) as iqd FROM other_expenses WHERE payment_type = 'قەرز'");
    $expenses = $stmt->fetch();
    
    // 3. Sum of remaining from purchase_materials
    $stmt = $pdo->query("SELECT SUM(remaining_amount_usd) as usd, SUM(remaining_amount_iqd) as iqd FROM purchase_materials WHERE payment_type = 'قەرز'");
    $purchases = $stmt->fetch();
    
    $person_debt_usd = floatval($opening['usd'] ?? 0) + floatval($expenses['usd'] ?? 0) + floatval($purchases['usd'] ?? 0);
    $person_debt_iqd = floatval($opening['iqd'] ?? 0) + floatval($expenses['iqd'] ?? 0) + floatval($purchases['iqd'] ?? 0);
    
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
    // For employee payments, filter by salary month, not payment creation date
    $date_condition_employee_payments = "";
    $date_condition_date = "";
    
    if ($use_range) {
        $from = $from_date ? $from_date : '1000-01-01';
        $to = $to_date ? $to_date : '9999-12-31';
        $date_condition_sales = " AND order_date >= '$from' AND order_date <= '$to'";
        // Filter employee payments by the selected salary month range
        $date_condition_employee_payments = " AND DATE(CONCAT(pay_month, '-01')) >= '$from' AND DATE(CONCAT(pay_month, '-01')) <= '$to'";
        $date_condition_date = " AND date >= '$from' AND date <= '$to'";
    } else {
        if ($filter === 'today') {
            $date_condition_sales = " AND order_date = CURDATE()";
            // Map 'today' to current month for salary-month-based filtering
            $date_condition_employee_payments = " AND YEAR(DATE(CONCAT(pay_month, '-01'))) = YEAR(CURDATE()) AND MONTH(DATE(CONCAT(pay_month, '-01'))) = MONTH(CURDATE())";
            $date_condition_date = " AND date = CURDATE()";
        } elseif ($filter === 'week') {
            $date_condition_sales = " AND YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1)";
            // Map 'week' to current month for salary-month-based filtering
            $date_condition_employee_payments = " AND YEAR(DATE(CONCAT(pay_month, '-01'))) = YEAR(CURDATE()) AND MONTH(DATE(CONCAT(pay_month, '-01'))) = MONTH(CURDATE())";
            $date_condition_date = " AND YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($filter === 'month') {
            $date_condition_sales = " AND YEAR(order_date) = YEAR(CURDATE()) AND MONTH(order_date) = MONTH(CURDATE())";
            $date_condition_employee_payments = " AND YEAR(DATE(CONCAT(pay_month, '-01'))) = YEAR(CURDATE()) AND MONTH(DATE(CONCAT(pay_month, '-01'))) = MONTH(CURDATE())";
            $date_condition_date = " AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())";
        } elseif ($filter === 'year') {
            $date_condition_sales = " AND YEAR(order_date) = YEAR(CURDATE())";
            $date_condition_employee_payments = " AND YEAR(DATE(CONCAT(pay_month, '-01'))) = YEAR(CURDATE())";
            $date_condition_date = " AND YEAR(date) = YEAR(CURDATE())";
        }
    }
    $purchases_query = "SELECT payment_type, SUM(price) as iqd, SUM(amount_iqd) as amount_iqd, SUM(amount_iqd / NULLIF(exchange_rate / 100, 0)) as iqd_converted FROM purchases WHERE type='دینار' $date_condition_date GROUP BY payment_type";
    $stmt = $pdo->query($purchases_query);
    while ($row = $stmt->fetch()) {
        if ($row['payment_type'] === 'نەقد') {
            $purchases['cash']['iqd'] = $row['amount_iqd'] ?? 0; // use total IQD amount
            $purchases['cash']['iqd_converted'] = $row['iqd_converted'] ?? 0;
        } elseif ($row['payment_type'] === 'قەرز') {
            $purchases['credit']['iqd'] = $row['amount_iqd'] ?? 0; // use total IQD amount
            $purchases['credit']['iqd_converted'] = $row['iqd_converted'] ?? 0;
        }
    }
    // دۆلار - Add date filter
    $purchases_usd_query = "SELECT payment_type, SUM(price) as usd FROM purchases WHERE type='دۆلار' $date_condition_date GROUP BY payment_type";
    $stmt = $pdo->query($purchases_usd_query);
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

    // Raw Material Sales (فرۆشتنی مەوادی خام) - Calculate total in USD
    // Convert IQD sales to USD using exchange rate from card (usd_iqd_rate)
    $raw_material_sales_date_condition = "";
    if ($use_range) {
        $from = $from_date ? $from_date : '1000-01-01';
        $to = $to_date ? $to_date : '9999-12-31';
        $raw_material_sales_date_condition = " AND sale_date >= '$from' AND sale_date <= '$to'";
    } else {
        if ($filter === 'today') {
            $raw_material_sales_date_condition = " AND sale_date = CURDATE()";
        } elseif ($filter === 'week') {
            $raw_material_sales_date_condition = " AND YEARWEEK(sale_date, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($filter === 'month') {
            $raw_material_sales_date_condition = " AND YEAR(sale_date) = YEAR(CURDATE()) AND MONTH(sale_date) = MONTH(CURDATE())";
        } elseif ($filter === 'year') {
            $raw_material_sales_date_condition = " AND YEAR(sale_date) = YEAR(CURDATE())";
        }
    }
    
    // Get raw material sales - convert all to USD using usd_iqd_rate from card
    $raw_material_sales_query = "
        SELECT 
            SUM(CASE 
                WHEN currency_type = 'دۆلار' THEN total_price 
                WHEN currency_type = 'دینار' AND ? > 0 THEN total_price / (? / 100)
                ELSE 0 
            END) as total_usd
        FROM raw_material_sales 
        WHERE is_deleted = 0 
        $raw_material_sales_date_condition
    ";
    $stmt = $pdo->prepare($raw_material_sales_query);
    $stmt->execute([$usd_iqd_rate, $usd_iqd_rate]);
    $raw_material_sales_total_usd = floatval($stmt->fetchColumn() ?? 0);
    
    // Calculate cost of raw material sales (تێچووی فرۆشتنی مەوادی خام)
    // Similar to material consumption cost calculation
    // cost_price is stored per kg in raw_material_sales table
    // For USD materials (چیمەنتۆ، دەرمان): cost_price is in USD per kg
    // For IQD materials (چەو، لم، گاز): cost_price is in IQD per kg
    // Total cost = quantity_kg × cost_price
    $raw_material_sales_cost_query = "
        SELECT 
            SUM(
                CASE 
                    WHEN rms.currency_type = 'دۆلار' THEN rms.quantity_kg * rms.cost_price
                    WHEN rms.currency_type = 'دینار' AND ? > 0 THEN (rms.quantity_kg * rms.cost_price) / (? / 100)
                    ELSE 0
                END
            ) as total_cost_usd
        FROM raw_material_sales rms
        WHERE rms.is_deleted = 0 
        $raw_material_sales_date_condition
    ";
    
    $raw_material_sales_cost_total_usd = 0;
    try {
        $stmt = $pdo->prepare($raw_material_sales_cost_query);
        $stmt->execute([$usd_iqd_rate, $usd_iqd_rate]);
        $raw_material_sales_cost_total_usd = floatval($stmt->fetchColumn() ?? 0);
    } catch (Exception $e) {
        error_log("Error calculating raw material sales cost: " . $e->getMessage());
    }

    // Cash Sales - Calculate received amounts in USD and IQD separately
    // تەنها فرۆشتن بە نەقدی (payment_type = 'نەقد') و فلتەری بەروار
    $cash_sales_query = "SELECT 
        SUM(amount_paid_usd) as paid_usd, 
        SUM(amount_paid_iq) as paid_iqd 
        FROM sales 
        WHERE payment_type = 'نەقد' 
        $date_condition_sales";
    $stmt = $pdo->query($cash_sales_query);
    $row = $stmt->fetch();
    $cash_sales_paid_usd = floatval($row['paid_usd'] ?? 0);
    $cash_sales_paid_iqd = floatval($row['paid_iqd'] ?? 0);

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

    // Employee Expenses (خەرجی کارمەند) - filter by salary month
    $employee_expenses_query = "SELECT SUM(total) as total_expenses FROM employee_payments WHERE 1=1 $date_condition_employee_payments";
    $stmt = $pdo->query($employee_expenses_query);
    $row = $stmt->fetch();
    $employee_expenses = $row['total_expenses'] ?? 0;
    $employee_expenses_usd = ($usd_iqd_rate > 0) ? ($employee_expenses / ($usd_iqd_rate / 100)) : 0;

    // Discounts (کۆی داشکاندن) - From sales + customer debt payments
    // Note: discount in sales table is already in USD
    $sales_discounts_query = "SELECT SUM(discount) as total_discount FROM sales WHERE 1=1 $date_condition_sales";
    $stmt = $pdo->query($sales_discounts_query);
    $row = $stmt->fetch();
    // Result is already in USD (discount column in sales table is in USD)
    $sales_discounts = floatval($row['total_discount'] ?? 0);
    
// Customer debt payments discounts (Updated to filter by Sale Date)
    // Note: This calculation already returns USD (discount and allocated_amount are in USD)
    $customer_debt_discounts_query = "
        SELECT SUM(
            CASE 
                WHEN (p.paid_usd + (CASE WHEN p.dolar_rate > 0 THEN p.paid_iqd / (p.dolar_rate / 100) ELSE 0 END) + p.discount) > 0 
                THEN 
                    (p.discount / (p.paid_usd + (CASE WHEN p.dolar_rate > 0 THEN p.paid_iqd / (p.dolar_rate / 100) ELSE 0 END) + p.discount)) * a.allocated_amount
                ELSE 0 
            END
        ) as total_discount 
        FROM customer_payment_allocations a
        JOIN customer_debt_payments p ON a.debt_payment_id = p.id
        JOIN sales s ON a.sale_id = s.id
        WHERE 1=1 $date_condition_sales
    ";
    $stmt = $pdo->query($customer_debt_discounts_query);
    $row = $stmt->fetch();
    // Result is already in USD (p.discount and a.allocated_amount are in USD)
    $customer_debt_discounts = floatval($row['total_discount'] ?? 0);
    
    // Total discounts = sales discounts + customer debt payment discounts (both in USD now)
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
    
    // Gas Income (داهاتی گاز) - Calculate based on specific cars using car_id
    $gas_income_query = "
        SELECT 
            SUM(oe.gas_total_cost) as gas_cost_iqd
        FROM other_expenses oe
        WHERE oe.expense_type = 'بەکارهێنانی گاز' 
        AND oe.car_id IN (
            SELECT id FROM cars WHERE name IN (
                'سانکۆ', 
                'کمال باوکی سانکۆ', 
                'تڕێلەکە', 
                'کەسارەکە/ئەرکان', 
                'کەسارەکە/سامی'
            )
        )
        $date_condition_date
    ";
    
    $stmt = $pdo->query($gas_income_query);
    $row = $stmt->fetch();
    $gas_income_iqd = $row['gas_cost_iqd'] ?? 0;
    
    // Convert IQD to USD using exchange rate
    $gas_income_total_usd = ($usd_iqd_rate > 0) ? ($gas_income_iqd / ($usd_iqd_rate / 100)) : 0;
    
    // Debug: Log gas income calculation
    error_log("Debug - Gas income calculation from specific cars: iqd_amount=" . $gas_income_iqd . 
              ", usd_rate=" . $usd_iqd_rate . ", usd_amount=" . $gas_income_total_usd);

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

    // Calculate Income based on the formula:
    // داهات = کۆی نرخی فرۆشتن + کۆی داهاتی گاز - کۆی نرخی کڕین(purchase) - کۆی داشکاندن - کۆی خەرجی تر (expense_type = خەرجی تر) - کۆی نرخی کڕینی کاڵا(purchase_material) - کۆی خەرجی کارمەندان
    
    // Get total sales (cash + credit)
    $total_sales_usd = $current_period_sales;
    
    // Get gas income (from other_expenses where expense_type = 'بەکارهێنانی گاز')
    try {
        $gas_income_query = "SELECT 
            SUM(CASE 
                WHEN currency_type = 'دۆلار' THEN amount_usd 
                WHEN currency_type = 'دینار' THEN amount_iqd / NULLIF(exchange_rate / 100, 0)
                WHEN currency_type = 'تێکەڵ' THEN amount_usd + (amount_iqd / NULLIF(exchange_rate / 100, 0))
                ELSE 0 
            END) as gas_income_usd
            FROM other_expenses 
            WHERE expense_type = 'بەکارهێنانی گاز' 
            AND date BETWEEN ? AND ?";
        $stmt = $pdo->prepare($gas_income_query);
        $stmt->execute([$from_date, $to_date]);
        $gas_income_usd = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        error_log("Error calculating gas income: " . $e->getMessage());
        $gas_income_usd = 0;
    }
    
    // Get total purchases (cash + credit)
    $total_purchases_usd = $current_period_purchases;
    
    // Get total discounts
    $total_discounts = 0;
    // Sales discounts - Already in USD (discount column in sales table is in USD)
    $stmt = $pdo->query("SELECT SUM(discount) as total_discount FROM sales WHERE 1=1 $date_condition_sales");
    $row = $stmt->fetch();
    $sales_discounts_total = floatval($row['total_discount'] ?? 0);
    
    // Customer debt payment discounts (Reusing calculated value - already converted to USD)
    $customer_debt_discounts_total = $customer_debt_discounts;
    
        // Total discounts = sales + customer debt payments (both in USD now)
    $total_discounts = $sales_discounts_total + $customer_debt_discounts_total;
    
    // Get other expenses (expense_type = 'خەرجی تر')
    try {
        $other_expenses_query = "SELECT 
            SUM(CASE 
                WHEN currency_type = 'دۆلار' THEN amount_usd 
                WHEN currency_type = 'دینار' THEN amount_iqd / NULLIF(exchange_rate / 100, 0)
                WHEN currency_type = 'تێکەڵ' THEN amount_usd + (amount_iqd / NULLIF(exchange_rate / 100, 0))
                ELSE 0 
            END) as other_expenses_usd
            FROM other_expenses 
            WHERE expense_type = 'خەرجی تر' 
            AND date BETWEEN ? AND ?";
        $stmt = $pdo->prepare($other_expenses_query);
        $stmt->execute([$from_date, $to_date]);
        $other_expenses_usd = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        error_log("Error calculating other expenses: " . $e->getMessage());
        $other_expenses_usd = 0;
    }
    
    // Get purchase materials total
    try {
        $purchase_materials_query = "SELECT 
            SUM(CASE 
                WHEN currency_type = 'دۆلار' THEN total_price_usd 
                WHEN currency_type = 'دینار' THEN total_price_iqd / NULLIF(usd_to_iqd_rate / 100, 0)
                ELSE 0 
            END) as purchase_materials_usd
            FROM purchase_materials 
            WHERE purchase_date BETWEEN ? AND ?";
        $stmt = $pdo->prepare($purchase_materials_query);
        $stmt->execute([$from_date, $to_date]);
        $purchase_materials_usd = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        error_log("Error calculating purchase materials: " . $e->getMessage());
        $purchase_materials_usd = 0;
    }
    
    // Get employee payments total
    try {
        $employee_payments_query = "SELECT 
            SUM(total) / NULLIF(?, 0) as employee_payments_usd
            FROM employee_payments 
            WHERE DATE(CONCAT(pay_month, '-01')) BETWEEN ? AND ?";
        $stmt = $pdo->prepare($employee_payments_query);
        $stmt->execute([$usd_iqd_rate / 100, $from_date, $to_date]);
        $employee_payments_usd = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        error_log("Error calculating employee payments: " . $e->getMessage());
        $employee_payments_usd = 0;
    }
    
    // Calculate total income
    $total_income_usd = $total_sales_usd + $gas_income_usd - $total_purchases_usd - $total_discounts - $other_expenses_usd - $purchase_materials_usd - $employee_payments_usd;
    
    // Debug: Log income calculation breakdown
    error_log("Debug - Income calculation: sales=" . $total_sales_usd . 
              ", gas_income=" . $gas_income_usd . 
              ", purchases=" . $total_purchases_usd . 
              ", discounts=" . $total_discounts . 
              ", other_expenses=" . $other_expenses_usd . 
              ", purchase_materials=" . $purchase_materials_usd . 
              ", employee_payments=" . $employee_payments_usd . 
              ", total_income=" . $total_income_usd);
    
    // Debug: Log discount breakdown
    error_log("Debug - Discounts breakdown: sales_discounts=" . $sales_discounts_total . 
              ", customer_debt_discounts=" . $customer_debt_discounts_total . 
              ", total_discounts=" . $total_discounts);
    


    // Calculate material consumption based on sales and formulas
    // cement_cem1 = دەلتا + لاڤارج (لە سایلۆی یەکدا)
    // cement_cem2 = ماس (لە سایلۆی دوودا)
    // Total cement = دەلتا + لاڤارج + ماس
    $material_consumption = [
        'black_sand' => 0,      // لمی کەسارە (چاوی ١)
        'brown_sand' => 0,      // لمی ڕەش (چاوی ٢)  
        'gravel_bin3' => 0,     // چەوی چاوی ٣
        'gravel_bin4' => 0,     // چەوی چاوی ٤
        'cement_cem1' => 0,     // چیمەنتۆی سایلۆی ١ (دەلتا + لاڤارج)
        'cement_cem2' => 0,     // چیمەنتۆی سایلۆی ٢ (ماس)
        'additive' => 0         // زیادکراو (دەرمان)
    ];
    
    // Get material consumption from sales based on formulas used
    // cement_cem1_kg = دەلتا + لاڤارج (لە سایلۆی یەکدا)
    // cement_cem2_kg = ماس (لە سایلۆی دوودا)
    // Total cement = دەلتا + لاڤارج + ماس
    $material_consumption_query = "
        SELECT 
            s.quantity as cubic_meters,
            cf.black_sand_kg,
            cf.brown_sand_kg,
            cf.gravel_bin3_kg,
            cf.gravel_bin4_kg,
            cf.cement_cem1_kg,
            cf.cement_cem2_kg,
            cf.additive_kg
        FROM sales s
        JOIN concrete_formulas cf ON s.formula_id = cf.id
        WHERE 1=1 $date_condition_sales
    ";
    
    try {
        $stmt = $pdo->query($material_consumption_query);
        while ($row = $stmt->fetch()) {
            $cubic_meters = floatval($row['cubic_meters']);
            
            // Calculate material consumption for this sale
            $material_consumption['black_sand'] += floatval($row['black_sand_kg']) * $cubic_meters;
            $material_consumption['brown_sand'] += floatval($row['brown_sand_kg']) * $cubic_meters;
            $material_consumption['gravel_bin3'] += floatval($row['gravel_bin3_kg']) * $cubic_meters;
            $material_consumption['gravel_bin4'] += floatval($row['gravel_bin4_kg']) * $cubic_meters;
            $material_consumption['cement_cem1'] += floatval($row['cement_cem1_kg']) * $cubic_meters;
            $material_consumption['cement_cem2'] += floatval($row['cement_cem2_kg']) * $cubic_meters;
            $material_consumption['additive'] += floatval($row['additive_kg']) * $cubic_meters;
        }
    } catch (Exception $e) {
        error_log("Error calculating material consumption: " . $e->getMessage());
    }
    
    // Convert kg to tons for better readability (1 ton = 1000 kg)
    // cement_cem1_tons = دەلتا + لاڤارج (تۆن)
    // cement_cem2_tons = ماس (تۆن)
    $material_consumption_tons = [];
    foreach ($material_consumption as $material => $kg_amount) {
        $material_consumption_tons[$material] = round($kg_amount / 1000, 3);
    }

    // Calculate average purchase prices per ton (USD)
    $material_prices = [
        'black_sand' => 0,
        'brown_sand' => 0,
        'gravel' => 0,
        'cement' => 0,
        'additive' => 0
    ];

    try {
        // First try getting filtered average
        $avg_query = "
            SELECT 
                m.name,
                SUM(CASE WHEN p.type = 'دۆلار' THEN p.price ELSE p.amount_iqd / NULLIF(p.exchange_rate / 100, 0) END) as total_usd,
                SUM(p.kg) as total_kg
            FROM purchases p
            JOIN materials m ON p.material_id = m.id
            WHERE p.kg > 0 $date_condition_date
            GROUP BY m.name
        ";
        $stmt_avg = $pdo->query($avg_query);
        while ($row = $stmt_avg->fetch()) {
            $price_per_ton = ($row['total_kg'] > 0) ? ($row['total_usd'] / $row['total_kg'] * 1000) : 0;
            $m_name = $row['name'];
            if ($m_name == 'لمی کەسارە') $material_prices['black_sand'] = $price_per_ton;
            elseif ($m_name == 'لمی ڕەش') $material_prices['brown_sand'] = $price_per_ton;
            elseif ($m_name == 'چەو') $material_prices['gravel'] = $price_per_ton;
            elseif ($m_name == 'چیمەنتۆ') $material_prices['cement'] = $price_per_ton;
            elseif ($m_name == 'دەرمان') $material_prices['additive'] = $price_per_ton;
        }

        // Fallback for any material that still has 0 price (if no purchases in filtered period)
        $has_zero = false;
        foreach($material_prices as $p) { if($p == 0) { $has_zero = true; break; } }
        
        if ($has_zero) {
             $global_avg_query = "
                SELECT 
                    m.name,
                    SUM(CASE WHEN p.type = 'دۆلار' THEN p.price ELSE p.amount_iqd / NULLIF(p.exchange_rate / 100, 0) END) as total_usd,
                    SUM(p.kg) as total_kg
                FROM purchases p
                JOIN materials m ON p.material_id = m.id
                WHERE p.kg > 0
                GROUP BY m.name
            ";
            $stmt_global = $pdo->query($global_avg_query);
            while ($row = $stmt_global->fetch()) {
                $m_name = $row['name'];
                $price_per_ton = ($row['total_kg'] > 0) ? ($row['total_usd'] / $row['total_kg'] * 1000) : 0;
                
                if ($m_name == 'لمی کەسارە' && $material_prices['black_sand'] == 0) $material_prices['black_sand'] = $price_per_ton;
                elseif ($m_name == 'لمی ڕەش' && $material_prices['brown_sand'] == 0) $material_prices['brown_sand'] = $price_per_ton;
                elseif ($m_name == 'چەو' && $material_prices['gravel'] == 0) $material_prices['gravel'] = $price_per_ton;
                elseif ($m_name == 'چیمەنتۆ' && $material_prices['cement'] == 0) $material_prices['cement'] = $price_per_ton;
                elseif ($m_name == 'دەرمان' && $material_prices['additive'] == 0) $material_prices['additive'] = $price_per_ton;
            }
        }
    } catch (Exception $e) {
        error_log("Error calculating material prices: " . $e->getMessage());
    }

    // Calculate costs for each consumption category
    $material_costs = [
        'black_sand' => $material_consumption_tons['black_sand'] * $material_prices['black_sand'],
        'brown_sand' => $material_consumption_tons['brown_sand'] * $material_prices['brown_sand'],
        'gravel_bin3' => $material_consumption_tons['gravel_bin3'] * $material_prices['gravel'],
        'gravel_bin4' => $material_consumption_tons['gravel_bin4'] * $material_prices['gravel'],
        'cement_cem1' => $material_consumption_tons['cement_cem1'] * $material_prices['cement'],
        'cement_cem2' => $material_consumption_tons['cement_cem2'] * $material_prices['cement'],
        'additive' => $material_consumption_tons['additive'] * $material_prices['additive']
    ];

    $total_used_material_cost_usd = array_sum($material_costs);
    
    // Get current stock levels for comparison
    // سایلۆی یەک: دەلتا + لاڤارج
    // سایلۆی دوو: ماس
    // Total cement stock = دەلتا + لاڤارج + ماس
    $current_stock = [];
    $stock_query = "
        SELECT 
            material_type,
            SUM(amount) as total_amount,
            SUM(total_value) as total_value
        FROM bins_silos 
        GROUP BY material_type
    ";
    
    try {
        $stmt = $pdo->query($stock_query);
        while ($row = $stmt->fetch()) {
            $current_stock[$row['material_type']] = [
                'amount' => floatval($row['total_amount']),
                'value' => floatval($row['total_value'])
            ];
        }
    } catch (Exception $e) {
        error_log("Error getting current stock: " . $e->getMessage());
    }

    // Additional Professional Reports Data
    
    // Employee Reports
    $employee_stats = [];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
    $employee_stats['total'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(salary) as total_salary FROM employees");
    $employee_stats['total_salary'] = $stmt->fetchColumn() ?: 0;
    
    // Calculate Total Monthly Fixed Cost (Salary + Bonus) for Active Employees
    // کۆی مووچە + بەخشیش بۆ کارمەندە چالاکەکان
    $stmt = $pdo->query("SELECT SUM(salary + COALESCE(bonus, 0)) as total_fixed FROM employees WHERE status = 'active'");
    $total_fixed_iqd = $stmt->fetchColumn() ?: 0;
    $employee_stats['total_fixed_usd'] = ($usd_iqd_rate > 0) ? ($total_fixed_iqd / ($usd_iqd_rate / 100)) : 0;

    // Count drivers - check for any role containing 'شۆفێر' (supports multiple roles)
    $stmt = $pdo->query("SELECT COUNT(*) as drivers FROM employees WHERE role LIKE '%شۆفێر%' OR role LIKE '%سایەق%'");
    $employee_stats['drivers'] = $stmt->fetchColumn();
    
    // Count accountants - check for 'ژمێریار' or 'موحاسیب' (supports multiple roles)
    $stmt = $pdo->query("SELECT COUNT(*) as accountants FROM employees WHERE role LIKE '%ژمێریار%' OR role LIKE '%موحاسیب%'");
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
        
    // Employee payments - use pay_month (salary month)
        $stmt = $pdo->prepare("
            SELECT SUM(total) as total_employee_payments 
            FROM employee_payments 
            WHERE DATE_FORMAT(CONCAT(pay_month, '-01'), '%Y-%m') = ?
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
    if (!isset($total_discount)) $total_discount = 0;
    
    // 6. Debt Analysis
    $debt_analysis = [
        'customer_debt' => $customer_debt_total_usd,
        'company_debt' => $company_debt_total_usd,
        'person_debt' => $person_debt_usd
    ];

    // Caravan Hisabi (کاروان حیسابی) - Calculate from concrete_receipts table
    // This is overtime payment for mixer drivers based on concrete receipts
    // Same calculation method as in get_expenses_summary.php
    
    // Get Overtime Rate from settings
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'overtime_rate'");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);
    $overtime_rate = floatval($setting['value'] ?? 0);
    
    // Build date condition for concrete_receipts
    $caravan_hisabi_date_condition = "";
    $caravan_hisabi_date_params = [];
    
    if ($use_range) {
        $from = $from_date ? $from_date : '1000-01-01';
        $to = $to_date ? $to_date : '9999-12-31';
        $caravan_hisabi_date_condition = " AND COALESCE(`date`, DATE(created_at)) BETWEEN ? AND ?";
        $caravan_hisabi_date_params = [$from, $to];
    } else {
        if ($filter === 'today') {
            $today = date('Y-m-d');
            $caravan_hisabi_date_condition = " AND COALESCE(`date`, DATE(created_at)) = ?";
            $caravan_hisabi_date_params = [$today];
        } elseif ($filter === 'week') {
            $week_start = date('Y-m-d', strtotime('monday this week'));
            $week_end = date('Y-m-d', strtotime('sunday this week'));
            $caravan_hisabi_date_condition = " AND COALESCE(`date`, DATE(created_at)) BETWEEN ? AND ?";
            $caravan_hisabi_date_params = [$week_start, $week_end];
        } elseif ($filter === 'month') {
            $month_start = date('Y-m-01');
            $month_end = date('Y-m-t');
            $caravan_hisabi_date_condition = " AND COALESCE(`date`, DATE(created_at)) BETWEEN ? AND ?";
            $caravan_hisabi_date_params = [$month_start, $month_end];
        } elseif ($filter === 'year') {
            $year_start = date('Y-01-01');
            $year_end = date('Y-12-31');
            $caravan_hisabi_date_condition = " AND COALESCE(`date`, DATE(created_at)) BETWEEN ? AND ?";
            $caravan_hisabi_date_params = [$year_start, $year_end];
        }
    }
    
    // Get all employees with role "شۆفێری میکسەر" (same as get_expenses_summary.php)
    $mixer_driver_ids = [];
    try {
        // Check if status column exists
        $status_exists = false;
        try {
            $check_status = $pdo->query("SHOW COLUMNS FROM employees LIKE 'status'");
            $status_exists = $check_status->rowCount() > 0;
        } catch (Exception $e) {}
        
        $emp_sql = "SELECT id FROM employees WHERE role LIKE '%شۆفێری میکسەر%'";
        if ($status_exists) {
            $emp_sql .= " AND status = 'active'";
        }
        
        $stmt = $pdo->query($emp_sql);
        $mixer_drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mixer_driver_ids = array_column($mixer_drivers, 'id');
    } catch (Exception $e) {
        error_log("Error getting mixer drivers: " . $e->getMessage());
    }
    
    // Calculate total overtime from concrete_receipts (same method as get_expenses_summary.php)
    $caravan_hisabi_iqd = 0;
    if (!empty($mixer_driver_ids)) {
        $placeholders = implode(',', array_fill(0, count($mixer_driver_ids), '?'));
        
        try {
            // Use COALESCE to fallback to created_at date if date column is NULL (same as get_expenses_summary.php)
            // Only count mixer_driver_id (not pump_driver_id) - same as get_expenses_summary.php
            $overtime_sql = "SELECT COUNT(*) as count 
                           FROM concrete_receipts 
                           WHERE mixer_driver_id IN ($placeholders)
                           AND COALESCE(`date`, DATE(created_at)) BETWEEN ? AND ?";
            
            $overtime_params = array_merge($mixer_driver_ids, $caravan_hisabi_date_params);
            
            $stmt = $pdo->prepare($overtime_sql);
            $stmt->execute($overtime_params);
            $overtime_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $receipt_count = intval($overtime_result['count'] ?? 0);
            
            // Calculate overtime amount: receipt count × overtime rate (same as get_expenses_summary.php)
            $caravan_hisabi_iqd = $receipt_count * $overtime_rate;
        } catch (Exception $e) {
            error_log("Error calculating caravan hisabi: " . $e->getMessage());
            // Fallback: try with created_at only (same as get_expenses_summary.php)
            try {
                $overtime_sql = "SELECT COUNT(*) as count 
                               FROM concrete_receipts 
                               WHERE mixer_driver_id IN ($placeholders)
                               AND created_at BETWEEN ? AND ?";
                
                $date_start = $caravan_hisabi_date_params[0] . ' 00:00:00';
                $date_end = (count($caravan_hisabi_date_params) > 1 ? $caravan_hisabi_date_params[1] : $caravan_hisabi_date_params[0]) . ' 23:59:59';
                
                $overtime_params = array_merge($mixer_driver_ids, [$date_start, $date_end]);
                
                $stmt = $pdo->prepare($overtime_sql);
                $stmt->execute($overtime_params);
                $overtime_result = $stmt->fetch(PDO::FETCH_ASSOC);
                $receipt_count = intval($overtime_result['count'] ?? 0);
                
                $caravan_hisabi_iqd = $receipt_count * $overtime_rate;
            } catch (Exception $e2) {
                error_log("Error calculating caravan hisabi (fallback): " . $e2->getMessage());
            }
        }
    }
    
    // Convert IQD to USD
    // usd_iqd_rate is the rate for 100 dollars, so we need to divide by 100 to get rate per dollar
    // Formula: amount_iqd / (usd_iqd_rate / 100) = amount_iqd / (rate_for_100_dollars / 100)
    // Example: 555000 / (146500 / 100) = 555000 / 1465 = 378.83 USD
    $caravan_hisabi_usd = ($usd_iqd_rate > 0) ? ($caravan_hisabi_iqd / ($usd_iqd_rate / 100)) : 0;
    
    // Debug: Log caravan hisabi calculation
    error_log("Debug - Caravan Hisabi: iqd=" . $caravan_hisabi_iqd . 
              ", usd_rate=" . $usd_iqd_rate . 
              ", usd=" . $caravan_hisabi_usd);

    // Debug: Log key variables
    error_log("Debug - Key variables: customer_debt_total_usd=" . $customer_debt_total_usd . 
              ", company_debt_total_usd=" . $company_debt_total_usd . 
              ", person_debt_usd=" . $person_debt_usd . 
              ", total_expenses_usd=" . $total_expenses_usd);
    
    // Prepare response data
    $response_data = [
        'success' => true,
        'data' => [
            'usd_iqd_rate' => $usd_iqd_rate,
            'customer' => ['usd' => $customer_debt_total_usd, 'iqd' => 0],
            'company' => ['usd' => $company_debt_total_usd, 'iqd' => 0],
            'person' => ['usd' => $person_debt_usd, 'iqd' => $person_debt_iqd],
            'purchases' => $purchases,
            'sales' => $sales,
            'raw_material_sales' => [
                'total_usd' => $raw_material_sales_total_usd,
                'cost_usd' => $raw_material_sales_cost_total_usd
            ],
            'car_expenses' => [
                'total_usd' => ($total_expenses_breakdown['material_usage'] ?? 0) + ($total_expenses_breakdown['gas_usage'] ?? 0)
            ],
            'caravan_hisabi' => [
                'total_usd' => $caravan_hisabi_usd,
                'total_iqd' => $caravan_hisabi_iqd
            ],
            'profit_loss' => [
                'total_revenue' => ($sales['cash']['usd'] ?? 0) + ($sales['credit']['usd'] ?? 0) + $raw_material_sales_total_usd,
                'total_cost' => ($total_used_material_cost_usd ?? 0) + ($employee_stats['total_fixed_usd'] ?? 0) + $caravan_hisabi_usd + (($total_expenses_breakdown['material_usage'] ?? 0) + ($total_expenses_breakdown['gas_usage'] ?? 0)) + $total_expenses_usd + ($total_discount ?? 0),
                'profit_loss' => (($sales['cash']['usd'] ?? 0) + ($sales['credit']['usd'] ?? 0) + $raw_material_sales_total_usd) - (($total_used_material_cost_usd ?? 0) + ($employee_stats['total_fixed_usd'] ?? 0) + $caravan_hisabi_usd + (($total_expenses_breakdown['material_usage'] ?? 0) + ($total_expenses_breakdown['gas_usage'] ?? 0)) + $total_expenses_usd + ($total_discount ?? 0))
            ],
            'discounts' => [
                'total_usd' => $total_discount,
                'sales_usd' => $sales_discounts,
                'customer_debt_usd' => $customer_debt_discounts
            ],
            'gas_income' => [
                'usd' => $gas_income_total_usd
            ],
            'customer_debt_payments' => [
                'usd' => $customer_debt_payments_total_usd,
                'iqd' => $customer_debt_payments_iqd,
                'usd_amount' => $customer_debt_payments_usd
            ],
            'person_debt_payments' => [
                'usd' => $person_debt_payments_total_usd,
                'iqd' => $person_debt_payments_iqd,
                'usd_amount' => $person_debt_payments_usd
            ],
            'company_debt_payments' => [
                'usd' => $debt_payments_total_usd,
                'iqd' => $debt_payments_iqd,
                'usd_amount' => $debt_payments_usd
            ],
            'cash_sales' => [
                'paid_usd' => $cash_sales_paid_usd,
                'paid_iqd' => $cash_sales_paid_iqd
            ],
            'total_expenses' => [
                'usd' => $total_expenses_usd,
                'breakdown' => $total_expenses_breakdown
            ],
            'income' => [
                'usd' => $total_income_usd,
                'breakdown' => [
                    'sales' => $total_sales_usd,
                    'gas_income' => $gas_income_usd,
                    'purchases' => $total_purchases_usd,
                    'discounts' => $total_discounts,
                    'other_expenses' => $other_expenses_usd,
                    'purchase_materials' => $purchase_materials_usd,
                    'employee_payments' => $employee_payments_usd
                ]
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
            ],
                // Material consumption data
    // cement_cem1: دەلتا + لاڤارج (سایلۆی یەک)
    // cement_cem2: ماس (سایلۆی دوو)
    'material_consumption' => [
        'kg' => $material_consumption,
        'tons' => $material_consumption_tons,
        'prices' => $material_prices,
        'costs' => $material_costs,
        'total_cost_usd' => $total_used_material_cost_usd,
        'current_stock' => $current_stock
    ]
        ]
    ];
    
    // Debug: Log material consumption data
    // Note: cement_cem1 includes both دەلتا and لاڤارج from سایلۆی ١
    // Note: cement_cem2 includes ماس from سایلۆی ٢
    // Total cement consumption = دەلتا + لاڤارج + ماس
    error_log("Debug - Material consumption: " . json_encode($material_consumption));
    error_log("Debug - Material consumption tons: " . json_encode($material_consumption_tons));
    error_log("Debug - Current stock: " . json_encode($current_stock));
    
    // Debug: Log response structure
    error_log("Debug - Response structure: " . json_encode($response_data));
    
    // Material consumption summary:
    // - cement_cem1: دەلتا + لاڤارج (سایلۆی یەک)
    // - cement_cem2: ماس (سایلۆی دوو)
    // - Total cement consumption = دەلتا + لاڤارج + ماس
    // - Total material consumption = لمی کەسارە + لمی ڕەش + چەوی چاوی ٣ + چەوی چاوی ٤ + دەلتا + لاڤارج + ماس
    
    // 2. Debts by type - Updated to use new calculation method
    $debts_by_type = [
        'customers' => 0,
        'companies' => 0,
        'persons' => 0
    ];
    
    // Customers debt (opening_debt + remaining from sales) - ڕاستکردنەوەی هەژمارکردنی قەرز
    // 1. قەرزی سەرەتایی (USD)
    $openingDebtUSD = $pdo->query("SELECT COALESCE(SUM(opening_debt_usd), 0) FROM customers")->fetchColumn();
    
    // 2. کۆی ماوەی قەرز لە فرۆشتنەکان (تەنها ئەوانەی amount_paid_iq = 0)
    $salesRemainingUSD = $pdo->query("
        SELECT COALESCE(SUM(remaining_amount), 0) 
        FROM sales 
        WHERE payment_type = 'قەرز' 
        AND amount_paid_iq = 0
    ")->fetchColumn();
    
    // 3. کۆی ماوەی قەرز لە فرۆشتنەکان (دینار - ئەوانەی amount_paid_iq > 0)
    $salesRemainingIQD = $pdo->query("
        SELECT COALESCE(SUM(remaining_amount), 0) 
        FROM sales 
        WHERE payment_type = 'قەرز' 
        AND amount_paid_iq > 0
    ")->fetchColumn();
    
    // 4. قەرزی سەرەتایی (IQD) - گۆڕینی بۆ دۆلار
    $openingDebtIQD = $pdo->query("SELECT COALESCE(SUM(opening_debt_iqd), 0) FROM customers")->fetchColumn();
    $openingDebtIQD_USD = $usd_iqd_rate > 0 ? ($openingDebtIQD / ($usd_iqd_rate / 100)) : 0;
    
    // 5. کۆکردنەوەی هەموو قەرزەکان بە دۆلار
    // فۆرمۆلا: کۆی قەرز = پارەی ماوەی فرۆشتنەکان + قەرزی سەرەتایی
    $debts_by_type['customers'] = floatval($openingDebtUSD) +           // قەرزی سەرەتایی (USD)
                                  floatval($openingDebtIQD_USD) +        // قەرزی سەرەتایی (IQD → USD)
                                  floatval($salesRemainingUSD) +         // پارەی ماوەی فرۆشتنەکان (USD)
                                  (floatval($salesRemainingIQD) / ($usd_iqd_rate / 100)); // پارەی ماوەی فرۆشتنەکان (IQD → USD)
    
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

    // Add employee stats to response data
    $response_data['data']['employee_stats'] = $employee_stats;

    echo json_encode($response_data);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}
