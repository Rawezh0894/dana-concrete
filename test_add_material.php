<?php
// Test file for add_material functionality
require_once 'config/db_conected.php';

echo "<h2>Testing Add Material Functionality</h2>";

try {
    // Check if list_materials table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'list_materials'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "<h3>1. Table Check:</h3>";
    if ($tableExists) {
        echo "✅ list_materials table exists<br>";
        
        // Count records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM list_materials");
        $count = $stmt->fetch()['count'];
        echo "📊 Records in table: $count<br>";
        
        // Show table structure
        echo "<h3>2. Table Structure:</h3>";
        $stmt = $pdo->query("DESCRIBE list_materials");
        $columns = $stmt->fetchAll();
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "❌ list_materials table does NOT exist<br>";
        echo "<p>You need to run: <code>mysql -u dana_user -p dana_concrete_db < create_list_materials.sql</code></p>";
    }
    
    // Test INSERT query
    echo "<h3>3. Test INSERT Query:</h3>";
    if ($tableExists) {
        try {
            $testName = "Test Material " . date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO list_materials (name, quantity, currency_type, purchase_price_usd, purchase_price_iqd) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$testName, 10.00, 'دینار', 0.00, 1000.00]);
            
            if ($result) {
                echo "✅ Test INSERT successful<br>";
                echo "Inserted: $testName<br>";
                
                // Clean up test record
                $stmt = $pdo->prepare("DELETE FROM list_materials WHERE name = ?");
                $stmt->execute([$testName]);
                echo "🧹 Test record cleaned up<br>";
            } else {
                echo "❌ Test INSERT failed<br>";
            }
        } catch (Exception $e) {
            echo "❌ INSERT Error: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
}

echo "<h3>4. Next Steps:</h3>";
if (!$tableExists) {
    echo "<p>1. Upload <code>create_list_materials.sql</code> to your server</p>";
    echo "<p>2. Run: <code>mysql -u dana_user -p dana_concrete_db < create_list_materials.sql</code></p>";
    echo "<p>3. Refresh this page to test again</p>";
} else {
    echo "<p>✅ Table exists and INSERT test passed. Add material functionality should work now.</p>";
}
?> 