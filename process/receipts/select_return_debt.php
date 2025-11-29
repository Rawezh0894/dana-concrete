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
$allocation_filter = isset($_GET['allocation_filter']) ? $_GET['allocation_filter'] : 'all';
$valid_allocation_filters = ['all', 'allocated', 'unallocated'];
if (!in_array($allocation_filter, $valid_allocation_filters, true)) {
    $allocation_filter = 'all';
}

// Prepare base SQL components
$select = "SELECT DISTINCT cdp.id, cdp.paid_usd, cdp.paid_iqd, cdp.date, cdp.discount, cdp.note, cdp.dolar_rate";
$from = " FROM customer_debt_payments cdp";
$joins = "";
$where = ["cdp.customer_id = :customer_id"];
$params = ['customer_id' => $customer_id];
$orderBy = " ORDER BY cdp.date ASC";
$invoice_numbers = [];
$hasInvoiceFilter = false;

if ($invoice_number) {
    $invoice_numbers = array_filter(array_map('trim', explode(',', $invoice_number)));
    if (empty($invoice_numbers)) {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $hasInvoiceFilter = true;
    $joins .= " INNER JOIN customer_payment_allocations cpa ON cdp.id = cpa.debt_payment_id
                INNER JOIN sales s ON cpa.sale_id = s.id";
    $invoiceConditions = [];
    foreach ($invoice_numbers as $index => $inv_num) {
        $param_name = 'invoice_number_' . $index;
        $invoiceConditions[] = "s.invoice_number LIKE :$param_name";
        $params[$param_name] = '%' . $inv_num . '%';
    }
    $where[] = '(' . implode(' OR ', $invoiceConditions) . ')';
    $orderBy = " ORDER BY cdp.date ASC";
} elseif ($allocation_filter !== 'all') {
    $joins .= " LEFT JOIN customer_payment_allocations cpa ON cdp.id = cpa.debt_payment_id";
}

if ($allocation_filter === 'allocated') {
    if ($hasInvoiceFilter) {
        // already ensures allocations due to INNER JOIN
    } else {
        $where[] = "cpa.debt_payment_id IS NOT NULL";
    }
} elseif ($allocation_filter === 'unallocated') {
    if ($hasInvoiceFilter) {
        // impossible to have invoice filter with unallocated payments
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $where[] = "cpa.debt_payment_id IS NULL";
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

$sql = $select . $from . $joins . ' WHERE ' . implode(' AND ', $where) . $orderBy;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = $row;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
