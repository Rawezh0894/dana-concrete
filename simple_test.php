<?php
echo "Testing database connection...\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=dana_concrete_db;charset=utf8mb4", "dana_user", "Rawezh.Jaza@0894");
    echo "Database connected successfully!\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'list_materials'");
    if ($stmt->rowCount() > 0) {
        echo "list_materials table exists!\n";
    } else {
        echo "list_materials table does NOT exist!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 