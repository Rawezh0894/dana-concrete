<?php
require_once 'config/db_conected.php';
$stmt = $pdo->query("SELECT * FROM settings");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
