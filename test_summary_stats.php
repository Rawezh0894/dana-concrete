<?php
// Test script to debug the summary stats issue
require_once 'config/db_conected.php';

echo "<h2>Testing Database Connection and Queries</h2>";

try {
    // Test 1: Basic connection
    echo "<h3>Test 1: Database Connection</h3>";
    if ($pdo) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
        exit;
    }

    // Test 2: Check if customers table exists
    echo "<h3>Test 2: Check customers table</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'customers'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Customers table exists<br>";
    } else {
        echo "❌ Customers table does not exist<br>";
        exit;
    }

    // Test 3: Check customers table structure
    echo "<h3>Test 3: Customers table structure</h3>";
    $stmt = $pdo->query("DESCRIBE customers");
    $columns = $stmt->fetchAll();
    echo "Columns in customers table:<br>";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
    }

    // Test 4: Check if sales table exists
    echo "<h3>Test 4: Check sales table</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'sales'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Sales table exists<br>";
    } else {
        echo "❌ Sales table does not exist<br>";
    }

    // Test 5: Test total customers query
    echo "<h3>Test 5: Total customers query</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
    $result = $stmt->fetch();
    echo "Total customers: " . $result['total'] . "<br>";

    // Test 6: Test customers with debt query
    echo "<h3>Test 6: Customers with debt query</h3>";
    $query = "
        SELECT COUNT(DISTINCT c.id) as count 
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_type = 'قەرز'
        WHERE (c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0 OR 
               COALESCE(s.remaining_amount, 0) > 0)
    ";
    $stmt = $pdo->query($query);
    $result = $stmt->fetch();
    echo "Customers with debt: " . $result['count'] . "<br>";

    // Test 7: Test total debt query
    echo "<h3>Test 7: Total debt query</h3>";
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
    echo "Opening debt USD: " . ($result['total_opening_debt_usd'] ?? 0) . "<br>";
    echo "Opening debt IQD: " . ($result['total_opening_debt_iqd'] ?? 0) . "<br>";
    echo "Remaining amount: " . ($result['total_remaining_amount'] ?? 0) . "<br>";

    // Test 8: Test sample data
    echo "<h3>Test 8: Sample customer data</h3>";
    $stmt = $pdo->query("SELECT id, name, opening_debt_usd, opening_debt_iqd FROM customers LIMIT 3");
    $customers = $stmt->fetchAll();
    foreach ($customers as $customer) {
        echo "Customer ID: " . $customer['id'] . ", Name: " . $customer['name'] . 
             ", Opening debt USD: " . $customer['opening_debt_usd'] . 
             ", Opening debt IQD: " . $customer['opening_debt_iqd'] . "<br>";
    }

    echo "<h3>✅ All tests completed successfully!</h3>";

} catch (Exception $e) {
    echo "<h3>❌ Error occurred:</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?> 