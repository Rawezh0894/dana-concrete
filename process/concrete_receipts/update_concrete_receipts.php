<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

// Check if user has permission to edit concrete receipts
if (!hasPermission('edit_concrete_receipts')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$id = $_POST['id'] ?? null;
$receipt_number = $_POST['receipt_number'] ?? null;
$customer_id = $_POST['customer_id'] ?? null;
$location = $_POST['location'] ?? null;
$meter_amount = $_POST['meter_amount'] ?? null;
$formulas_id = $_POST['formulas_id'] ?? null;
$pump_car_id = $_POST['pump_car_id'] ?? null;
$pump_driver_id = $_POST['pump_driver_id'] ?? null;
$mixer_car_id = $_POST['mixer_car_id'] ?? null;
$mixer_driver_id = $_POST['mixer_driver_id'] ?? null;
$receiver_name = $_POST['edit_receiver_name'] ?? null;
if (!$id || !$receipt_number || !$location || !$meter_amount || !$formulas_id) {
    echo json_encode(['success' => false, 'message' => 'هەموو خانە پڕ بکە']);
    exit;
}
try {
    $stmt = $pdo->prepare('UPDATE concrete_receipts SET receipt_number=?, customer_id=?, location=?, meter_amount=?, formulas_id=?, pump_car_id=?, pump_driver_id=?, mixer_car_id=?, mixer_driver_id=?, receiver_name=? WHERE id=?');
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
        $receiver_name !== '' ? $receiver_name : null,
        $id
    ]);
    require_once __DIR__ . '/../../includes/notify.php';
    notify('update', 'concrete_receipts', $id, 'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: ' . $receipt_number . ')');
    echo json_encode(['success' => true, 'message' => 'پسوڵە نوێکرایەوە']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
