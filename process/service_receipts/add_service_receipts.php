<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

// Check if user has permission to add service receipts
if (!hasPermission('add_service_receipts')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json');
try {
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
    $payment_type = $_POST['payment_type'] ?? 'credit';
    $paid_usd = $_POST['paid_usd'] ?? 0;
    $paid_iqd = $_POST['paid_iqd'] ?? 0;
    $exchange_rate = $_POST['exchange_rate'] ?? 0;
    
    // Basic validation
    if (!$receipt_number || !$meter_amount) {
        echo json_encode(['success' => false, 'message' => 'دڵنیابەرەوە لە پڕکردنەوەی ژمارەی پسوڵە و بڕی مەتر']);
        exit;
    }
    
    // Check for duplicate receipt number
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM service_receipts WHERE receipt_number = ?");
    $check_stmt->execute([$receipt_number]);
    $duplicate_count = $check_stmt->fetchColumn();
    
    if ($duplicate_count > 0) {
        echo json_encode(['success' => false, 'message' => 'ژمارەی پسوڵە دووبارەیە! تکایە ژمارەیەکی دیکە هەڵبژێرە']);
        exit;
    }
    
    // Get current timestamp in Iraq timezone
    $current_timestamp = getCurrentTimestamp();
    
    $stmt = $pdo->prepare("INSERT INTO service_receipts (receipt_number, customer_id, location, meter_amount, price_per_meter, pump_car_id, pump_driver_id, mixer_car_id, mixer_driver_id, receiver_name, notes, payment_type, paid_usd, paid_iqd, exchange_rate, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $receipt_number,
        $customer_id ?: null,
        $location,
        $meter_amount,
        $price_per_meter,
        $pump_car_id !== '' ? $pump_car_id : null,
        $pump_driver_id !== '' ? $pump_driver_id : null,
        $mixer_car_id !== '' ? $mixer_car_id : null,
        $mixer_driver_id !== '' ? $mixer_driver_id : null,
        $receiver_name,
        $notes,
        $payment_type,
        $paid_usd,
        $paid_iqd,
        $exchange_rate,
        $current_timestamp
    ]);
    
    $inserted_id = $pdo->lastInsertId();

    echo json_encode(['success' => true, 'message' => 'پسوڵەی خزمەتگوزاری بەسەرکەوتوویی زیادکرا!', 'id' => $inserted_id]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
