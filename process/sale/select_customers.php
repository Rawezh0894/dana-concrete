<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');
$customers = $pdo->query('SELECT id, name FROM customers')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($customers); 