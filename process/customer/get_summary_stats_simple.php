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

    // Get customers with debt count - ڕاستکردنەوەی هەژمارکردنی کڕیارانی قەرز
    $customersWithDebt = 0;
    try {
        $customersWithDebtQuery = "
            SELECT COUNT(DISTINCT c.id) as count 
            FROM customers c
            LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_type = 'قەرز' AND s.remaining_amount > 0
            WHERE (c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0 OR s.id IS NOT NULL)
        ";
        $customersWithDebt = $pdo->query($customersWithDebtQuery)->fetchColumn();
    } catch (Exception $e) {
        error_log('Customers with debt query failed: ' . $e->getMessage());
    }

    // Get total debt - ڕاستکردنەوەی هەژمارکردنی قەرز
    $totalDebtUSD = 0;
    try {
        // 1. قەرزی سەرەتایی (USD)
        $openingDebtUSD = $pdo->query("SELECT COALESCE(SUM(opening_debt_usd), 0) FROM customers")->fetchColumn();
        
        // 2. کۆی ماوەی قەرز لە فرۆشتنەکان (تەنها ئەوانەی amount_paid_iq = 0)
        $salesRemainingUSD = $pdo->query("
            SELECT COALESCE(SUM(remaining_amount), 0) 
            FROM sales 
            WHERE payment_type = 'قەرز' 
            AND amount_paid_iq = 0
        ")->fetchColumn();
        
        // 3. کۆی ماوەی قەرز لە فرۆشتنەکان (دینار - ئەوانەی amount_paid_iq > 0)
        $salesRemainingIQD = $pdo->query("
            SELECT COALESCE(SUM(remaining_amount), 0) 
            FROM sales 
            WHERE payment_type = 'قەرز' 
            AND amount_paid_iq > 0
        ")->fetchColumn();
        
        // 4. قەرزی سەرەتایی (IQD) - گۆڕینی بۆ دۆلار
        $openingDebtIQD = $pdo->query("SELECT COALESCE(SUM(opening_debt_iqd), 0) FROM customers")->fetchColumn();
        $openingDebtIQD_USD = $usdRate > 0 ? ($openingDebtIQD / ($usdRate / 100)) : 0;
        
        // 5. کۆکردنەوەی هەموو قەرزەکان بە دۆلار
        $totalDebtUSD = floatval($openingDebtUSD) + 
                       floatval($salesRemainingUSD) + 
                       floatval($openingDebtIQD_USD) +
                       (floatval($salesRemainingIQD) / ($usdRate / 100));
        
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