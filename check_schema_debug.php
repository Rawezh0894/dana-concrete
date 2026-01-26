<?php
require_once 'config/db_conected.php';

try {
    $stmt = $pdo->query("DESCRIBE employee_expenses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if ($col['Field'] === 'expense_type') {
            echo "Column: " . $col['Field'] . "\n";
            echo "Type: " . $col['Type'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
