<?php
require_once '../../config/db_conected.php';
$stmt = $pdo->query("SHOW COLUMNS FROM employees");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}
?>
