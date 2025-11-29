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
$invoice_allocation = isset($_GET['invoice_allocation']) ? $_GET['invoice_allocation'] : 'all';
$invoice_allocation = in_array($invoice_allocation, ['all', 'linked', 'unlinked']) ? $invoice_allocation : 'all';

$params = ['customer_id' => $customer_id];
$where = ["cdp.customer_id = :customer_id"];
$joins = '';

// Handle invoice number filtering with joins
if ($invoice_number) {
    $invoice_numbers = array_map('trim', explode(',', $invoice_number));
    $invoice_numbers = array_filter($invoice_numbers);
    
    if (empty($invoice_numbers)) {
        echo json_encode([]);
        exit;
    }
    
    $joins .= " INNER JOIN customer_payment_allocations cpa_filter ON cpa_filter.debt_payment_id = cdp.id
                INNER JOIN sales s ON s.id = cpa_filter.sale_id";
    $invoiceConditions = [];
    foreach ($invoice_numbers as $index => $inv_num) {
        $param_name = 'invoice_number_' . $index;
        $invoiceConditions[] = "s.invoice_number LIKE :" . $param_name;
        $params[$param_name] = '%' . $inv_num . '%';
    }
    if ($invoiceConditions) {
        $where[] = '(' . implode(' OR ', $invoiceConditions) . ')';
    }
}

// Handle allocation-only filter
if ($invoice_allocation === 'linked') {
    $where[] = "EXISTS (
        SELECT 1 FROM customer_payment_allocations cpa_link
        WHERE cpa_link.debt_payment_id = cdp.id
    )";
} elseif ($invoice_allocation === 'unlinked') {
    $where[] = "NOT EXISTS (
        SELECT 1 FROM customer_payment_allocations cpa_unlink
        WHERE cpa_unlink.debt_payment_id = cdp.id
    )";
}

if ($month !== 'all') {
    $where[] = "MONTH(cdp.date) = :month";
    $params['month'] = $month;
}
if ($date_from) {
    $where[] = "cdp.date >= :date_from";
    $params['date_from'] = $date_from;
}
if ($date_to) {
    $where[] = "cdp.date <= :date_to";
    $params['date_to'] = $date_to;
}

$sql = "SELECT cdp.paid_usd, cdp.paid_iqd, cdp.date, cdp.discount, cdp.note, cdp.dolar_rate
        FROM customer_debt_payments cdp
        $joins";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= " ORDER BY cdp.date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = $row;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);

