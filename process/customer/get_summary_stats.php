<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if user has permission to view customers
if (!hasPermission('view_customer')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

try {
    // Test database connection first
    if (!$pdo) {
        throw new Exception('Database connection failed');
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

    // Get total customers count
    $totalCustomersQuery = "SELECT COUNT(*) as total FROM customers";
    $totalCustomersStmt = $pdo->query($totalCustomersQuery);
    if (!$totalCustomersStmt) {
        throw new Exception('Failed to execute total customers query: ' . implode(', ', $pdo->errorInfo()));
    }
    $totalCustomers = $totalCustomersStmt->fetchColumn();

    // Get customers with debt count (opening debt + remaining from sales)
    $customersWithDebtQuery = "
        SELECT COUNT(DISTINCT c.id) as count 
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_type = 'قەرز'
        WHERE (c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0 OR 
               COALESCE(s.remaining_amount, 0) > 0)
    ";
    $customersWithDebtStmt = $pdo->query($customersWithDebtQuery);
    if (!$customersWithDebtStmt) {
        throw new Exception('Failed to execute customers with debt query: ' . implode(', ', $pdo->errorInfo()));
    }
    $customersWithDebt = $customersWithDebtStmt->fetchColumn();

    // Get total debt (opening_debt + remaining from sales converted to USD)
    $totalDebtQuery = "
        SELECT 
            SUM(c.opening_debt_usd) as total_opening_debt_usd,
            SUM(c.opening_debt_iqd) as total_opening_debt_iqd,
            COALESCE(SUM(s.remaining_amount), 0) as total_remaining_amount
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_type = 'قەرز'
    ";
    $totalDebtStmt = $pdo->query($totalDebtQuery);
    if (!$totalDebtStmt) {
        throw new Exception('Failed to execute total debt query: ' . implode(', ', $pdo->errorInfo()));
    }
    $totalDebtData = $totalDebtStmt->fetch(PDO::FETCH_ASSOC);

    // Calculate total debt in USD
    $totalDebtUSD = floatval($totalDebtData['total_opening_debt_usd'] ?? 0) + 
                   floatval($totalDebtData['total_remaining_amount'] ?? 0) +
                   (floatval($totalDebtData['total_opening_debt_iqd'] ?? 0) / ($usdRate / 100));

    $response = [
        'success' => true,
        'summary' => [
            'total_debt_usd' => round($totalDebtUSD, 2),
            'total_customers' => (int)$totalCustomers,
            'customers_with_debt' => (int)$customersWithDebt,
            'usd_rate' => $usdRate
        ]
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);

} catch (Exception $e) {
    error_log('Error in get_summary_stats.php: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'details' => $e->getTraceAsString()
    ]);
}
?> 