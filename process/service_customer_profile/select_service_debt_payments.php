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
    
    $sql = "SELECT * FROM service_debt_payments WHERE customer_id = ? ORDER BY payment_date DESC, id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customerId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'data' => $payments
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
