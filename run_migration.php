<?php
// Database Migration Script for Unit System
// Run this script to add the missing columns to purchase_materials table

require_once 'config/db_conected.php';

echo "Starting database migration for unit system...\n";

try {
    // Read the migration SQL file
    $migration_sql = file_get_contents('database_migration_unit_system.sql');
    
    if (!$migration_sql) {
        throw new Exception("Could not read migration file");
    }
    
    // Split the SQL into individual statements
    $statements = explode(';', $migration_sql);
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $success_count++;
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            // Skip if column already exists or function already exists
            if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                strpos($e->getMessage(), 'Duplicate function name') !== false ||
                strpos($e->getMessage(), 'Duplicate view name') !== false) {
                echo "⚠ Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
                $success_count++;
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
                echo "Statement: " . $statement . "\n";
                $error_count++;
            }
        }
    }
    
    echo "\nMigration completed!\n";
    echo "Successful statements: $success_count\n";
    echo "Errors: $error_count\n";
    
    if ($error_count == 0) {
        echo "\n✅ Migration successful! The purchase materials table now has all required unit system columns.\n";
    } else {
        echo "\n⚠ Some errors occurred, but the migration may still be partially successful.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
?> 