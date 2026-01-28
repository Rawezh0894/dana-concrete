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

// Check permission
if (!hasPermission('delete_service_receipts')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$id = $_POST['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID پێویستە']);
    exit;
}

try {
    // First check if the receipt exists
    $checkStmt = $pdo->prepare('SELECT * FROM service_receipts WHERE id = ?');
    $checkStmt->execute([$id]);
    $receipt = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receipt) {
        echo json_encode(['success' => false, 'message' => 'پسوڵە نەدۆزرایەوە!']);
        exit;
    }
    
    // Get related information for notification
    $customer_name = 'هیچ کڕیارێک نییە';
    $pump_car_name = 'هیچ سەیارەیەک نییە';
    $pump_driver_name = 'هیچ شۆفێرێک نییە';
    $mixer_car_name = 'هیچ سەیارەیەک نییە';
    $mixer_driver_name = 'هیچ شۆفێرێک نییە';

    if ($receipt['customer_id']) {
        $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
        $stmt->execute([$receipt['customer_id']]);
        $customer = $stmt->fetch();
        $customer_name = $customer['name'] ?? 'Unknown';
    }

    if ($receipt['pump_car_id']) {
        $stmt = $pdo->prepare("SELECT name FROM cars WHERE id = ?");
        $stmt->execute([$receipt['pump_car_id']]);
        $pump_car = $stmt->fetch();
        $pump_car_name = $pump_car['name'] ?? 'Unknown';
    }

    if ($receipt['pump_driver_id']) {
        $stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = ?");
        $stmt->execute([$receipt['pump_driver_id']]);
        $pump_driver = $stmt->fetch();
        $pump_driver_name = $pump_driver['name'] ?? 'Unknown';
    }

    if ($receipt['mixer_car_id']) {
        $stmt = $pdo->prepare("SELECT name FROM cars WHERE id = ?");
        $stmt->execute([$receipt['mixer_car_id']]);
        $mixer_car = $stmt->fetch();
        $mixer_car_name = $mixer_car['name'] ?? 'Unknown';
    }

    if ($receipt['mixer_driver_id']) {
        $stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = ?");
        $stmt->execute([$receipt['mixer_driver_id']]);
        $mixer_driver = $stmt->fetch();
        $mixer_driver_name = $mixer_driver['name'] ?? 'Unknown';
    }

    // Create old values for notification
    $old_values = [
        'receipt_number' => $receipt['receipt_number'],
        'customer_id' => $receipt['customer_id'],
        'customer_name' => $customer_name,
        'location' => $receipt['location'],
        'meter_amount' => $receipt['meter_amount'],
        'price_per_meter' => $receipt['price_per_meter'],
        'pump_car_id' => $receipt['pump_car_id'],
        'pump_car_name' => $pump_car_name,
        'pump_driver_id' => $receipt['pump_driver_id'],
        'pump_driver_name' => $pump_driver_name,
        'mixer_car_id' => $receipt['mixer_car_id'],
        'mixer_car_name' => $mixer_car_name,
        'mixer_driver_id' => $receipt['mixer_driver_id'],
        'mixer_driver_name' => $mixer_driver_name,
        'receiver_name' => $receipt['receiver_name'],
        'notes' => $receipt['notes']
    ];

    $additional_info = [
        'action_type' => 'service_receipt_deletion',
        'receipt_type' => 'service_revenue',
        'amount_m3' => $receipt['meter_amount'],
        'customer' => $customer_name
    ];

    $stmt = $pdo->prepare('DELETE FROM service_receipts WHERE id = ?');
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'delete',
            'service_receipts',
            $id,
            "پسوڵەی خزمەتگوزاری سڕایەوە (شماره: {$receipt['receipt_number']}, کڕیار: $customer_name, بڕ: {$receipt['meter_amount']} م³)",
            $old_values,
            null,
            $additional_info,
            getUserIP()
        );

        echo json_encode(['success' => true, 'message' => 'پسوڵە سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'پسوڵە نەدۆزرایەوە!']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
