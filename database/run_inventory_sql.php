<?php
require_once __DIR__ . '/../config/db_conected.php';

// Only allow admins or specific users if needed
// For now, it's a simple runner

try {
    $sql = file_get_contents(__DIR__ . '/create_inventory_tables.sql');
    $pdo->exec($sql);
    echo "Summary: Inventory tables created successfully.";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
