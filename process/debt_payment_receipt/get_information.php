<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'تکایە بەژوور بە']);
    exit;
}

// Check if user has permission
if (!hasPermission('print_concrete_receipts')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'توانای دەست گەیشتنت نییە']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $debt_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Debug logging
    error_log('Debt Payment Receipt - Requested ID: ' . $debt_id);
    error_log('Debt Payment Receipt - GET params: ' . print_r($_GET, true));
    
    if (!$debt_id) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی دانەوەی قەرز دیاری نەکراوە']);
        exit;
    }
    
    // Get debt payment information with customer details
    $stmt = $pdo->prepare('
        SELECT 
            dp.*,
            c.name AS customer_name,
            c.mobile1 AS customer_phone,
            c.mobile2 AS customer_address
        FROM customer_debt_payments dp
        LEFT JOIN customers c ON dp.customer_id = c.id
        WHERE dp.id = ?
    ');
    
    $stmt->execute([$debt_id]);
    $debt_payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug logging
    error_log('Debt Payment Receipt - Query result: ' . print_r($debt_payment, true));
    
    if (!$debt_payment) {
        error_log('Debt Payment Receipt - No debt payment found for ID: ' . $debt_id);
        echo json_encode(['success' => false, 'message' => 'دانەوەی قەرز نەدۆزرایەوە']);
        exit;
    }
    
    // Format the data for the receipt
    $receipt_data = [
        'id' => $debt_payment['id'],
        'customer_id' => $debt_payment['customer_id'],
        'receipt_number' => 'QW-' . str_pad($debt_payment['id'], 4, '0', STR_PAD_LEFT),
        'customer_name' => $debt_payment['customer_name'],
        'customer_phone' => $debt_payment['customer_phone'],
        'customer_address' => $debt_payment['customer_address'],
        'payment_date' => $debt_payment['date'],
        'date' => $debt_payment['date'],
        'dolar_rate' => $debt_payment['dolar_rate'],
        'paid_usd' => $debt_payment['paid_usd'],
        'paid_iqd' => $debt_payment['paid_iqd'],
        'discount' => $debt_payment['discount'],
        'note' => $debt_payment['note']
    ];
    
    echo json_encode(['success' => true, 'data' => $receipt_data]);
    
} catch (PDOException $e) {
    error_log('PDOException in debt_payment_receipt/get_information.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in debt_payment_receipt/get_information.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
} 