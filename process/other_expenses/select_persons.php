<?php
require_once '../../config/db_conected.php';
$stmt = $pdo->query("SELECT id, name, expense_usd, expense_iqd FROM other_expense_persons ORDER BY name ASC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data); 