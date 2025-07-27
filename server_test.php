<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Server Database Test ===\n";

try {
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
    echo "✅ Database connection successful\n";
    
    // Check if list_materials table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'list_materials'");
    if ($stmt->rowCount() > 0) {
        echo "✅ list_materials table exists\n";
        
        // Count records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM list_materials");
        $count = $stmt->fetch()['count'];
        echo "📊 Records in table: $count\n";
        
    } else {
        echo "❌ list_materials table does NOT exist\n";
        echo "Run: mysql -u dana_user -p dana_concrete_db < create_list_materials.sql\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}

echo "PHP Version: " . phpversion() . "\n";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'Loaded' : 'Not loaded') . "\n";
?> 