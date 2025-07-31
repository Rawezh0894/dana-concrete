<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!hasPermission('add_notes')) {
    echo json_encode(['success' => false, 'error' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get form data
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$customer_id = $_POST['customer_id'] ?? '';
$location = $_POST['location'] ?? '';
$recipient = $_POST['recipient'] ?? '';
$meter_amount = $_POST['meter_amount'] ?? '';
$formula_id = $_POST['formula_id'] ?? '';
$mixer_car_id = $_POST['mixer_car_id'] ?? null;
$mixer_driver_id = $_POST['mixer_driver_id'] ?? null;
$pump_car_id = $_POST['pump_car_id'] ?? null;
$pump_driver_id = $_POST['pump_driver_id'] ?? null;

// Convert 'null' strings to actual null values
if ($mixer_car_id === 'null' || $mixer_car_id === '') $mixer_car_id = null;
if ($mixer_driver_id === 'null' || $mixer_driver_id === '') $mixer_driver_id = null;
if ($pump_car_id === 'null' || $pump_car_id === '') $pump_car_id = null;
if ($pump_driver_id === 'null' || $pump_driver_id === '') $pump_driver_id = null;

// Validate required fields
if (empty($date) || empty($time) || empty($customer_id) || empty($location) || empty($meter_amount) || empty($formula_id)) {
    echo json_encode(['success' => false, 'error' => 'تکایە هەموو خانە پێویستەکان پڕبکەرەوە']);
    exit;
}

// Validate numeric fields
if (!is_numeric($meter_amount) || $meter_amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'بڕی مەتر دەبێت ژمارەیەکی دروست بێت']);
    exit;
}

try {
    // Insert new note
    $sql = "INSERT INTO notes (date, time, customer_id, location, recipient, meter_amount, formula_id, mixer_car_id, mixer_driver_id, pump_car_id, pump_driver_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $date,
        $time,
        $customer_id,
        $location,
        $recipient,
        $meter_amount,
        $formula_id,
        $mixer_car_id,
        $mixer_driver_id,
        $pump_car_id,
        $pump_driver_id
    ]);
    
    $note_id = $pdo->lastInsertId();
    
    // Log the activity
    if (function_exists('log_user_activity')) {
        log_user_activity(
            $_SESSION['user_id'],
            $_SESSION['username'],
            'create',
            'notes',
            'زیادکردنی تێبینی نوێ',
            $note_id,
            'notes',
            null,
            json_encode([
                'date' => $date,
                'time' => $time,
                'customer_id' => $customer_id,
                'location' => $location,
                'meter_amount' => $meter_amount,
                'formula_id' => $formula_id
            ]),
            $_SERVER['REMOTE_ADDR'] ?? ''
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تێبینی بەسەرکەوتوویی زیادکرا',
        'note_id' => $note_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()
    ]);
}
