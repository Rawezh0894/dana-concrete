<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Simple error handling
try {
    require_once '../../config/db_conected.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'سێشن نییە!']);
        exit;
    }
    
    // Get USD exchange rate from API
    $apiUrl = 'https://dinarapi.hediworks.site/api/get-price';
    $apiToken = 'S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
    $dollarId = 8; // ID for 100 USD
    
    $url = $apiUrl . '?id=' . $dollarId . '&api_token=' . $apiToken;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $usdRate = 139250; // Default rate
    if (!$error && $httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['value'])) {
            $usdRate = $data['value'];
        }
    }
    
    // Simple queries without complex joins
    $totalCompanies = $pdo->query("SELECT COUNT(*) FROM company")->fetchColumn();
    
    // Simple debt calculation
    $openingDebt = $pdo->query("SELECT SUM(opening_debt_usd) as usd, SUM(opening_debt_iqd) as iqd FROM company")->fetch();
    $remainingDebt = $pdo->query("SELECT SUM(remaining_usd) as usd, SUM(remaining_iqd) as iqd FROM purchases WHERE payment_type = 'قەرز'")->fetch();
    
    $totalDebtUSD = floatval($openingDebt['usd'] ?? 0) + floatval($remainingDebt['usd'] ?? 0);
    $totalDebtIQD = floatval($openingDebt['iqd'] ?? 0) + floatval($remainingDebt['iqd'] ?? 0);
    
    // Convert IQD to USD
    $totalDebtUSD += ($totalDebtIQD / ($usdRate / 100));
    
    // Count companies with debt (including both opening debt and remaining from purchases)
    $companiesWithDebt = $pdo->query("
        SELECT COUNT(DISTINCT c.id) as count 
        FROM company c
        LEFT JOIN purchases p ON c.id = p.company_id AND p.payment_type = 'قەرز'
        WHERE (c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0 OR 
               COALESCE(p.remaining_usd, 0) > 0 OR COALESCE(p.remaining_iqd, 0) > 0)
    ")->fetchColumn();
    
    $response = [
        'success' => true,
        'summary' => [
            'total_debt_usd' => round($totalDebtUSD, 2),
            'total_companies' => (int)$totalCompanies,
            'companies_with_debt' => (int)$companiesWithDebt,
            'usd_rate' => $usdRate
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'هەڵە: ' . $e->getMessage()]);
}
?> 