<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('update_concrete_receipts.php POST: ' . print_r($_POST, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for concrete receipt update');
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

// Check if user has permission to edit concrete receipts
if (!hasPermission('edit_concrete_receipts')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to edit concrete receipts');
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

// Log parsed variables for debugging
error_log("Parsed vars: id='$id', receipt_number='$receipt_number', customer_id='$customer_id', location='$location', meter_amount='$meter_amount', formulas_id='$formulas_id', pump_car_id='$pump_car_id', pump_driver_id='$pump_driver_id', mixer_car_id='$mixer_car_id', mixer_driver_id='$mixer_driver_id', receiver_name='$receiver_name'");

if (!$id || !$receipt_number || !$location || !$meter_amount || !$formulas_id) {
    error_log('Missing required fields for concrete receipt update');
    echo json_encode(['success' => false, 'message' => 'هەموو خانە پڕ بکە']);
    exit;
}

try {
    // Check if receipt exists
    $checkStmt = $pdo->prepare('SELECT id, receipt_number FROM concrete_receipts WHERE id = ?');
    $checkStmt->execute([$id]);
    $receipt = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receipt) {
        error_log('Concrete receipt not found for update: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'پسوڵە نەدۆزرایەوە!']);
        exit;
    }
    
    error_log('Found concrete receipt for update: ' . print_r($receipt, true));
    
    // Check for duplicate receipt_number (excluding current record)
    $dupCheck = $pdo->prepare('SELECT COUNT(*) FROM concrete_receipts WHERE receipt_number = ? AND id != ?');
    $dupCheck->execute([$receipt_number, $id]);
    if ($dupCheck->fetchColumn() > 0) {
        error_log('Duplicate receipt number: ' . $receipt_number);
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی پسوڵە پێشتر تۆمارکراوە!']);
        exit;
    }
    
    $stmt = $pdo->prepare('UPDATE concrete_receipts SET receipt_number=?, customer_id=?, location=?, meter_amount=?, formulas_id=?, pump_car_id=?, pump_driver_id=?, mixer_car_id=?, mixer_driver_id=?, receiver_name=? WHERE id=?');
    $result = $stmt->execute([
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
    
    if ($result && $stmt->rowCount() > 0) {
        require_once __DIR__ . '/../../includes/notify.php';
        notify('update', 'concrete_receipts', $id, 'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: ' . $receipt_number . ')');
        error_log('Concrete receipt successfully updated: ID=' . $id . ', Receipt Number=' . $receipt_number);
        echo json_encode(['success' => true, 'message' => 'پسوڵە نوێکرایەوە']);
    } else {
        error_log('No rows affected when updating concrete receipt: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'هیچ گۆڕانکارییەک نەکرا!']);
    }
} catch (PDOException $e) {
    error_log('PDOException in update_concrete_receipts.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in update_concrete_receipts.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
