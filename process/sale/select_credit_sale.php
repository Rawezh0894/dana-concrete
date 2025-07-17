<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

$where = "payment_type = 'قەرز'";
$params = [];
if (!empty($_GET['from'])) {
    $where .= ' AND order_date >= ?';
    $params[] = $_GET['from'];
}
if (!empty($_GET['to'])) {
    $where .= ' AND order_date <= ?';
    $params[] = $_GET['to'];
}
$sql = "SELECT s.*, c.name as customer_name, f.name as formula_name FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN concrete_formulas f ON s.formula_id = f.id
        WHERE $where ORDER BY s.order_date DESC, s.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data); 