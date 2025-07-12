<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');
$company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($company_id <= 0) {
    echo json_encode([]);
    exit;
}
$sql = "SELECT p.id, c.name AS company_name, l.name AS location_name, d.name AS driver_name, p.invoice_number, m.name AS material_name, p.date, p.payment_type, p.type, p.kg, p.price_per_kg_usd, p.price_per_kg_iqd, p.price, p.amount_iqd, p.exchange_rate, p.paid_usd, p.paid_iqd, p.remaining_usd, p.remaining_iqd FROM purchases p LEFT JOIN company c ON p.company_id = c.id LEFT JOIN drivers d ON p.driver = d.name LEFT JOIN locations l ON p.location = l.name LEFT JOIN materials m ON p.material_id = m.id WHERE p.company_id = ? ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$company_id]);
$purchases = $stmt->fetchAll();
echo json_encode($purchases);
