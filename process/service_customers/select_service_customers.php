<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

if (!hasPermission('view_service_customers')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Select customers who have at least one service receipt
    // We also aggregate their financial totals for the list view
    $sql = "SELECT 
                c.id, 
                c.name, 
                c.mobile1, 
                c.mobile2,
                COUNT(sr.id) as receipts_count,
                SUM(sr.meter_amount * COALESCE(sr.price_per_meter, 0)) as total_usd,
                SUM(COALESCE(sr.paid_usd, 0) + (COALESCE(sr.paid_iqd, 0) / NULLIF(sr.exchange_rate, 0))) as total_paid_usd
            FROM customers c
            INNER JOIN service_receipts sr ON c.id = sr.customer_id
            GROUP BY c.id
            ORDER BY c.name ASC";
            
    $stmt = $pdo->query($sql);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate remaining balance for each
    foreach ($customers as &$customer) {
        $customer['balance'] = (float)$customer['total_usd'] - (float)$customer['total_paid_usd'];
        $customer['total_usd'] = (float)$customer['total_usd'];
        $customer['total_paid_usd'] = (float)$customer['total_paid_usd'];
    }

    echo json_encode([
        'success' => true, 
        'data' => $customers
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
