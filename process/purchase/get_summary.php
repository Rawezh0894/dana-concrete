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
        $filter_conditions[] = "l.id = ?";
        $filter_params[] = $location_id;
    }
    if ($driver_id) {
        $filter_conditions[] = "d.id = ?";
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
            SUM(CASE WHEN p.type = 'دۆلار' THEN p.price ELSE 0 END) as total_price_usd,
            SUM(CASE WHEN p.type = 'دینار' THEN p.amount_iqd ELSE 0 END) as total_price_iqd,
            COUNT(p.id) as total_invoices
        FROM purchases p
        LEFT JOIN locations l ON p.location = l.name
        LEFT JOIN drivers d ON p.driver = d.name
        $filter_sql
    ";
    
    $stmt = $pdo->prepare($purchase_stats_sql);
    $stmt->execute($filter_params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_debt_usd += floatval($row['remaining_usd'] ?? 0);
    $total_debt_usd += floatval($row['remaining_iqd_converted'] ?? 0);
    $total_price_usd = floatval($row['total_price_usd'] ?? 0);
    $total_price_iqd = floatval($row['total_price_iqd'] ?? 0);
    $total_invoices = intval($row['total_invoices'] ?? 0);
    
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
    
    // 2. Material KG summary - with filters
    $material_kg_sql = "
        SELECT 
            m.name as material_name, 
            SUM(p.kg) as total_kg,
            SUM(CASE WHEN p.type = 'دۆلار' THEN p.price ELSE p.amount_iqd / NULLIF(p.exchange_rate / 100, 0) END) as total_cost_usd,
            SUM(CASE WHEN p.type = 'دینار' THEN p.amount_iqd ELSE p.price * (p.exchange_rate / 100) END) as total_cost_iqd
        FROM purchases p
        JOIN materials m ON p.material_id = m.id
        LEFT JOIN locations l ON p.location = l.name
        LEFT JOIN drivers d ON p.driver = d.name
        $filter_sql
        GROUP BY m.id, m.name
    ";
    
    $stmt_mat = $pdo->prepare($material_kg_sql);
    $stmt_mat->execute($filter_params);
    $materials_raw = $stmt_mat->fetchAll(PDO::FETCH_ASSOC);
    
    $materials_kg = [];
    foreach ($materials_raw as $row) {
        $kg = floatval($row['total_kg'] ?? 0);
        $avg_price_usd_per_ton = $kg > 0 ? ($row['total_cost_usd'] / $kg * 1000) : 0;
        $avg_price_iqd_per_ton = $kg > 0 ? ($row['total_cost_iqd'] / $kg * 1000) : 0;
        
        $row['avg_price_usd_per_ton'] = round($avg_price_usd_per_ton, 2);
        $row['avg_price_iqd_per_ton'] = round($avg_price_iqd_per_ton, 0);
        
        $materials_kg[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_debt' => round($total_debt_final, 2),
            'total_price_usd' => round($total_price_usd, 2),
            'total_price_iqd' => round($total_price_iqd, 0),
            'total_invoices' => $total_invoices,
            'materials_kg' => $materials_kg,
            'usd_iqd_rate' => $usd_iqd_rate
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 