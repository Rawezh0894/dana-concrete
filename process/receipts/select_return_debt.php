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
$job_filter = isset($_GET['job_filter']) ? $_GET['job_filter'] : 'all';
$job_specific = isset($_GET['job_specific']) ? trim($_GET['job_specific']) : '';

// Build the main query with optional JOIN for job filtering
if ($job_filter === 'specific' && $job_specific) {
    // Use a more comprehensive approach to find payments by job/work
    $sql = "SELECT DISTINCT cdp.paid_usd, cdp.paid_iqd, cdp.date, cdp.discount, cdp.note, cdp.dolar_rate,
                   GROUP_CONCAT(DISTINCT s.location SEPARATOR ', ') as related_locations,
                   GROUP_CONCAT(DISTINCT s.invoice_number SEPARATOR ', ') as related_invoices
            FROM customer_debt_payments cdp
            LEFT JOIN customer_payment_allocations cpa ON cdp.id = cpa.debt_payment_id
            LEFT JOIN sales s ON cpa.sale_id = s.id
            WHERE cdp.customer_id = :customer_id
            AND (
                cdp.note LIKE :job_specific 
                OR s.location LIKE :job_specific 
                OR s.invoice_number LIKE :job_specific
            )
            GROUP BY cdp.id, cdp.paid_usd, cdp.paid_iqd, cdp.date, cdp.discount, cdp.note, cdp.dolar_rate";
    
    $params = ['customer_id' => $customer_id, 'job_specific' => '%' . $job_specific . '%'];
    
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
} else {
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
}
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Error in select_return_debt.php: " . $e->getMessage());
    error_log("SQL: " . $sql);
    error_log("Params: " . print_r($params, true));
    
    // Return empty array on error
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}
