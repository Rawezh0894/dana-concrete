<?php
// Database Schema Check Script
// This script checks if the required unit system columns exist in the purchase_materials table

require_once 'config/db_conected.php';

echo "Checking database schema for unit system...\n\n";

try {
    // Check purchase_materials table structure
    $stmt = $pdo->query("DESCRIBE purchase_materials");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Purchase Materials Table Columns:\n";
    $required_columns = [
        'unit_type',
        'pieces_per_carton', 
        'bags_per_barrel',
        'liters_per_bag',
        'liters_per_barrel',
        'price_per_piece',
        'price_per_liter',
        'price_per_bag'
    ];
    
    $existing_columns = [];
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        $existing_columns[] = $column['Field'];
    }
    
    echo "\nChecking for required unit system columns:\n";
    $missing_columns = [];
    foreach ($required_columns as $required) {
        if (in_array($required, $existing_columns)) {
            echo "✓ " . $required . " - EXISTS\n";
        } else {
            echo "✗ " . $required . " - MISSING\n";
            $missing_columns[] = $required;
        }
    }
    
    if (empty($missing_columns)) {
        echo "\n✅ All required columns exist! The unit system should work properly.\n";
    } else {
        echo "\n❌ Missing columns: " . implode(', ', $missing_columns) . "\n";
        echo "You need to run the database migration to add these columns.\n";
        echo "Run the SQL commands in database_migration_unit_system.sql\n";
    }
    
    // Check if there are any existing purchase records
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM purchase_materials");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nExisting purchase records: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        // Check a sample record
        $stmt = $pdo->query("SELECT * FROM purchase_materials LIMIT 1");
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nSample purchase record:\n";
        foreach ($sample as $key => $value) {
            echo "- $key: $value\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error checking database: " . $e->getMessage() . "\n";
}
?> 