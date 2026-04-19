<?php
require_once 'config/db_conected.php';

echo "Starting Financial System Database Installation...\n";

try {
    $sql = file_get_contents('database/financial_system_full.sql');
    
    // Split by semicolon to execute one by one (PDO doesn't like multi-queries in exec)
    // Actually, simple way would be to just use exec if it's multiple statements, but let's be safe
    $pdo->exec($sql);
    
    echo "SUCCESS: All tables and categories created successfully!\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR during installation: " . $e->getMessage() . "\n";
}
?>
