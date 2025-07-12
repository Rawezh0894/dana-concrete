<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
if (!$customer_id) { echo json_encode(['opening_debt_usd' => 0]); exit; }
$stmt = $pdo->prepare("SELECT opening_debt_usd FROM customers WHERE id = :id");
$stmt->execute(['id' => $customer_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode(['opening_debt_usd' => $row ? floatval($row['opening_debt_usd']) : 0]); 