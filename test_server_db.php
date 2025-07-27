<?php
// Simple database connection test for server
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Server Database Connection Test</h2>";

try {
    // Test connection with same settings as db_conected.php
    $host = 'localhost';
    $db   = 'dana_concrete_db';
    $user = 'dana_user';
    $pass = 'Rawezh.Jaza@0894';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✅ Database connection successful<br>";
    
    // Test if list_materials table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'list_materials'");
    if ($stmt->rowCount() > 0) {
        echo "✅ list_materials table exists<br>";
        
        // Test INSERT
        $testName = "Server Test " . date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO list_materials (name, quantity, currency_type) VALUES (?, ?, ?)");
        $result = $stmt->execute([$testName, 1.00, 'دینار']);
        
        if ($result) {
            echo "✅ INSERT test successful<br>";
            
            // Clean up
            $stmt = $pdo->prepare("DELETE FROM list_materials WHERE name = ?");
            $stmt->execute([$testName]);
            echo "🧹 Test record cleaned up<br>";
        } else {
            echo "❌ INSERT test failed<br>";
        }
    } else {
        echo "❌ list_materials table does NOT exist<br>";
        echo "<p>Run: <code>mysql -u dana_user -p dana_concrete_db < create_list_materials.sql</code></p>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
}

echo "<h3>PHP Info:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ Loaded' : '❌ Not loaded') . "<br>";
echo "Error Log: " . ini_get('error_log') . "<br>";
?> 