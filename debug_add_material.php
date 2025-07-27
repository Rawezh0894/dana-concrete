<?php
// Debug file for add_material error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Add Material Error</h2>";

try {
    // Test database connection
    echo "<h3>1. Database Connection Test:</h3>";
    require_once 'config/db_conected.php';
    echo "✅ Database connection successful<br>";
    
    // Check if list_materials table exists
    echo "<h3>2. Table Existence Check:</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'list_materials'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ list_materials table exists<br>";
        
        // Check table structure
        echo "<h3>3. Table Structure:</h3>";
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
    }
    
    // Test the exact query from add.php
    echo "<h3>4. Test INSERT Query (from add.php):</h3>";
    if ($tableExists) {
        try {
            // Simulate POST data
            $_POST['name'] = 'Test Material Debug';
            $_POST['quantity'] = 10.00;
            $_POST['currency_type'] = 'دینار';
            $_POST['purchase_price_usd'] = 0.00;
            $_POST['purchase_price_iqd'] = 1000.00;
            
            $name = $_POST['name'] ?? '';
            $quantity = $_POST['quantity'] ?? 0;
            $currency_type = $_POST['currency_type'] ?? 'دینار';
            $purchase_price_usd = $_POST['purchase_price_usd'] ?? 0;
            $purchase_price_iqd = $_POST['purchase_price_iqd'] ?? 0;
            
            echo "Name: $name<br>";
            echo "Quantity: $quantity<br>";
            echo "Currency: $currency_type<br>";
            echo "Price USD: $purchase_price_usd<br>";
            echo "Price IQD: $purchase_price_iqd<br>";
            
            if ($name !== '') {
                $query = "INSERT INTO list_materials (name, quantity, currency_type, purchase_price_usd, purchase_price_iqd) VALUES (?, ?, ?, ?, ?)";
                echo "Query: $query<br>";
                
                $stmt = $pdo->prepare($query);
                $result = $stmt->execute([$name, $quantity, $currency_type, $purchase_price_usd, $purchase_price_iqd]);
                
                if ($result) {
                    echo "✅ INSERT successful<br>";
                    
                    // Clean up
                    $stmt = $pdo->prepare("DELETE FROM list_materials WHERE name = ?");
                    $stmt->execute([$name]);
                    echo "🧹 Test record cleaned up<br>";
                } else {
                    echo "❌ INSERT failed<br>";
                }
            } else {
                echo "❌ Name is empty<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ INSERT Error: " . $e->getMessage() . "<br>";
            echo "Error Code: " . $e->getCode() . "<br>";
        }
    }
    
    // Check file permissions
    echo "<h3>5. File Permissions Check:</h3>";
    $addFile = 'process/add_material/add.php';
    if (file_exists($addFile)) {
        echo "✅ File exists: $addFile<br>";
        echo "Permissions: " . substr(sprintf('%o', fileperms($addFile)), -4) . "<br>";
        echo "Readable: " . (is_readable($addFile) ? 'Yes' : 'No') . "<br>";
    } else {
        echo "❌ File does not exist: $addFile<br>";
    }
    
    // Check PHP error log
    echo "<h3>6. PHP Error Log Check:</h3>";
    $errorLog = ini_get('error_log');
    if ($errorLog && file_exists($errorLog)) {
        echo "Error log: $errorLog<br>";
        $lastLines = shell_exec("tail -10 $errorLog 2>/dev/null");
        if ($lastLines) {
            echo "<pre>Last 10 lines of error log:\n$lastLines</pre>";
        } else {
            echo "No recent errors in log<br>";
        }
    } else {
        echo "Error log not found or not accessible<br>";
    }
    
} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<h3>7. Next Steps:</h3>";
echo "<p>1. Check the error details above</p>";
echo "<p>2. If table doesn't exist, run: <code>mysql -u dana_user -p dana_concrete_db < create_list_materials.sql</code></p>";
echo "<p>3. Check server error logs: <code>tail -f /var/log/apache2/error.log</code> or <code>tail -f /var/log/nginx/error.log</code></p>";
?> 