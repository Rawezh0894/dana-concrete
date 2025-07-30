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
    
    // Get old values BEFORE updating
    $stmt = $pdo->prepare("SELECT * FROM concrete_receipts WHERE id = ?");
    $stmt->execute([$id]);
    $old_record = $stmt->fetch();

    // Get old related information
    $old_customer_name = 'هیچ کڕیارێک نییە';
    $old_formula_name = 'هیچ فۆرمۆلایەک نییە';
    $old_pump_car_name = 'هیچ سەیارەیەک نییە';
    $old_pump_driver_name = 'هیچ شۆفێرێک نییە';
    $old_mixer_car_name = 'هیچ سەیارەیەک نییە';
    $old_mixer_driver_name = 'هیچ شۆفێرێک نییە';

    if ($old_record['customer_id']) {
        $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
        $stmt->execute([$old_record['customer_id']]);
        $old_customer = $stmt->fetch();
        $old_customer_name = $old_customer['name'] ?? 'Unknown';
    }

    if ($old_record['formulas_id']) {
        $stmt = $pdo->prepare("SELECT name FROM concrete_formulas WHERE id = ?");
        $stmt->execute([$old_record['formulas_id']]);
        $old_formula = $stmt->fetch();
        $old_formula_name = $old_formula['name'] ?? 'Unknown';
    }

    if ($old_record['pump_car_id']) {
        $stmt = $pdo->prepare("SELECT name FROM cars WHERE id = ?");
        $stmt->execute([$old_record['pump_car_id']]);
        $old_pump_car = $stmt->fetch();
        $old_pump_car_name = $old_pump_car['name'] ?? 'Unknown';
    }

    if ($old_record['pump_driver_id']) {
        $stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = ?");
        $stmt->execute([$old_record['pump_driver_id']]);
        $old_pump_driver = $stmt->fetch();
        $old_pump_driver_name = $old_pump_driver['name'] ?? 'Unknown';
    }

    if ($old_record['mixer_car_id']) {
        $stmt = $pdo->prepare("SELECT name FROM cars WHERE id = ?");
        $stmt->execute([$old_record['mixer_car_id']]);
        $old_mixer_car = $stmt->fetch();
        $old_mixer_car_name = $old_mixer_car['name'] ?? 'Unknown';
    }

    if ($old_record['mixer_driver_id']) {
        $stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = ?");
        $stmt->execute([$old_record['mixer_driver_id']]);
        $old_mixer_driver = $stmt->fetch();
        $old_mixer_driver_name = $old_mixer_driver['name'] ?? 'Unknown';
    }

    $old_values = [
        'receipt_number' => $old_record['receipt_number'],
        'customer_id' => $old_record['customer_id'],
        'customer_name' => $old_customer_name,
        'location' => $old_record['location'],
        'meter_amount' => $old_record['meter_amount'],
        'formulas_id' => $old_record['formulas_id'],
        'formula_name' => $old_formula_name,
        'pump_car_id' => $old_record['pump_car_id'],
        'pump_car_name' => $old_pump_car_name,
        'pump_driver_id' => $old_record['pump_driver_id'],
        'pump_driver_name' => $old_pump_driver_name,
        'mixer_car_id' => $old_record['mixer_car_id'],
        'mixer_car_name' => $old_mixer_car_name,
        'mixer_driver_id' => $old_record['mixer_driver_id'],
        'mixer_driver_name' => $old_mixer_driver_name,
        'receiver_name' => $old_record['receiver_name']
    ];

    // Now perform the update
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
            'action_type' => 'concrete_receipt_update',
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
            'update',
            'concrete_receipts',
            $id,
            "پسوڵەی کۆنکرێت نوێکرایەوە (شماره: $receipt_number, کڕیار: $customer_name, فۆرمۆلا: $formula_name, بڕ: $meter_amount م³)",
            $old_values,
            $new_values,
            $additional_info,
            getUserIP()
        );

        error_log('Concrete receipt successfully updated: ID=' . $id . ', Receipt Number=' . $receipt_number . ', Customer=' . $customer_name);
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
