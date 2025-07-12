<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
if (!$customer_id) { echo json_encode([]); exit; }
$month = isset($_GET['month']) ? $_GET['month'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$sql = "SELECT paid_usd, paid_iqd, date, discount, note, dolar_rate FROM customer_debt_payments WHERE customer_id = :customer_id";
$params = ['customer_id' => $customer_id];
if ($month !== 'all') {
    $sql .= " AND MONTH(date) = :month";
    $params['month'] = $month;
}
if ($date_from) {
    $sql .= " AND date >= :date_from";
    $params['date_from'] = $date_from;
}
if ($date_to) {
    $sql .= " AND date <= :date_to";
    $params['date_to'] = $date_to;
}
$sql .= " ORDER BY date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = $row;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
