<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

// Simple error handling
try {
    error_log('Starting company summary stats...');
    
    require_once '../../config/db_conected.php';
    error_log('Database connection loaded successfully');
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        error_log('User not logged in');
        http_response_code(401);
        echo json_encode(['error' => 'سێشن نییە!']);
        exit;
    }
    error_log('User logged in: ' . $_SESSION['user_id']);
    
    // Test database connection
    $pdo->query("SELECT 1");
    error_log('Database connection test successful');
    
    // Check if tables exist
    $tables = ['company', 'purchases'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            throw new Exception("Table '$table' does not exist");
        }
        error_log("Table '$table' exists");
    }
    
    // Get USD exchange rate from API
    error_log('Fetching USD exchange rate...');
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
            error_log('USD rate from API: ' . $usdRate);
        } else {
            error_log('Failed to parse USD rate from API response');
        }
    } else {
        error_log('Failed to get USD rate from API: HTTP Code=' . $httpCode . ', Error=' . $error);
    }
    
    // Simple queries without complex joins
    error_log('Getting total companies count...');
    $totalCompanies = $pdo->query("SELECT COUNT(*) FROM company")->fetchColumn();
    error_log('Total companies: ' . $totalCompanies);
    
    // Simple debt calculation
    error_log('Getting opening debt...');
    $openingDebt = $pdo->query("SELECT SUM(opening_debt_usd) as usd, SUM(opening_debt_iqd) as iqd FROM company")->fetch();
    error_log('Opening debt: ' . print_r($openingDebt, true));
    
    error_log('Getting remaining debt...');
    $remainingDebt = $pdo->query("SELECT SUM(remaining_usd) as usd, SUM(remaining_iqd) as iqd FROM purchases WHERE payment_type = 'قەرز'")->fetch();
    error_log('Remaining debt: ' . print_r($remainingDebt, true));
    
    $totalDebtUSD = floatval($openingDebt['usd'] ?? 0) + floatval($remainingDebt['usd'] ?? 0);
    $totalDebtIQD = floatval($openingDebt['iqd'] ?? 0) + floatval($remainingDebt['iqd'] ?? 0);
    
    // Convert IQD to USD
    $totalDebtUSD += ($totalDebtIQD / ($usdRate / 100));
    error_log('Total debt USD: ' . $totalDebtUSD);
    
    // Count companies with debt (including both opening debt and remaining from purchases)
    error_log('Getting companies with debt count...');
    $companiesWithDebt = $pdo->query("
        SELECT COUNT(DISTINCT c.id) as count 
        FROM company c
        LEFT JOIN purchases p ON c.id = p.company_id AND p.payment_type = 'قەرز'
        WHERE (c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0 OR 
               COALESCE(p.remaining_usd, 0) > 0 OR COALESCE(p.remaining_iqd, 0) > 0)
    ")->fetchColumn();
    error_log('Companies with debt: ' . $companiesWithDebt);
    
    $response = [
        'success' => true,
        'summary' => [
            'total_debt_usd' => round($totalDebtUSD, 2),
            'total_companies' => (int)$totalCompanies,
            'companies_with_debt' => (int)$companiesWithDebt,
            'usd_rate' => $usdRate
        ]
    ];
    
    error_log('Response prepared: ' . json_encode($response));
    echo json_encode($response);
    error_log('Response sent successfully');
    
} catch (PDOException $e) {
    error_log('PDOException in company/get_summary_stats.php: ' . $e->getMessage());
    error_log('PDOException trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in company/get_summary_stats.php: ' . $e->getMessage());
    error_log('Exception trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
?> 