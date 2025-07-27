<?php
// Check database tables on server
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Tables Check</h2>";

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
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tables on Server:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Check specific tables that are needed
    $required_tables = [
        'list_materials',
        'other_expense_persons', 
        'purchase_material_items',
        'purchase_materials',
        'materials'
    ];
    
    echo "<h3>Required Tables Check:</h3>";
    foreach ($required_tables as $table) {
        if (in_array($table, $tables)) {
            echo "<p style='color: green;'>✅ $table exists</p>";
            
            // Count records
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $result = $stmt->fetch();
                echo "<p>📊 Records in $table: " . $result['count'] . "</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Error counting $table: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ $table does not exist</p>";
        }
    }
    
    // Check purchase_materials structure
    if (in_array('purchase_materials', $tables)) {
        echo "<h3>purchase_materials Table Structure:</h3>";
        $stmt = $pdo->query("DESCRIBE purchase_materials");
        $columns = $stmt->fetchAll();
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}
?> 