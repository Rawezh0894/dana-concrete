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
$invoice_number = isset($_GET['invoice_number']) ? trim($_GET['invoice_number']) : '';

// Base query - if invoice number filter is provided, join with allocations and sales tables
if ($invoice_number) {
    $sql = "SELECT DISTINCT cdp.paid_usd, cdp.paid_iqd, cdp.date, cdp.discount, cdp.note, cdp.dolar_rate 
            FROM customer_debt_payments cdp
            INNER JOIN customer_payment_allocations cpa ON cdp.id = cpa.debt_payment_id
            INNER JOIN sales s ON cpa.sale_id = s.id
            WHERE cdp.customer_id = :customer_id 
            AND s.invoice_number LIKE :invoice_number";
    $params = [
        'customer_id' => $customer_id,
        'invoice_number' => '%' . $invoice_number . '%'
    ];
} else {
    $sql = "SELECT paid_usd, paid_iqd, date, discount, note, dolar_rate FROM customer_debt_payments WHERE customer_id = :customer_id";
    $params = ['customer_id' => $customer_id];
}

if ($month !== 'all') {
    if ($invoice_number) {
        $sql .= " AND MONTH(cdp.date) = :month";
    } else {
        $sql .= " AND MONTH(date) = :month";
    }
    $params['month'] = $month;
}
if ($date_from) {
    if ($invoice_number) {
        $sql .= " AND cdp.date >= :date_from";
    } else {
        $sql .= " AND date >= :date_from";
    }
    $params['date_from'] = $date_from;
}
if ($date_to) {
    if ($invoice_number) {
        $sql .= " AND cdp.date <= :date_to";
    } else {
        $sql .= " AND date <= :date_to";
    }
    $params['date_to'] = $date_to;
}
if ($invoice_number) {
    $sql .= " ORDER BY cdp.date ASC";
} else {
    $sql .= " ORDER BY date ASC";
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = $row;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
