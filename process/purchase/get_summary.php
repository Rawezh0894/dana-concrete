<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

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

try {
    // Get filter parameters
    $company_id = $_GET['company_id'] ?? null;
    $location_id = $_GET['location_id'] ?? null;
    $driver_id = $_GET['driver_id'] ?? null;
    $material_id = $_GET['material_id'] ?? null;
    $from_date = $_GET['from'] ?? null;
    $to_date = $_GET['to'] ?? null;
    
    // Build filter conditions
    $filter_conditions = [];
    $filter_params = [];
    
    if ($company_id) {
        $filter_conditions[] = "p.company_id = ?";
        $filter_params[] = $company_id;
    }
    if ($location_id) {
        $filter_conditions[] = "EXISTS (SELECT 1 FROM locations l WHERE l.id = ? AND l.name = p.location)";
        $filter_params[] = $location_id;
    }
    if ($driver_id) {
        $filter_conditions[] = "EXISTS (SELECT 1 FROM drivers d WHERE d.id = ? AND d.name = p.driver)";
        $filter_params[] = $driver_id;
    }
    if ($material_id) {
        $filter_conditions[] = "p.material_id = ?";
        $filter_params[] = $material_id;
    }
    if ($from_date) {
        $filter_conditions[] = "p.date >= ?";
        $filter_params[] = $from_date;
    }
    if ($to_date) {
        $filter_conditions[] = "p.date <= ?";
        $filter_params[] = $to_date;
    }
    
    $filter_sql = "";
    if (!empty($filter_conditions)) {
        $filter_sql = " WHERE " . implode(" AND ", $filter_conditions);
    }
    
    // Get exchange rate from API
    $usd_iqd_rate = null;
    $api_rate = fetchDollarRateFromAPI();
    if ($api_rate !== null) {
        $usd_iqd_rate = $api_rate;
    }
    
    // 1. Total debt and Total Prices - with filters
    $total_debt_usd = 0;
    $total_debt_iqd = 0;
    $total_price_usd = 0;
    $total_price_iqd = 0;
    
    // Get debt and prices from purchases
    $purchase_stats_sql = "
        SELECT 
            SUM(p.remaining_usd) as remaining_usd, 
            SUM(p.remaining_iqd) as remaining_iqd,
            SUM(p.remaining_iqd / NULLIF(p.exchange_rate / 100, 0)) as remaining_iqd_converted,
            SUM(p.price) as total_price_usd,
            SUM(p.amount_iqd) as total_price_iqd
        FROM purchases p
        $filter_sql
    ";
    
    $stmt = $pdo->prepare($purchase_stats_sql);
    $stmt->execute($filter_params);
    $row = $stmt->fetch();
    
    $total_debt_usd += floatval($row['remaining_usd'] ?? 0);
    $total_debt_usd += floatval($row['remaining_iqd_converted'] ?? 0);
    $total_price_usd = floatval($row['total_price_usd'] ?? 0);
    $total_price_iqd = floatval($row['total_price_iqd'] ?? 0);
    
    // Get debt from company opening debts - only if company filter is applied
    if ($company_id) {
        $stmt = $pdo->prepare("SELECT SUM(opening_debt_usd) as usd, SUM(opening_debt_iqd) as iqd FROM company WHERE id = ?");
        $stmt->execute([$company_id]);
        $row = $stmt->fetch();
        $total_debt_usd += floatval($row['usd'] ?? 0);
        $total_debt_iqd += floatval($row['iqd'] ?? 0);
    } else {
        // If no company filter, get all company opening debts
        $stmt = $pdo->query("SELECT SUM(opening_debt_usd) as usd, SUM(opening_debt_iqd) as iqd FROM company");
        $row = $stmt->fetch();
        $total_debt_usd += floatval($row['usd'] ?? 0);
        $total_debt_iqd += floatval($row['iqd'] ?? 0);
    }
    
    // Convert IQD to USD using API rate
    $total_debt_iqd_converted = 0;
    if ($usd_iqd_rate !== null && $usd_iqd_rate > 0) {
        $total_debt_iqd_converted = $total_debt_iqd / ($usd_iqd_rate / 100);
    }
    
    $total_debt_final = $total_debt_usd + $total_debt_iqd_converted;
    
    // 2. Total companies count (کۆی ژمارەی کۆمپانیاکان) - with filters
    if ($company_id) {
        // If specific company is selected, count is 1
        $total_companies = 1;
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM company");
        $row = $stmt->fetch();
        $total_companies = intval($row['total'] ?? 0);
    }
    
    // 3. Indebted companies count (کۆمپانیاکانی قەرزدار) - with filters
    if ($company_id) {
        // If specific company is selected, check if it's indebted
        $stmt = $pdo->prepare("
            SELECT 
                CASE WHEN (c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0) 
                     OR EXISTS(SELECT 1 FROM purchases p WHERE p.company_id = c.id AND (p.remaining_usd > 0 OR p.remaining_iqd > 0))
                THEN 1 ELSE 0 END as indebted_count
            FROM company c 
            WHERE c.id = ?
        ");
        $stmt->execute([$company_id]);
        $row = $stmt->fetch();
        $indebted_companies = intval($row['indebted_count'] ?? 0);
    } else {
        // If no company filter, get all indebted companies
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT c.id) as indebted_count 
            FROM company c 
            LEFT JOIN purchases p ON c.id = p.company_id 
            WHERE (c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0) 
               OR (p.remaining_usd > 0 OR p.remaining_iqd > 0)
        ");
        $row = $stmt->fetch();
        $indebted_companies = intval($row['indebted_count'] ?? 0);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_debt' => round($total_debt_final, 2),
            'total_price_usd' => round($total_price_usd, 2),
            'total_price_iqd' => round($total_price_iqd, 0),
            'total_companies' => $total_companies,
            'indebted_companies' => $indebted_companies,
            'usd_iqd_rate' => $usd_iqd_rate
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 