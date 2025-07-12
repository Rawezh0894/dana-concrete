<?php
require_once '../../config/db_conected.php';
$stmt = $pdo->query("SELECT id, name FROM cars ORDER BY id DESC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
