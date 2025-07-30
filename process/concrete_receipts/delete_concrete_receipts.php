<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('delete_concrete_receipts.php POST: ' . print_r($_POST, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for concrete receipt deletion');
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

// Check if user has permission to delete concrete receipts
if (!hasPermission('delete_concrete_receipts')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to delete concrete receipts');
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$id = $_POST['id'] ?? null;
if (!$id) {
    error_log('No concrete receipt ID provided for deletion');
    echo json_encode(['success' => false, 'message' => 'ID پێویستە']);
    exit;
}

try {
    // First check if the receipt exists
    $checkStmt = $pdo->prepare('SELECT * FROM concrete_receipts WHERE id = ?');
    $checkStmt->execute([$id]);
    $receipt = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receipt) {
        error_log('Concrete receipt not found for ID: ' . $id);
        echo json_encode(['success' => false, 'message' => 'پسوڵە نەدۆزرایەوە!']);
        exit;
    }
    
    error_log('Found concrete receipt for deletion: ' . print_r($receipt, true));
    
    // Get related information for notification
    $customer_name = 'هیچ کڕیارێک نییە';
    $formula_name = 'هیچ فۆرمۆلایەک نییە';
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

    if ($receipt['formulas_id']) {
        $stmt = $pdo->prepare("SELECT name FROM concrete_formulas WHERE id = ?");
        $stmt->execute([$receipt['formulas_id']]);
        $formula = $stmt->fetch();
        $formula_name = $formula['name'] ?? 'Unknown';
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
        'formulas_id' => $receipt['formulas_id'],
        'formula_name' => $formula_name,
        'pump_car_id' => $receipt['pump_car_id'],
        'pump_car_name' => $pump_car_name,
        'pump_driver_id' => $receipt['pump_driver_id'],
        'pump_driver_name' => $pump_driver_name,
        'mixer_car_id' => $receipt['mixer_car_id'],
        'mixer_car_name' => $mixer_car_name,
        'mixer_driver_id' => $receipt['mixer_driver_id'],
        'mixer_driver_name' => $mixer_driver_name,
        'receiver_name' => $receipt['receiver_name']
    ];

    $additional_info = [
        'action_type' => 'concrete_receipt_deletion',
        'receipt_type' => 'concrete_delivery',
        'amount_m3' => $receipt['meter_amount'],
        'delivery_components' => [
            'pump_car' => $pump_car_name,
            'mixer_car' => $mixer_car_name
        ]
    ];

    $stmt = $pdo->prepare('DELETE FROM concrete_receipts WHERE id = ?');
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'delete',
            'concrete_receipts',
            $id,
            "پسوڵەی کۆنکرێت سڕایەوە (شماره: {$receipt['receipt_number']}, کڕیار: $customer_name, فۆرمۆلا: $formula_name, بڕ: {$receipt['meter_amount']} م³)",
            $old_values,
            null, // No new values for delete
            $additional_info,
            getUserIP()
        );

        error_log('Concrete receipt successfully deleted: ID=' . $id . ', Receipt Number=' . $receipt['receipt_number'] . ', Customer=' . $customer_name);
        echo json_encode(['success' => true, 'message' => 'پسوڵە سڕایەوە']);
    } else {
        error_log('No rows affected when deleting concrete receipt: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'پسوڵە نەدۆزرایەوە!']);
    }
} catch (PDOException $e) {
    error_log('PDOException in delete_concrete_receipts.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in delete_concrete_receipts.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
