<?php
session_start();

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../config/db_conected.php';
    require_once '../../config/permissions.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error: ' . $e->getMessage()]);
    exit;
}

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
    // Test database connection
    $pdo->query('SELECT 1');
    
    // Get USD exchange rate from settings table first, fallback to API
    $usdRate = 139250; // Default rate
    
    try {
        $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'usd_iqd_rate'");
        $row = $stmt->fetch();
        if ($row && is_numeric($row['value'])) {
            $usdRate = floatval($row['value']);
        }
    } catch (Exception $e) {
        // If settings table doesn't exist or query fails, use default
        error_log('Settings table query failed: ' . $e->getMessage());
    }

    // Get total customers count
    $totalCustomers = 0;
    try {
        $totalCustomersQuery = "SELECT COUNT(*) as total FROM customers";
        $totalCustomersStmt = $pdo->query($totalCustomersQuery);
        $totalCustomers = $totalCustomersStmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Total customers query failed: ' . $e->getMessage());
    }

    // Get customers with debt count (opening debt + remaining from sales)
    $customersWithDebt = 0;
    try {
        $customersWithDebtQuery = "
            SELECT COUNT(DISTINCT c.id) as count 
            FROM customers c
            LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_type = 'قەرز'
            WHERE (c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0 OR 
                   COALESCE(s.remaining_amount, 0) > 0)
        ";
        $customersWithDebtStmt = $pdo->query($customersWithDebtQuery);
        $customersWithDebt = $customersWithDebtStmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Customers with debt query failed: ' . $e->getMessage());
    }

    // Get total debt (opening_debt + remaining from sales converted to USD)
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
        $totalDebtStmt = $pdo->query($totalDebtQuery);
        $totalDebtData = $totalDebtStmt->fetch(PDO::FETCH_ASSOC);

        // Calculate total debt in USD
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
    error_log('PDO Exception in get_summary_stats.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('General Exception in get_summary_stats.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 