<?php
require_once 'config/db_conected.php';

function describeTable($pdo, $tableName) {
    try {
        echo "Structure of $tableName:\n";
        $stmt = $pdo->query("DESCRIBE $tableName");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo " - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    } catch (PDOException $e) {
        echo "Error describing $tableName: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

describeTable($pdo, 'employees');
describeTable($pdo, 'employee_transactions');
describeTable($pdo, 'salary_generations');
?>
