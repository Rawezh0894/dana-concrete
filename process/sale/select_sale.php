<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('view_sale')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$where = [];
$params = [];
if ($from) {
    $where[] = "s.order_date >= ?";
    $params[] = $from;
}
if ($to) {
    $where[] = "s.order_date <= ?";
    $params[] = $to;
}
$sql = "SELECT s.*, c.name AS customer_name, f.name AS formula_name, r.id AS recipient_id
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id 
        LEFT JOIN concrete_formulas f ON s.formula_id = f.id
        LEFT JOIN recipients r ON r.name = s.recipient";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY s.order_date ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $sales]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
