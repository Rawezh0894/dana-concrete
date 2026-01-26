<?php
require_once 'config/db_conected.php';

try {
    $stmt = $pdo->query("SHOW INDEX FROM sales WHERE Column_name = 'invoice_number'");
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($indices)) {
        echo "No index on invoice_number.\n";
    } else {
        foreach ($indices as $idx) {
            echo "Index: " . $idx['Key_name'] . ", Unique: " . ($idx['Non_unique'] == 0 ? 'Yes' : 'No') . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
