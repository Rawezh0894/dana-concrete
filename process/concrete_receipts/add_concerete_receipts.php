<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

// Check if user has permission to add concrete receipts
if (!hasPermission('add_concrete_receipts')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}
header('Content-Type: application/json');
try {
    $receipt_number = $_POST['receipt_number'] ?? null;
    $customer_id = $_POST['customer_id'] ?? null;
    $location = $_POST['location'] ?? null;
    $meter_amount = $_POST['meter_amount'] ?? null;
    $formulas_id = $_POST['formulas_id'] ?? null;
    $pump_car_id = $_POST['pump_car_id'] ?? null;
    $pump_driver_id = $_POST['pump_driver_id'] ?? null;
    $mixer_car_id = $_POST['mixer_car_id'] ?? null;
    $mixer_driver_id = $_POST['mixer_driver_id'] ?? null;
    $receiver_name = $_POST['receiver_name'] ?? null;
    if (!$receipt_number || !$location || !$meter_amount || !$formulas_id) {
        echo json_encode(['success' => false, 'message' => 'هەموو خانە پڕ بکە']);
        exit;
    }
    
    // Check for duplicate receipt number
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM concrete_receipts WHERE receipt_number = ?");
    $check_stmt->execute([$receipt_number]);
    $duplicate_count = $check_stmt->fetchColumn();
    
    if ($duplicate_count > 0) {
        echo json_encode(['success' => false, 'message' => 'ژمارەی پسوڵە دووبارەیە! تکایە ژمارەیەکی دیکە هەڵبژێرە']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO concrete_receipts (receipt_number, customer_id, location, meter_amount, formulas_id, pump_car_id, pump_driver_id, mixer_car_id, mixer_driver_id , receiver_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ? , ?)");
    $stmt->execute([
        $receipt_number,
        $customer_id ?: null,
        $location,
        $meter_amount,
        $formulas_id,
        $pump_car_id !== '' ? $pump_car_id : null,
        $pump_driver_id !== '' ? $pump_driver_id : null,
        $mixer_car_id ?: null,
        $mixer_driver_id ?: null,
        $receiver_name

    ]);
    $inserted_id = $pdo->lastInsertId();

    echo json_encode(['success' => true, 'message' => 'پسوڵە بەسەرکەوتوویی زیادکرا!', 'id' => $inserted_id]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
