<?php
// Simple test script to check company summary stats
session_start();
require_once 'config/db_conected.php';

echo "<h2>Testing Company Summary Stats</h2>";

try {
    // Test database connection
    $pdo->query("SELECT 1");
    echo "<p>✅ Database connection successful</p>";
    
    // Check if tables exist
    $tables = ['company', 'purchases'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p>❌ Table '$table' does not exist</p>";
        }
    }
    
    // Test basic queries
    $totalCompanies = $pdo->query("SELECT COUNT(*) FROM company")->fetchColumn();
    echo "<p>✅ Total companies: $totalCompanies</p>";
    
    $totalPurchases = $pdo->query("SELECT COUNT(*) FROM purchases")->fetchColumn();
    echo "<p>✅ Total purchases: $totalPurchases</p>";
    
    // Test the actual query
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
    echo "<p>✅ Debt query successful: " . print_r($totalDebtData, true) . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?> 