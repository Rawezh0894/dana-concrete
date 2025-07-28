<?php
// Simple database connection test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

try {
    require_once 'config/db_conected.php';
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test if customers table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'customers'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Customers table exists</p>";
        
        // Test customers table structure
        $stmt = $pdo->query("DESCRIBE customers");
        echo "<h3>Customers table structure:</h3>";
        echo "<ul>";
        while ($row = $stmt->fetch()) {
            echo "<li>{$row['Field']} - {$row['Type']}</li>";
        }
        echo "</ul>";
        
        // Test count query
        $count = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        echo "<p>Total customers: $count</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Customers table does not exist</p>";
    }
    
    // Test if sales table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'sales'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Sales table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Sales table does not exist</p>";
    }
    
    // Test if settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Settings table exists</p>";
        
        // Test settings query
        $stmt = $pdo->query("SELECT * FROM settings WHERE name = 'usd_iqd_rate'");
        $row = $stmt->fetch();
        if ($row) {
            echo "<p>USD rate from settings: {$row['value']}</p>";
        } else {
            echo "<p>No USD rate found in settings</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Settings table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Test Summary Stats Query</h2>";

try {
    // Test the exact query from get_summary_stats.php
    $query = "
        SELECT 
            SUM(c.opening_debt_usd) as total_opening_debt_usd,
            SUM(c.opening_debt_iqd) as total_opening_debt_iqd,
            COALESCE(SUM(s.remaining_amount), 0) as total_remaining_amount
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_type = 'قەرز'
    ";
    
    $stmt = $pdo->query($query);
    $result = $stmt->fetch();
    
    echo "<p>Query executed successfully</p>";
    echo "<p>Opening debt USD: " . ($result['total_opening_debt_usd'] ?? 'NULL') . "</p>";
    echo "<p>Opening debt IQD: " . ($result['total_opening_debt_iqd'] ?? 'NULL') . "</p>";
    echo "<p>Remaining amount: " . ($result['total_remaining_amount'] ?? 'NULL') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Query error: " . $e->getMessage() . "</p>";
}
?> 