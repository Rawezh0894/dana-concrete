<?php
// Suppress warnings to see output clearly
error_reporting(E_ERROR | E_PARSE);
// Prevent session start warning if possible, but hard if it's in require
// We'll just ignore the noise

// Mock session to prevent undefined index errors if db_conected uses it
if (session_status() == PHP_SESSION_NONE) {
    // session_start(); // We prefer to not start it if we can avoid it, but db_conected likely does.
    $_SESSION['user_id'] = 1; // Dummy
}

require_once 'config/db_conected.php';

try {
    echo "Attempting to fix 'expense_type' column...\n";
    
    // Check current state
    $stmt = $pdo->query("DESCRIBE employee_expenses expense_type");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current definition: " . $col['Type'] . "\n";
    
    // Changing to VARCHAR(50) covers existing ENUM values and the new longer value
    $sql = "ALTER TABLE employee_expenses MODIFY COLUMN expense_type VARCHAR(50) NOT NULL";
    $pdo->exec($sql);
    
    echo "Table altered successfully.\n";
    
    // Verify
    $stmt = $pdo->query("DESCRIBE employee_expenses expense_type");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "New definition: " . $col['Type'] . "\n";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
