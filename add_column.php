<?php
require_once 'config/db_conected.php';
try {
    $pdo->exec("ALTER TABLE employees ADD COLUMN join_date DATE DEFAULT NULL");
    echo "Column join_date added successfully";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column join_date already exists";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
