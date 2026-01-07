<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    // Get filter parameters
    $filter = $_GET['filter'] ?? 'year';
    $from_date = $_GET['from_date'] ?? null;
    $to_date = $_GET['to_date'] ?? null;
    
    // Build date condition
    $date_condition = '';
    $date_condition_sales = '';
    $date_condition_date = '';
    
    if ($from_date && $to_date) {
        $date_condition = " AND DATE(cb.date) BETWEEN '$from_date' AND '$to_date'";
        $date_condition_sales = " AND DATE(s.order_date) BETWEEN '$from_date' AND '$to_date'";
        $date_condition_date = " AND DATE(p.date) BETWEEN '$from_date' AND '$to_date'";
    } else {
        switch ($filter) {
            case 'today':
                $date_condition = " AND DATE(cb.date) = CURDATE()";
                $date_condition_sales = " AND DATE(s.order_date) = CURDATE()";
                $date_condition_date = " AND DATE(p.date) = CURDATE()";
                break;
            case 'week':
                $date_condition = " AND YEARWEEK(cb.date, 1) = YEARWEEK(CURDATE(), 1)";
                $date_condition_sales = " AND YEARWEEK(s.order_date, 1) = YEARWEEK(CURDATE(), 1)";
                $date_condition_date = " AND YEARWEEK(p.date, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $date_condition = " AND YEAR(cb.date) = YEAR(CURDATE()) AND MONTH(cb.date) = MONTH(CURDATE())";
                $date_condition_sales = " AND YEAR(s.order_date) = YEAR(CURDATE()) AND MONTH(s.order_date) = MONTH(CURDATE())";
                $date_condition_date = " AND YEAR(p.date) = YEAR(CURDATE()) AND MONTH(p.date) = MONTH(CURDATE())";
                break;
            case 'year':
            default:
                $date_condition = " AND YEAR(cb.date) = YEAR(CURDATE())";
                $date_condition_sales = " AND YEAR(s.order_date) = YEAR(CURDATE())";
                $date_condition_date = " AND YEAR(p.date) = YEAR(CURDATE())";
                break;
        }
    }
    
    // Get exchange rate
    $usd_iqd_rate = 139250; // Default fallback value
    $rate_query = $pdo->query("SELECT usd_iqd_rate FROM information WHERE id = 1");
    if ($rate_row = $rate_query->fetch()) {
        $usd_iqd_rate = floatval($rate_row['usd_iqd_rate'] ?? 139250);
    }
    
    // ============================================
    // 1. هەژمارکردنی تێچووی مەواد بۆ 1 م³ کۆنکرێت
    // ============================================
    
    // بەدەستهێنانی کۆی م³ فرۆشراو
    $total_cubic_meters_query = "
        SELECT COALESCE(SUM(quantity), 0) as total_m3
        FROM sales
        WHERE 1=1 $date_condition_sales
    ";
    $total_cubic_meters = floatval($pdo->query($total_cubic_meters_query)->fetchColumn());
    
    if ($total_cubic_meters == 0) {
        echo json_encode([
            'success' => true,
            'data' => [
                'total_cubic_meters' => 0,
                'material_cost_per_m3' => 0,
                'employee_cost_per_m3' => 0,
                'vehicle_cost_per_m3' => 0,
                'daily_expense_cost_per_m3' => 0,
                'total_cost_per_m3' => 0,
                'average_revenue_per_m3' => 0,
                'gross_profit_per_m3' => 0,
                'profit_margin_percent' => 0,
                'explanation' => 'هیچ فرۆشتنێک نییە بۆ هەژمارکردنی قازانج'
            ]
        ]);
        exit;
    }
    
    // بەدەستهێنانی بەکارهێنانی مەواد لە فرۆشتنەکان
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
    
    $material_consumption = [
        'black_sand' => 0,
        'brown_sand' => 0,
        'gravel_bin3' => 0,
        'gravel_bin4' => 0,
        'cement_cem1' => 0,
        'cement_cem2' => 0,
        'additive' => 0
    ];
    
    $stmt = $pdo->query($material_consumption_query);
    while ($row = $stmt->fetch()) {
        $cubic_meters = floatval($row['cubic_meters']);
        $material_consumption['black_sand'] += floatval($row['black_sand_kg']) * $cubic_meters;
        $material_consumption['brown_sand'] += floatval($row['brown_sand_kg']) * $cubic_meters;
        $material_consumption['gravel_bin3'] += floatval($row['gravel_bin3_kg']) * $cubic_meters;
        $material_consumption['gravel_bin4'] += floatval($row['gravel_bin4_kg']) * $cubic_meters;
        $material_consumption['cement_cem1'] += floatval($row['cement_cem1_kg']) * $cubic_meters;
        $material_consumption['cement_cem2'] += floatval($row['cement_cem2_kg']) * $cubic_meters;
        $material_consumption['additive'] += floatval($row['additive_kg']) * $cubic_meters;
    }
    
    // بەدەستهێنانی نرخی مامناوەندی مەوادەکان (بە دۆلار بۆ کیلۆگرام)
    $material_prices = [
        'black_sand' => 0,
        'brown_sand' => 0,
        'gravel' => 0,
        'cement' => 0,
        'additive' => 0
    ];
    
    // بەکارهێنانی average_price لە bins_silos تەیبڵ
    $bins_query = "
        SELECT 
            material_type,
            average_price
        FROM bins_silos
        WHERE average_price > 0
    ";
    $bins_stmt = $pdo->query($bins_query);
    while ($bin_row = $bins_stmt->fetch()) {
        $material_type = $bin_row['material_type'];
        $avg_price = floatval($bin_row['average_price']);
        
        if ($material_type == 'لمی کەسارە' && $material_prices['black_sand'] == 0) {
            $material_prices['black_sand'] = $avg_price / 1000; // Convert to USD per kg
        } elseif ($material_type == 'لمی ڕەش' && $material_prices['brown_sand'] == 0) {
            $material_prices['brown_sand'] = $avg_price / 1000;
        } elseif ($material_type == 'چەو' && $material_prices['gravel'] == 0) {
            $material_prices['gravel'] = $avg_price / 1000;
        } elseif ($material_type == 'چیمەنتۆ' && $material_prices['cement'] == 0) {
            $material_prices['cement'] = $avg_price / 1000;
        } elseif ($material_type == 'دەرمان' && $material_prices['additive'] == 0) {
            $material_prices['additive'] = $avg_price / 1000;
        }
    }
    
    // Fallback: بەکارهێنانی نرخی کڕین لە purchases تەیبڵ
    $purchases_query = "
        SELECT 
            m.name,
            AVG(CASE 
                WHEN p.type = 'دۆلار' THEN p.price_per_kg_usd
                ELSE p.price_per_kg_iqd / NULLIF(p.exchange_rate / 100, 0)
            END) as avg_price_per_kg
        FROM purchases p
        JOIN materials m ON p.material_id = m.id
        WHERE p.kg > 0 $date_condition_date
        GROUP BY m.name
    ";
    $purchases_stmt = $pdo->query($purchases_query);
    while ($purchase_row = $purchases_stmt->fetch()) {
        $m_name = $purchase_row['name'];
        $avg_price = floatval($purchase_row['avg_price_per_kg']);
        
        if ($m_name == 'لمی کەسارە' && $material_prices['black_sand'] == 0) {
            $material_prices['black_sand'] = $avg_price;
        } elseif ($m_name == 'لمی ڕەش' && $material_prices['brown_sand'] == 0) {
            $material_prices['brown_sand'] = $avg_price;
        } elseif ($m_name == 'چەو' && $material_prices['gravel'] == 0) {
            $material_prices['gravel'] = $avg_price;
        } elseif ($m_name == 'چیمەنتۆ' && $material_prices['cement'] == 0) {
            $material_prices['cement'] = $avg_price;
        } elseif ($m_name == 'دەرمان' && $material_prices['additive'] == 0) {
            $material_prices['additive'] = $avg_price;
        }
    }
    
    // هەژمارکردنی تێچووی مەواد بۆ هەموو فرۆشتنەکان
    $total_material_cost_usd = 
        ($material_consumption['black_sand'] * $material_prices['black_sand']) +
        ($material_consumption['brown_sand'] * $material_prices['brown_sand']) +
        ($material_consumption['gravel_bin3'] * $material_prices['gravel']) +
        ($material_consumption['gravel_bin4'] * $material_prices['gravel']) +
        ($material_consumption['cement_cem1'] * $material_prices['cement']) +
        ($material_consumption['cement_cem2'] * $material_prices['cement']) +
        ($material_consumption['additive'] * $material_prices['additive']);
    
    // تێچووی مەواد بۆ 1 م³
    $material_cost_per_m3 = $total_cubic_meters > 0 ? ($total_material_cost_usd / $total_cubic_meters) : 0;
    
    // ============================================
    // 2. هەژمارکردنی تێچووی کارمەند بۆ 1 م³
    // ============================================
    
    // کۆی مووچەی کارمەندەکان (لە employee_expenses تەیبڵ)
    // expense_date بە فۆرماتی YYYY-MM ە
    $employee_date_condition = '';
    if ($from_date && $to_date) {
        $from_year_month = substr($from_date, 0, 7); // YYYY-MM
        $to_year_month = substr($to_date, 0, 7);
        $employee_date_condition = " AND expense_date >= '$from_year_month' AND expense_date <= '$to_year_month'";
    } else {
        switch ($filter) {
            case 'today':
                $current_month = date('Y-m');
                $employee_date_condition = " AND expense_date = '$current_month'";
                break;
            case 'week':
            case 'month':
                $current_month = date('Y-m');
                $employee_date_condition = " AND expense_date = '$current_month'";
                break;
            case 'year':
            default:
                $current_year = date('Y');
                $employee_date_condition = " AND expense_date LIKE '$current_year-%'";
                break;
        }
    }
    
    $employee_salary_query = "
        SELECT COALESCE(SUM(amount), 0) as total_salary
        FROM employee_expenses
        WHERE expense_type = 'salary' $employee_date_condition
    ";
    $total_employee_salary = floatval($pdo->query($employee_salary_query)->fetchColumn());
    
    // گۆڕینی بۆ دۆلار (ئەگەر بە دینار بوو)
    // لە employee_expenses تەیبڵ، مووچە بە دینارە
    $total_employee_salary_usd = $total_employee_salary / ($usd_iqd_rate / 100);
    
    // تێچووی کارمەند بۆ 1 م³
    $employee_cost_per_m3 = $total_cubic_meters > 0 ? ($total_employee_salary_usd / $total_cubic_meters) : 0;
    
    // ============================================
    // 3. هەژمارکردنی تێچووی سەیارەکان بۆ 1 م³
    // ============================================
    
    // خەرجی سەیارەکان (لە other_expenses تەیبڵ)
    // تێبینی: خەرجی سەیارەکان بە شێوەیەکی گشتی لە other_expenses تەیبڵدا هەیە
    // بەڵام بۆ دەقیتر، دەتوانین تەنها خەرجی سەیارەکان بگرین
    $vehicle_expense_query = "
        SELECT 
            COALESCE(SUM(CASE 
                WHEN currency_type = 'دۆلار' THEN amount_usd
                WHEN currency_type = 'دینار' THEN amount_iqd / NULLIF(exchange_rate / 100, 0)
                WHEN currency_type = 'تێکەڵ' THEN amount_usd + (amount_iqd / NULLIF(exchange_rate / 100, 0))
                ELSE 0
            END), 0) as total_vehicle_expense
        FROM other_expenses
        WHERE car_id IS NOT NULL $date_condition_date
    ";
    $total_vehicle_expense_usd = floatval($pdo->query($vehicle_expense_query)->fetchColumn());
    
    // تێچووی سەیارەکان بۆ 1 م³
    $vehicle_cost_per_m3 = $total_cubic_meters > 0 ? ($total_vehicle_expense_usd / $total_cubic_meters) : 0;
    
    // ============================================
    // 4. هەژمارکردنی تێچووی خەرجی ڕۆژانە بۆ 1 م³
    // ============================================
    
    // خەرجی ڕۆژانە (لە other_expenses تەیبڵ)
    $daily_expense_query = "
        SELECT 
            COALESCE(SUM(CASE 
                WHEN currency_type = 'دۆلار' THEN amount_usd
                WHEN currency_type = 'دینار' THEN amount_iqd / NULLIF(exchange_rate / 100, 0)
                WHEN currency_type = 'تێکەڵ' THEN amount_usd + (amount_iqd / NULLIF(exchange_rate / 100, 0))
                ELSE 0
            END), 0) as total_daily_expense
        FROM other_expenses
        WHERE expense_type IN ('خەرجی تر', 'خواردنگە', 'ئۆفیس') $date_condition_date
    ";
    $total_daily_expense_usd = floatval($pdo->query($daily_expense_query)->fetchColumn());
    
    // تێچووی خەرجی ڕۆژانە بۆ 1 م³
    $daily_expense_cost_per_m3 = $total_cubic_meters > 0 ? ($total_daily_expense_usd / $total_cubic_meters) : 0;
    
    // ============================================
    // 5. هەژمارکردنی داهات بۆ 1 م³
    // ============================================
    
    // کۆی داهات لە فرۆشتنەکان (بە دۆلار)
    // داهات = price_per_unit * quantity (بە دۆلار)
    $revenue_query = "
        SELECT 
            COALESCE(SUM(
                CASE 
                    WHEN price_per_unit > 0 THEN price_per_unit * quantity
                    WHEN amount_paid_usd > 0 THEN amount_paid_usd
                    WHEN amount_paid_iq > 0 THEN amount_paid_iq / NULLIF(dolar_rate / 100, 0)
                    WHEN total_price > 0 AND dolar_rate > 0 THEN total_price / NULLIF(dolar_rate / 100, 0)
                    ELSE 0
                END
            ), 0) as total_revenue
        FROM sales
        WHERE 1=1 $date_condition_sales
    ";
    $total_revenue_usd = floatval($pdo->query($revenue_query)->fetchColumn());
    
    // داهات بۆ 1 م³
    $average_revenue_per_m3 = $total_cubic_meters > 0 ? ($total_revenue_usd / $total_cubic_meters) : 0;
    
    // ============================================
    // 6. هەژمارکردنی قازانجی خامی
    // ============================================
    
    // کۆی تێچوو بۆ 1 م³
    $total_cost_per_m3 = $material_cost_per_m3 + $employee_cost_per_m3 + $vehicle_cost_per_m3 + $daily_expense_cost_per_m3;
    
    // قازانجی خامی بۆ 1 م³
    $gross_profit_per_m3 = $average_revenue_per_m3 - $total_cost_per_m3;
    
    // ڕێژەی قازانج (%)
    $profit_margin_percent = $average_revenue_per_m3 > 0 ? (($gross_profit_per_m3 / $average_revenue_per_m3) * 100) : 0;
    
    // ============================================
    // 7. دروستکردنی ڕوونکردنەوە
    // ============================================
    
    $explanation = "
        **هەژمارکردنی قازانجی خامی بۆ 1 م³ کۆنکرێت:**
        
        **1. تێچووی مەواد:** \${$material_cost_per_m3} بۆ هەر م³
        - لمی کەسارە: " . ($material_consumption['black_sand'] / $total_cubic_meters) . " کگ × \${$material_prices['black_sand']} = \$" . (($material_consumption['black_sand'] / $total_cubic_meters) * $material_prices['black_sand']) . "
        - لمی ڕەش: " . ($material_consumption['brown_sand'] / $total_cubic_meters) . " کگ × \${$material_prices['brown_sand']} = \$" . (($material_consumption['brown_sand'] / $total_cubic_meters) * $material_prices['brown_sand']) . "
        - چەو: " . (($material_consumption['gravel_bin3'] + $material_consumption['gravel_bin4']) / $total_cubic_meters) . " کگ × \${$material_prices['gravel']} = \$" . ((($material_consumption['gravel_bin3'] + $material_consumption['gravel_bin4']) / $total_cubic_meters) * $material_prices['gravel']) . "
        - چیمەنتۆ: " . (($material_consumption['cement_cem1'] + $material_consumption['cement_cem2']) / $total_cubic_meters) . " کگ × \${$material_prices['cement']} = \$" . ((($material_consumption['cement_cem1'] + $material_consumption['cement_cem2']) / $total_cubic_meters) * $material_prices['cement']) . "
        - دەرمان: " . ($material_consumption['additive'] / $total_cubic_meters) . " کگ × \${$material_prices['additive']} = \$" . (($material_consumption['additive'] / $total_cubic_meters) * $material_prices['additive']) . "
        
        **2. تێچووی کارمەند:** \${$employee_cost_per_m3} بۆ هەر م³
        - کۆی مووچە: \${$total_employee_salary_usd} ÷ {$total_cubic_meters} م³ = \${$employee_cost_per_m3}
        
        **3. تێچووی سەیارەکان:** \${$vehicle_cost_per_m3} بۆ هەر م³
        - کۆی خەرجی سەیارەکان: \${$total_vehicle_expense_usd} ÷ {$total_cubic_meters} م³ = \${$vehicle_cost_per_m3}
        
        **4. تێچووی خەرجی ڕۆژانە:** \${$daily_expense_cost_per_m3} بۆ هەر م³
        - کۆی خەرجی ڕۆژانە: \${$total_daily_expense_usd} ÷ {$total_cubic_meters} م³ = \${$daily_expense_cost_per_m3}
        
        **5. داهات:** \${$average_revenue_per_m3} بۆ هەر م³
        - کۆی داهات: \${$total_revenue_usd} ÷ {$total_cubic_meters} م³ = \${$average_revenue_per_m3}
        
        **6. قازانجی خامی:** \${$gross_profit_per_m3} بۆ هەر م³
        - قازانج = داهات - تێچوو
        - قازانج = \${$average_revenue_per_m3} - \${$total_cost_per_m3} = \${$gross_profit_per_m3}
        - ڕێژەی قازانج: {$profit_margin_percent}%
    ";
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_cubic_meters' => $total_cubic_meters,
            'material_cost_per_m3' => round($material_cost_per_m3, 2),
            'employee_cost_per_m3' => round($employee_cost_per_m3, 2),
            'vehicle_cost_per_m3' => round($vehicle_cost_per_m3, 2),
            'daily_expense_cost_per_m3' => round($daily_expense_cost_per_m3, 2),
            'total_cost_per_m3' => round($total_cost_per_m3, 2),
            'average_revenue_per_m3' => round($average_revenue_per_m3, 2),
            'gross_profit_per_m3' => round($gross_profit_per_m3, 2),
            'profit_margin_percent' => round($profit_margin_percent, 2),
            'explanation' => $explanation,
            'details' => [
                'total_material_cost_usd' => round($total_material_cost_usd, 2),
                'total_employee_salary_usd' => round($total_employee_salary_usd, 2),
                'total_vehicle_expense_usd' => round($total_vehicle_expense_usd, 2),
                'total_daily_expense_usd' => round($total_daily_expense_usd, 2),
                'total_revenue_usd' => round($total_revenue_usd, 2),
                'material_prices' => $material_prices,
                'material_consumption_per_m3' => [
                    'black_sand' => round($material_consumption['black_sand'] / $total_cubic_meters, 2),
                    'brown_sand' => round($material_consumption['brown_sand'] / $total_cubic_meters, 2),
                    'gravel' => round(($material_consumption['gravel_bin3'] + $material_consumption['gravel_bin4']) / $total_cubic_meters, 2),
                    'cement' => round(($material_consumption['cement_cem1'] + $material_consumption['cement_cem2']) / $total_cubic_meters, 2),
                    'additive' => round($material_consumption['additive'] / $total_cubic_meters, 2)
                ]
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە هەژمارکردنی قازانج: ' . $e->getMessage()
    ]);
}

