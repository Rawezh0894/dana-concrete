<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');
$company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($company_id <= 0) {
    echo json_encode([]);
    exit;
}

// Get date filters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Build date filter conditions
$date_condition = '';
$params = [$company_id];
if ($from_date && $to_date) {
    $date_condition = ' AND p.date >= ? AND p.date <= ?';
    $params[] = $from_date;
    $params[] = $to_date;
} elseif ($from_date) {
    $date_condition = ' AND p.date >= ?';
    $params[] = $from_date;
} elseif ($to_date) {
    $date_condition = ' AND p.date <= ?';
    $params[] = $to_date;
}

$sql = "SELECT p.id, c.name AS company_name, l.name AS location_name, d.name AS driver_name, p.invoice_number, m.name AS material_name, p.date, p.payment_type, p.type, p.kg, p.price_per_kg_usd, p.price_per_kg_iqd, p.price, p.amount_iqd, p.exchange_rate, p.paid_usd, p.paid_iqd, p.remaining_usd, p.remaining_iqd FROM purchases p LEFT JOIN company c ON p.company_id = c.id LEFT JOIN drivers d ON p.driver = d.name LEFT JOIN locations l ON p.location = l.name LEFT JOIN materials m ON p.material_id = m.id WHERE p.company_id = ?" . $date_condition . " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$purchases = $stmt->fetchAll();
echo json_encode($purchases);
