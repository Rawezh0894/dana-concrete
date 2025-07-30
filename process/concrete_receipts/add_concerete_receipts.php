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

    // Get related information for notification
    $customer_name = 'هیچ کڕیارێک نییە';
    $formula_name = 'هیچ فۆرمۆلایەک نییە';
    $pump_car_name = 'هیچ سەیارەیەک نییە';
    $pump_driver_name = 'هیچ شۆفێرێک نییە';
    $mixer_car_name = 'هیچ سەیارەیەک نییە';
    $mixer_driver_name = 'هیچ شۆفێرێک نییە';

    if ($customer_id) {
        $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch();
        $customer_name = $customer['name'] ?? 'Unknown';
    }

    if ($formulas_id) {
        $stmt = $pdo->prepare("SELECT name FROM concrete_formulas WHERE id = ?");
        $stmt->execute([$formulas_id]);
        $formula = $stmt->fetch();
        $formula_name = $formula['name'] ?? 'Unknown';
    }

    if ($pump_car_id) {
        $stmt = $pdo->prepare("SELECT name FROM cars WHERE id = ?");
        $stmt->execute([$pump_car_id]);
        $pump_car = $stmt->fetch();
        $pump_car_name = $pump_car['name'] ?? 'Unknown';
    }

    if ($pump_driver_id) {
        $stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = ?");
        $stmt->execute([$pump_driver_id]);
        $pump_driver = $stmt->fetch();
        $pump_driver_name = $pump_driver['name'] ?? 'Unknown';
    }

    if ($mixer_car_id) {
        $stmt = $pdo->prepare("SELECT name FROM cars WHERE id = ?");
        $stmt->execute([$mixer_car_id]);
        $mixer_car = $stmt->fetch();
        $mixer_car_name = $mixer_car['name'] ?? 'Unknown';
    }

    if ($mixer_driver_id) {
        $stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = ?");
        $stmt->execute([$mixer_driver_id]);
        $mixer_driver = $stmt->fetch();
        $mixer_driver_name = $mixer_driver['name'] ?? 'Unknown';
    }

    // Create detailed notification
    $new_values = [
        'receipt_number' => $receipt_number,
        'customer_id' => $customer_id,
        'customer_name' => $customer_name,
        'location' => $location,
        'meter_amount' => $meter_amount,
        'formulas_id' => $formulas_id,
        'formula_name' => $formula_name,
        'pump_car_id' => $pump_car_id,
        'pump_car_name' => $pump_car_name,
        'pump_driver_id' => $pump_driver_id,
        'pump_driver_name' => $pump_driver_name,
        'mixer_car_id' => $mixer_car_id,
        'mixer_car_name' => $mixer_car_name,
        'mixer_driver_id' => $mixer_driver_id,
        'mixer_driver_name' => $mixer_driver_name,
        'receiver_name' => $receiver_name
    ];

    $additional_info = [
        'action_type' => 'concrete_receipt_creation',
        'receipt_type' => 'concrete_delivery',
        'amount_m3' => $meter_amount,
        'delivery_components' => [
            'pump_car' => $pump_car_name,
            'mixer_car' => $mixer_car_name
        ]
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'insert',
        'concrete_receipts',
        $inserted_id,
        "پسوڵەی کۆنکرێت زیادکرا (شماره: $receipt_number, کڕیار: $customer_name, فۆرمۆلا: $formula_name, بڕ: $meter_amount م³)",
        null, // No old values for insert
        $new_values,
        $additional_info,
        getUserIP()
    );

    echo json_encode(['success' => true, 'message' => 'پسوڵە بەسەرکەوتوویی زیادکرا!', 'id' => $inserted_id]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
