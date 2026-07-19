<?php
require_once 'c:\xampp\htdocs\dana-concrete\config\db_conected.php';
$stmt = $pdo->query("DESCRIBE purchases");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $column) {
    echo $column['Field'] . "\n";
}
?>
