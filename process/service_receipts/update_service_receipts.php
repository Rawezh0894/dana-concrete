<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

// Check if user has permission to edit service receipts
if (!hasPermission('edit_service_receipts')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$id = $_POST['id'] ?? null;
$receipt_number = $_POST['receipt_number'] ?? null;
$customer_id = $_POST['customer_id'] ?? null;
$location = $_POST['location'] ?? null;
$meter_amount = $_POST['meter_amount'] ?? null;
$price_per_meter = $_POST['price_per_meter'] ?? 0;
$pump_car_id = $_POST['pump_car_id'] ?? null;
$pump_driver_id = $_POST['pump_driver_id'] ?? null;
$mixer_car_id = $_POST['mixer_car_id'] ?? null;
$mixer_driver_id = $_POST['mixer_driver_id'] ?? null;
$receiver_name = $_POST['receiver_name'] ?? null;
$notes = $_POST['notes'] ?? null;
$created_at_input = $_POST['created_at'] ?? null;
$payment_type = $_POST['payment_type'] ?? 'credit';
$paid_usd = $_POST['paid_usd'] ?? 0;
$paid_iqd = $_POST['paid_iqd'] ?? 0;
$exchange_rate = $_POST['exchange_rate'] ?? 0;

if (!$id || !$receipt_number || !$meter_amount) {
    echo json_encode(['success' => false, 'message' => 'دڵنیابەرەوە لە پڕکردنەوەی ژمارەی پسوڵە و بڕی مەتر']);
    exit;
}

try {
    // Check if receipt exists
    $checkStmt = $pdo->prepare('SELECT id, receipt_number, created_at FROM service_receipts WHERE id = ?');
    $checkStmt->execute([$id]);
    $receipt = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receipt) {
        echo json_encode(['success' => false, 'message' => 'پسوڵە نەدۆزرایەوە!']);
        exit;
    }
    
    // Check for duplicate receipt_number (excluding current record)
    $dupCheck = $pdo->prepare('SELECT COUNT(*) FROM service_receipts WHERE receipt_number = ? AND id != ?');
    $dupCheck->execute([$receipt_number, $id]);
    if ($dupCheck->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی پسوڵە پێشتر تۆمارکراوە!']);
        exit;
    }
    
    $old_record = $receipt;

    // Determine created_at
    $new_created_at = $old_record['created_at'];
    if ($created_at_input) {
        $new_created_at = date('Y-m-d H:i:s', strtotime($created_at_input));
    }

    // Now perform the update
    $stmt = $pdo->prepare('UPDATE service_receipts SET receipt_number=?, customer_id=?, location=?, meter_amount=?, price_per_meter=?, pump_car_id=?, pump_driver_id=?, mixer_car_id=?, mixer_driver_id=?, receiver_name=?, notes=?, payment_type=?, paid_usd=?, paid_iqd=?, exchange_rate=?, created_at=? WHERE id=?');
    $result = $stmt->execute([
        $receipt_number,
        $customer_id ?: null,
        $location,
        $meter_amount,
        $price_per_meter,
        $pump_car_id !== '' ? $pump_car_id : null,
        $pump_driver_id !== '' ? $pump_driver_id : null,
        $mixer_car_id !== '' ? $mixer_car_id : null,
        $mixer_driver_id !== '' ? $mixer_driver_id : null,
        $receiver_name !== '' ? $receiver_name : null,
        $notes,
        $payment_type,
        $paid_usd,
        $paid_iqd,
        $exchange_rate,
        $new_created_at,
        $id
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'پسوڵە نوێکرایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هیچ گۆڕانکارییەک نەکرا!']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
