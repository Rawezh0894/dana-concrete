<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../config/db_conected.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'سێشن نییە!']);
        exit;
    }
    
    // Get total companies count
    $totalCompanies = $pdo->query("SELECT COUNT(*) FROM company")->fetchColumn();
    
    // Get opening debt from companies
    $openingDebt = $pdo->query("SELECT SUM(opening_debt_usd) as usd, SUM(opening_debt_iqd) as iqd FROM company")->fetch();
    
    // Get remaining debt from purchases with their individual exchange rates
    $remainingDebt = $pdo->query("
        SELECT 
            SUM(remaining_usd) as usd, 
            SUM(remaining_iqd) as iqd,
            SUM(remaining_iqd / NULLIF(exchange_rate, 0)) as iqd_converted
        FROM purchases 
        WHERE payment_type = 'قەرز'
    ")->fetch();
    
    // Calculate total debt
    $totalDebtUSD = floatval($openingDebt['usd'] ?? 0) + floatval($remainingDebt['usd'] ?? 0);
    $totalDebtUSD += floatval($remainingDebt['iqd_converted'] ?? 0); // Add converted IQD amount
    
    // For opening debt IQD, use the latest exchange rate from purchases
    $latestRate = $pdo->query("
        SELECT exchange_rate 
        FROM purchases 
        WHERE exchange_rate > 0 
        ORDER BY date DESC, id DESC 
        LIMIT 1
    ")->fetchColumn();
    
    $usdRate = $latestRate ?: 139250; // Fallback to default if no purchases exist
    $totalDebtUSD += (floatval($openingDebt['iqd'] ?? 0) / ($usdRate / 100));
    
    // Count companies with debt
    $companiesWithDebt = $pdo->query("
        SELECT COUNT(DISTINCT c.id) 
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