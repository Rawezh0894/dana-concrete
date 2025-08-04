<?php
// Simplified version for testing - no session or permissions required
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../config/db_conected.php';
    
    // Test database connection
    $pdo->query('SELECT 1');
    
    // Get USD exchange rate from settings table
    $usdRate = 139250; // Default rate
    
    try {
        $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'usd_iqd_rate'");
        $row = $stmt->fetch();
        if ($row && is_numeric($row['value'])) {
            $usdRate = floatval($row['value']);
        }
    } catch (Exception $e) {
        // Use default rate if settings table doesn't exist
    }

    // Get total customers count
    $totalCustomers = 0;
    try {
        $totalCustomers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    } catch (Exception $e) {
        error_log('Total customers query failed: ' . $e->getMessage());
    }

    // Get customers with debt count
    $customersWithDebt = 0;
    try {
        $customersWithDebtQuery = "
            SELECT COUNT(DISTINCT c.id) as count 
            FROM customers c
            LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_type = 'قەرز'
            WHERE (c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0 OR 
                   COALESCE(s.remaining_amount, 0) > 0)
        ";
        $customersWithDebt = $pdo->query($customersWithDebtQuery)->fetchColumn();
    } catch (Exception $e) {
        error_log('Customers with debt query failed: ' . $e->getMessage());
    }

    // Get total debt
    $totalDebtUSD = 0;
    try {
        $totalDebtQuery = "
            SELECT 
                SUM(c.opening_debt_usd) as total_opening_debt_usd,
                SUM(c.opening_debt_iqd) as total_opening_debt_iqd,
                COALESCE(SUM(s.remaining_amount), 0) as total_remaining_amount
            FROM customers c
            LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_type = 'قەرز'
        ";
        $totalDebtData = $pdo->query($totalDebtQuery)->fetch(PDO::FETCH_ASSOC);

        $totalDebtUSD = floatval($totalDebtData['total_opening_debt_usd'] ?? 0) + 
                       floatval($totalDebtData['total_remaining_amount'] ?? 0) +
                       (floatval($totalDebtData['total_opening_debt_iqd'] ?? 0) / ($usdRate / 100));
    } catch (Exception $e) {
        error_log('Total debt query failed: ' . $e->getMessage());
    }

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

} catch (PDOException $e) {
    error_log('PDO Exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('General Exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 