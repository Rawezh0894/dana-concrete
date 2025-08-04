<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
// require_once '../../config/permissions.php';

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

// Temporarily bypass permissions check to debug the issue
// if (!hasPermission('view_accounts')) {
//     error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to view company summary stats');
//     http_response_code(403);
//     echo json_encode(['error' => 'ڕێگەت پێنەدراوە!']);
//     exit;
// }

try {
    // Test database connection first
    $pdo->query("SELECT 1");
    error_log('Database connection successful');
    
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
    error_log('Total companies: ' . $totalCompanies);

    // Get companies with debt count (opening debt + remaining from purchases)
    $companiesWithDebtQuery = "
        SELECT COUNT(DISTINCT c.id) as count 
        FROM company c
        LEFT JOIN purchases p ON c.id = p.company_id AND p.payment_type = 'قەرز'
        WHERE (c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0 OR 
               COALESCE(p.remaining_usd, 0) > 0 OR COALESCE(p.remaining_iqd, 0) > 0)
    ";
    $companiesWithDebtStmt = $pdo->query($companiesWithDebtQuery);
    $companiesWithDebt = $companiesWithDebtStmt->fetchColumn();
    error_log('Companies with debt: ' . $companiesWithDebt);

    // Get total debt (opening_debt + remaining from purchases converted to USD)
    $totalDebtQuery = "
        SELECT 
            SUM(c.opening_debt_usd) as total_opening_debt_usd,
            SUM(c.opening_debt_iqd) as total_opening_debt_iqd,
            COALESCE(SUM(p.remaining_usd), 0) as total_remaining_usd,
            COALESCE(SUM(p.remaining_iqd), 0) as total_remaining_iqd
        FROM company c
        LEFT JOIN purchases p ON c.id = p.company_id AND p.payment_type = 'قەرز'
    ";
    $totalDebtStmt = $pdo->query($totalDebtQuery);
    $totalDebtData = $totalDebtStmt->fetch(PDO::FETCH_ASSOC);
    error_log('Total debt data: ' . print_r($totalDebtData, true));

    // Calculate total debt in USD
    $totalDebtUSD = floatval($totalDebtData['total_opening_debt_usd'] ?? 0) + 
                   floatval($totalDebtData['total_remaining_usd'] ?? 0) +
                   (floatval($totalDebtData['total_opening_debt_iqd'] ?? 0) / ($usdRate / 100)) +
                   (floatval($totalDebtData['total_remaining_iqd'] ?? 0) / ($usdRate / 100));

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