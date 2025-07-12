<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('delete_sale')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ بینینی ڕیسایکڵ بین']);
    exit;
}

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$where = [];
$params = [];
if ($from) {
    $where[] = "rbs.order_date >= ?";
    $params[] = $from;
}
if ($to) {
    $where[] = "rbs.order_date <= ?";
    $params[] = $to;
}
$sql = "SELECT rbs.id, rbs.original_id, c.name AS customer_name, rbs.recipient, rbs.location, rbs.invoice_number, f.name AS formula_name, rbs.order_date, rbs.payment_type, rbs.quantity, rbs.price_per_unit, rbs.total_price, rbs.amount_paid_iq, rbs.amount_paid_usd, rbs.remaining_amount, rbs.dolar_rate, rbs.notes, rbs.discount, rbs.deleted_at\nFROM recycle_bin_sales rbs\nLEFT JOIN customers c ON rbs.customer_id = c.id\nLEFT JOIN concrete_formulas f ON rbs.formula_id = f.id";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY rbs.deleted_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data); 