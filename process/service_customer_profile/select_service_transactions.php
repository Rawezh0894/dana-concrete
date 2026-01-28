<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

if (!isset($_GET['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'customer_id is required']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $customerId = (int)$_GET['customer_id'];
    
    $sql = "SELECT 
                *,
                (meter_amount * price_per_meter) as total_price,
                (COALESCE(paid_usd, 0) + (COALESCE(paid_iqd, 0) / NULLIF(exchange_rate, 0))) as total_paid
            FROM service_receipts 
            WHERE customer_id = ? 
            ORDER BY created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customerId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'data' => $transactions
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
