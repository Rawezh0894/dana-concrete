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
if (!hasPermission('delete_purchase')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ بینینی ڕیسایکڵ بین']);
    exit;
}

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$where = [];
$params = [];
if ($from) {
    $where[] = "rbp.date >= ?";
    $params[] = $from;
}
if ($to) {
    $where[] = "rbp.date <= ?";
    $params[] = $to;
}
$sql = "SELECT rbp.id, rbp.original_id, c.name AS company_name, l.name AS location_name, d.name AS driver_name, rbp.invoice_number, m.name AS material_name, rbp.date, rbp.payment_type, rbp.type, rbp.kg, rbp.price_per_kg_usd, rbp.price_per_kg_iqd, rbp.price, rbp.amount_iqd, rbp.exchange_rate, rbp.paid_usd, rbp.paid_iqd, rbp.remaining_usd, rbp.remaining_iqd, b.name AS bin_name, rbp.deleted_at\nFROM recycle_bin_purchases rbp\nLEFT JOIN company c ON rbp.company_id = c.id\nLEFT JOIN locations l ON rbp.location = l.name\nLEFT JOIN drivers d ON rbp.driver = d.name\nLEFT JOIN materials m ON rbp.material_id = m.id\nLEFT JOIN bins_silos b ON rbp.bin_id = b.id";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY rbp.deleted_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data); 