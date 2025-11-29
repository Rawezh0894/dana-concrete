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
$invoice_filter_mode = isset($_GET['invoice_filter_mode']) ? $_GET['invoice_filter_mode'] : 'include';
$invoice_filter_mode = $invoice_filter_mode === 'exclude' ? 'exclude' : 'include';

// Start base query
$sql = "SELECT cdp.id, cdp.paid_usd, cdp.paid_iqd, cdp.date, cdp.discount, cdp.note, cdp.dolar_rate 
        FROM customer_debt_payments cdp
        WHERE cdp.customer_id = :customer_id";
$params = ['customer_id' => $customer_id];

// Handle invoice number filtering (include/exclude specific invoices)
if ($invoice_number) {
    $invoice_numbers = array_map('trim', explode(',', $invoice_number));
    $invoice_numbers = array_filter($invoice_numbers);
    
    if (!empty($invoice_numbers)) {
        $invoiceConditions = [];
        foreach ($invoice_numbers as $index => $inv_num) {
            $param_name = ':invoice_number_' . $index;
            $invoiceConditions[] = "s.invoice_number LIKE $param_name";
            $params[$param_name] = '%' . $inv_num . '%';
        }
        $invoiceConditionSql = implode(' OR ', $invoiceConditions);
        
        if ($invoice_filter_mode === 'exclude') {
            $sql .= " AND NOT EXISTS (
                SELECT 1 
                FROM customer_payment_allocations cpa 
                INNER JOIN sales s ON cpa.sale_id = s.id 
                WHERE cpa.debt_payment_id = cdp.id
                  AND ($invoiceConditionSql)
            )";
        } else {
            $sql .= " AND EXISTS (
                SELECT 1 
                FROM customer_payment_allocations cpa 
                INNER JOIN sales s ON cpa.sale_id = s.id 
                WHERE cpa.debt_payment_id = cdp.id
                  AND ($invoiceConditionSql)
            )";
        }
    } else {
        // No valid invoice numbers
        echo json_encode([]);
        exit;
    }
}

if ($month !== 'all') {
    $sql .= " AND MONTH(cdp.date) = :month";
    $params['month'] = $month;
}
if ($date_from) {
    $sql .= " AND cdp.date >= :date_from";
    $params['date_from'] = $date_from;
}
if ($date_to) {
    $sql .= " AND cdp.date <= :date_to";
    $params['date_to'] = $date_to;
}
$sql .= " ORDER BY cdp.date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = $row;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
