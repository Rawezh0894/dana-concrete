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
if (!hasPermission('view_purchase')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ بینینی کڕینەکان']);
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT p.*, l.id as location_id, d.id as driver_id FROM purchases p LEFT JOIN locations l ON p.location = l.name LEFT JOIN drivers d ON p.driver = d.name WHERE p.id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        // Overwrite location and driver fields with their ids for the modal
        $row['location_id'] = $row['location_id'] ?? '';
        $row['driver_id'] = $row['driver_id'] ?? '';
    }
    echo json_encode($row);
    exit;
}

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$where = [];
$params = [];
if ($from) {
    $where[] = "p.date >= ?";
    $params[] = $from;
}
if ($to) {
    $where[] = "p.date <= ?";
    $params[] = $to;
}
$sql = "SELECT p.id, c.name AS company_name, l.name AS location_name, d.name AS driver_name, p.invoice_number, m.name AS material_name, p.date, p.payment_type, p.type, p.kg, p.price_per_kg_usd, p.price_per_kg_iqd, p.price, p.amount_iqd, p.exchange_rate, p.paid_usd, p.paid_iqd, p.remaining_usd, p.remaining_iqd, b.name AS bin_name
FROM purchases p
LEFT JOIN company c ON p.company_id = c.id
LEFT JOIN locations l ON p.location = l.name
LEFT JOIN drivers d ON p.driver = d.name
LEFT JOIN materials m ON p.material_id = m.id
LEFT JOIN bins_silos b ON p.bin_id = b.id";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY p.date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
