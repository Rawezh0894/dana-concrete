<?php
require_once 'config/db_conected.php';

try {
    $stmt = $pdo->query("SHOW CREATE TABLE sales");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Create Table Statement:\n";
    print_r($result);
    
    // Also check indices specifically
    $stmt = $pdo->query("SHOW INDEX FROM sales");
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nIndices:\n";
    foreach ($indices as $idx) {
        if ($idx['Column_name'] == 'invoice_number') {
            echo "Index on invoice_number: " . $idx['Key_name'] . " (Unique: " . ($idx['Non_unique'] == 0 ? 'Yes' : 'No') . ")\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
