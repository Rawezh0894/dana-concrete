<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for company summary stats');
    http_response_code(401);
    echo json_encode(['error' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

// Check if user has permission to view accounts
if (!hasPermission('view_accounts')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to view company summary stats');
    http_response_code(403);
    echo json_encode(['error' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
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
            error_log('USD rate retrieved from API: ' . $usdRate);
        } else {
            error_log('Failed to parse USD rate from API response');
        }
    } else {
        error_log('Failed to get USD rate from API: HTTP Code=' . $httpCode . ', Error=' . $error);
    }

    // Get total companies count
    $totalCompaniesQuery = "SELECT COUNT(*) as total FROM company";
    $totalCompaniesStmt = $pdo->query($totalCompaniesQuery);
    $totalCompanies = $totalCompaniesStmt->fetchColumn();

    // Get companies with debt count (opening debt + remaining from purchases)
    $companiesWithDebtQuery = "
        SELECT COUNT(DISTINCT c.id) as count 
        FROM company c
        WHERE c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0
        UNION
        SELECT COUNT(DISTINCT p.company_id) as count
        FROM purchases p
        WHERE p.payment_type = 'قەرز' AND (p.remaining_usd > 0 OR p.remaining_iqd > 0)
    ";
    $companiesWithDebtStmt = $pdo->query($companiesWithDebtQuery);
    $companiesWithDebt = $companiesWithDebtStmt->fetchColumn();

    // Get total debt (opening_debt + remaining from purchases converted to USD)
    // Fixed: Use separate queries to avoid duplicate counting
    $openingDebtQuery = "
        SELECT 
            SUM(opening_debt_usd) as total_opening_debt_usd,
            SUM(opening_debt_iqd) as total_opening_debt_iqd
        FROM company
    ";
    $openingDebtStmt = $pdo->query($openingDebtQuery);
    $openingDebtData = $openingDebtStmt->fetch(PDO::FETCH_ASSOC);
    
    $remainingQuery = "
        SELECT 
            COALESCE(SUM(remaining_usd), 0) as total_remaining_usd,
            COALESCE(SUM(remaining_iqd), 0) as total_remaining_iqd
        FROM purchases 
        WHERE payment_type = 'قەرز'
    ";
    $remainingStmt = $pdo->query($remainingQuery);
    $remainingData = $remainingStmt->fetch(PDO::FETCH_ASSOC);

    // Calculate total debt in USD
    $totalDebtUSD = floatval($openingDebtData['total_opening_debt_usd'] ?? 0) + 
                   floatval($remainingData['total_remaining_usd'] ?? 0) +
                   (floatval($openingDebtData['total_opening_debt_iqd'] ?? 0) / ($usdRate / 100)) +
                   (floatval($remainingData['total_remaining_iqd'] ?? 0) / ($usdRate / 100));

    error_log('Company summary stats calculated: Total Companies=' . $totalCompanies . ', Companies with Debt=' . $companiesWithDebt . ', Total Debt USD=' . $totalDebtUSD);

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

} catch (PDOException $e) {
    error_log('PDOException in company/get_summary_stats.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in company/get_summary_stats.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
?> 