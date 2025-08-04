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
    // Get exchange rate from API
    $usd_iqd_rate = null;
    $api_rate = fetchDollarRateFromAPI();
    if ($api_rate !== null) {
        $usd_iqd_rate = $api_rate;
    }
    
    // 1. Total debt (کۆی قەرزی ئێمە)
    $total_debt_usd = 0;
    $total_debt_iqd = 0;
    
    // Get debt from purchases (remaining amounts) with individual exchange rates
    $stmt = $pdo->query("
        SELECT 
            SUM(remaining_usd) as usd, 
            SUM(remaining_iqd) as iqd,
            SUM(remaining_iqd / NULLIF(exchange_rate / 100, 0)) as iqd_converted
        FROM purchases
    ");
    $row = $stmt->fetch();
    $total_debt_usd += floatval($row['usd'] ?? 0);
    $total_debt_usd += floatval($row['iqd_converted'] ?? 0); // Add converted IQD amount
    
    // Get debt from company opening debts
    $stmt = $pdo->query("SELECT SUM(opening_debt_usd) as usd, SUM(opening_debt_iqd) as iqd FROM company");
    $row = $stmt->fetch();
    $total_debt_usd += floatval($row['usd'] ?? 0);
    $total_debt_iqd += floatval($row['iqd'] ?? 0);
    
    // Convert IQD to USD using API rate
    $total_debt_iqd_converted = 0;
    if ($usd_iqd_rate !== null && $usd_iqd_rate > 0) {
        $total_debt_iqd_converted = $total_debt_iqd / ($usd_iqd_rate / 100);
    }
    
    $total_debt_final = $total_debt_usd + $total_debt_iqd_converted;
    
    // 2. Total companies count (کۆی ژمارەی کۆمپانیاکان)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM company");
    $row = $stmt->fetch();
    $total_companies = intval($row['total'] ?? 0);
    
    // 3. Indebted companies count (کۆمپانیاکانی قەرزدار)
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT c.id) as indebted_count 
        FROM company c 
        LEFT JOIN purchases p ON c.id = p.company_id 
        WHERE (c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0) 
           OR (p.remaining_usd > 0 OR p.remaining_iqd > 0)
    ");
    $row = $stmt->fetch();
    $indebted_companies = intval($row['indebted_count'] ?? 0);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_debt' => round($total_debt_final, 2),
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