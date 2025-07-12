<?php
require_once '../../config/db_conected.php';
$stmt = $pdo->query("SELECT id, name FROM employees ORDER BY name ASC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data); 