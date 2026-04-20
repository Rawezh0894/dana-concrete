<?php
require_once dirname(__DIR__) . '/config/db_conected.php';

try {
    $pdo->exec("ALTER TABLE inv_purchases ADD COLUMN person_id INT AFTER invoice_number");
    echo "Column person_id added to inv_purchases successfully.\n";
} catch (Exception $e) {
    echo "Error or already exists: " . $e->getMessage() . "\n";
}
?>
