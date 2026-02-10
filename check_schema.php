<?php
require_once 'config/db_conected.php';
$stmt = $pdo->query("DESCRIBE employees");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($columns, JSON_PRETTY_PRINT);
?>
