<?php
session_start();
require_once '../../config/db_conected.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    // Test database connection first
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Simple queries without API calls
    $usdRate = 150000; // Default rate

    // Get total customers count
    $totalCustomersQuery = "SELECT COUNT(*) as total FROM customers";
    $totalCustomersStmt = $pdo->query($totalCustomersQuery);
    if (!$totalCustomersStmt) {
        throw new Exception('Failed to execute total customers query');
    }
    $totalCustomers = $totalCustomersStmt->fetchColumn();

    // Get customers with debt count (simplified)
    $customersWithDebtQuery = "
        SELECT COUNT(DISTINCT c.id) as count 
        FROM customers c
        WHERE c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0
    ";
    $customersWithDebtStmt = $pdo->query($customersWithDebtQuery);
    if (!$customersWithDebtStmt) {
        throw new Exception('Failed to execute customers with debt query');
    }
    $customersWithDebt = $customersWithDebtStmt->fetchColumn();

    // Get total debt (simplified)
    $totalDebtQuery = "
        SELECT 
            SUM(c.opening_debt_usd) as total_opening_debt_usd,
            SUM(c.opening_debt_iqd) as total_opening_debt_iqd
        FROM customers c
    ";
    $totalDebtStmt = $pdo->query($totalDebtQuery);
    if (!$totalDebtStmt) {
        throw new Exception('Failed to execute total debt query');
    }
    $totalDebtData = $totalDebtStmt->fetch(PDO::FETCH_ASSOC);

    // Calculate total debt in USD
    $totalDebtUSD = floatval($totalDebtData['total_opening_debt_usd'] ?? 0) + 
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

    echo json_encode($response);

} catch (Exception $e) {
    error_log('Error in get_summary_stats_simple.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'details' => $e->getTraceAsString()
    ]);
}
?> 