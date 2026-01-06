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

    // Parse filters
    $year = $_GET['year'] ?? '';
    $month = $_GET['month'] ?? '';
    $fromDate = $_GET['from_date'] ?? '';
    $toDate = $_GET['to_date'] ?? '';

    $salesWhere = "WHERE payment_type = 'قەرز'";
    $salesParams = [];

    if ($fromDate) {
        $salesWhere .= " AND order_date >= :from_date";
        $salesParams['from_date'] = $fromDate;
    }
    if ($toDate) {
        $salesWhere .= " AND order_date <= :to_date";
        $salesParams['to_date'] = $toDate;
    }
    if ($year) {
        $salesWhere .= " AND YEAR(order_date) = :year";
        $salesParams['year'] = $year;
    }
    if ($month) {
        $salesWhere .= " AND MONTH(order_date) = :month";
        $salesParams['month'] = $month;
    }

    $isFiltered = ($fromDate || $toDate || $year || $month);

    // Get total customers count
    $totalCustomers = 0;
    try {
        $totalCustomersQuery = "SELECT COUNT(*) as total FROM customers";
        // If filtered, maybe we should filter by created_at if it exists, but for now we'll keep it as is
        // or filter based on whether they have transactions in that period
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
            WHERE " . ($isFiltered ? "" : "(c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0) OR ") . "
                  (COALESCE(s.remaining_amount, 0) > 0" . str_replace("WHERE", "AND", $salesWhere) . ")
        ";
        $customersWithDebtStmt = $pdo->prepare($customersWithDebtQuery);
        $customersWithDebtStmt->execute($salesParams);
        $customersWithDebt = $customersWithDebtStmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Customers with debt query failed: ' . $e->getMessage());
    }

    // Get total debt
    $totalDebtUSD = 0;
    try {
        // 1. قەرزی سەرەتایی - Only include if not filtered
        $openingDebtUSD = 0;
        $openingDebtIQD_USD = 0;
        
        if (!$isFiltered) {
            $openingDebtUSD = $pdo->query("SELECT COALESCE(SUM(opening_debt_usd), 0) FROM customers")->fetchColumn();
            $openingDebtIQD = $pdo->query("SELECT COALESCE(SUM(opening_debt_iqd), 0) FROM customers")->fetchColumn();
            $openingDebtIQD_USD = $usdRate > 0 ? ($openingDebtIQD / ($usdRate / 100)) : 0;
        }
        
        // 2. کۆی ماوەی قەرز لە فرۆشتنەکان (USD - ئەوانەی amount_paid_iq = 0)
        $salesRemainingUSDQuery = "
            SELECT COALESCE(SUM(remaining_amount), 0) 
            FROM sales 
            $salesWhere 
            AND amount_paid_iq = 0
        ";
        $stmt = $pdo->prepare($salesRemainingUSDQuery);
        $stmt->execute($salesParams);
        $salesRemainingUSD = $stmt->fetchColumn();
        
        // 3. کۆی ماوەی قەرز لە فرۆشتنەکان (IQD - ئەوانەی amount_paid_iq > 0)
        $salesRemainingIQDQuery = "
            SELECT COALESCE(SUM(remaining_amount), 0) 
            FROM sales 
            $salesWhere 
            AND amount_paid_iq > 0
        ";
        $stmt = $pdo->prepare($salesRemainingIQDQuery);
        $stmt->execute($salesParams);
        $salesRemainingIQD = $stmt->fetchColumn();
        
        // 5. کۆکردنەوەی هەموو قەرزەکان بە دۆلار
        $totalDebtUSD = floatval($openingDebtUSD) +           // قەرزی سەرەتایی (USD)
                       floatval($openingDebtIQD_USD) +        // قەرزی سەرەتایی (IQD → USD)
                       floatval($salesRemainingUSD) +         // پارەی ماوەی فرۆشتنەکان (USD)
                       (floatval($salesRemainingIQD) / ($usdRate / 100)); // پارەی ماوەی فرۆشتنەکان (IQD → USD)
        
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