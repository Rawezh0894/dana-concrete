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
$sale_filter_mode = isset($_GET['sale_filter_mode']) ? $_GET['sale_filter_mode'] : 'all';

// Base query - handle invoice number filter and sale filter mode
if ($invoice_number || $sale_filter_mode !== 'all') {
    // Split invoice numbers by comma and clean them
    $invoice_numbers = [];
    if ($invoice_number) {
        $invoice_numbers = array_map('trim', explode(',', $invoice_number));
        $invoice_numbers = array_filter($invoice_numbers); // Remove empty values
    }
    
    // Build query based on filter mode
    if ($sale_filter_mode === 'with') {
        // Only show payments that have allocations (linked to sales)
        if (!empty($invoice_numbers)) {
            // Filter by specific invoice numbers
            $sql = "SELECT DISTINCT cdp.paid_usd, cdp.paid_iqd, cdp.date, cdp.discount, cdp.note, cdp.dolar_rate 
                    FROM customer_debt_payments cdp
                    INNER JOIN customer_payment_allocations cpa ON cdp.id = cpa.debt_payment_id
                    INNER JOIN sales s ON cpa.sale_id = s.id
                    WHERE cdp.customer_id = :customer_id 
                    AND (";
            
            $params = ['customer_id' => $customer_id];
            $conditions = [];
            
            foreach ($invoice_numbers as $index => $inv_num) {
                $param_name = 'invoice_number_' . $index;
                $conditions[] = "s.invoice_number LIKE :" . $param_name;
                $params[$param_name] = '%' . $inv_num . '%';
            }
            
            $sql .= implode(' OR ', $conditions) . ")";
        } else {
            // Show all payments that have allocations (linked to any sales)
            $sql = "SELECT DISTINCT cdp.paid_usd, cdp.paid_iqd, cdp.date, cdp.discount, cdp.note, cdp.dolar_rate 
                    FROM customer_debt_payments cdp
                    INNER JOIN customer_payment_allocations cpa ON cdp.id = cpa.debt_payment_id
                    WHERE cdp.customer_id = :customer_id";
            $params = ['customer_id' => $customer_id];
        }
    } else if ($sale_filter_mode === 'without') {
        // Only show payments that don't have allocations (not linked to any sales)
        if (!empty($invoice_numbers)) {
            // This combination doesn't make sense - if filtering by invoice, we need allocations
            // Return empty result
            $sql = "SELECT paid_usd, paid_iqd, date, discount, note, dolar_rate FROM customer_debt_payments WHERE 1=0";
            $params = [];
        } else {
            // Show payments that don't have any allocations
            $sql = "SELECT cdp.paid_usd, cdp.paid_iqd, cdp.date, cdp.discount, cdp.note, cdp.dolar_rate 
                    FROM customer_debt_payments cdp
                    LEFT JOIN customer_payment_allocations cpa ON cdp.id = cpa.debt_payment_id
                    WHERE cdp.customer_id = :customer_id 
                    AND cpa.id IS NULL";
            $params = ['customer_id' => $customer_id];
        }
    } else {
        // sale_filter_mode === 'all' - show all payments, but filter by invoice if provided
        if (!empty($invoice_numbers)) {
            $sql = "SELECT DISTINCT cdp.paid_usd, cdp.paid_iqd, cdp.date, cdp.discount, cdp.note, cdp.dolar_rate 
                    FROM customer_debt_payments cdp
                    INNER JOIN customer_payment_allocations cpa ON cdp.id = cpa.debt_payment_id
                    INNER JOIN sales s ON cpa.sale_id = s.id
                    WHERE cdp.customer_id = :customer_id 
                    AND (";
            
            $params = ['customer_id' => $customer_id];
            $conditions = [];
            
            foreach ($invoice_numbers as $index => $inv_num) {
                $param_name = 'invoice_number_' . $index;
                $conditions[] = "s.invoice_number LIKE :" . $param_name;
                $params[$param_name] = '%' . $inv_num . '%';
            }
            
            $sql .= implode(' OR ', $conditions) . ")";
        } else {
            $sql = "SELECT paid_usd, paid_iqd, date, discount, note, dolar_rate FROM customer_debt_payments WHERE customer_id = :customer_id";
            $params = ['customer_id' => $customer_id];
        }
    }
} else {
    $sql = "SELECT paid_usd, paid_iqd, date, discount, note, dolar_rate FROM customer_debt_payments WHERE customer_id = :customer_id";
    $params = ['customer_id' => $customer_id];
}

// Add date filters - check if we're using joined tables
$using_joined_tables = ($invoice_number || $sale_filter_mode !== 'all');
$date_prefix = $using_joined_tables ? 'cdp.' : '';

if ($month !== 'all') {
    $sql .= " AND MONTH(" . $date_prefix . "date) = :month";
    $params['month'] = $month;
}
if ($date_from) {
    $sql .= " AND " . $date_prefix . "date >= :date_from";
    $params['date_from'] = $date_from;
}
if ($date_to) {
    $sql .= " AND " . $date_prefix . "date <= :date_to";
    $params['date_to'] = $date_to;
}

$sql .= " ORDER BY " . $date_prefix . "date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = $row;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
