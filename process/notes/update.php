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

if (!hasPermission('update_notes')) {
    echo json_encode(['success' => false, 'error' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get form data
$note_id = $_POST['edit_note_id'] ?? '';
$date = $_POST['edit_date'] ?? '';
$time = $_POST['edit_time'] ?? '';
$customer_id = $_POST['edit_customer_id'] ?? '';
$location = $_POST['edit_location'] ?? '';
$recipient = $_POST['edit_recipient'] ?? '';
$meter_amount = $_POST['edit_meter_amount'] ?? '';
$formula_id = $_POST['edit_formula_id'] ?? '';
$mixer_car_id = $_POST['edit_mixer_car_id'] ?? null;
$mixer_driver_id = $_POST['edit_mixer_driver_id'] ?? null;
$pump_car_id = $_POST['edit_pump_car_id'] ?? null;
$pump_driver_id = $_POST['edit_pump_driver_id'] ?? null;

// Validate required fields
if (empty($note_id) || empty($date) || empty($time) || empty($customer_id) || empty($location) || empty($meter_amount) || empty($formula_id)) {
    echo json_encode(['success' => false, 'error' => 'تکایە هەموو خانە پێویستەکان پڕبکەرەوە']);
    exit;
}

// Validate numeric fields
if (!is_numeric($meter_amount) || $meter_amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'بڕی مەتر دەبێت ژمارەیەکی دروست بێت']);
    exit;
}

try {
    // Get old values for logging
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
    $stmt->execute([$note_id]);
    $old_note = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$old_note) {
        echo json_encode(['success' => false, 'error' => 'تێبینی نەدۆزرایەوە']);
        exit;
    }
    
    // Update note
    $sql = "UPDATE notes SET 
            date = ?, time = ?, customer_id = ?, location = ?, recipient = ?, 
            meter_amount = ?, formula_id = ?, mixer_car_id = ?, mixer_driver_id = ?, 
            pump_car_id = ?, pump_driver_id = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?";
    
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
        $pump_driver_id,
        $note_id
    ]);
    
    // Log the activity
    if (function_exists('log_user_activity')) {
        log_user_activity(
            $_SESSION['user_id'],
            $_SESSION['username'],
            'update',
            'notes',
            'نوێکردنەوەی تێبینی',
            $note_id,
            'notes',
            json_encode($old_note),
            json_encode([
                'date' => $date,
                'time' => $time,
                'customer_id' => $customer_id,
                'location' => $location,
                'recipient' => $recipient,
                'meter_amount' => $meter_amount,
                'formula_id' => $formula_id,
                'mixer_car_id' => $mixer_car_id,
                'mixer_driver_id' => $mixer_driver_id,
                'pump_car_id' => $pump_car_id,
                'pump_driver_id' => $pump_driver_id
            ]),
            $_SERVER['REMOTE_ADDR'] ?? ''
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تێبینی بەسەرکەوتوویی نوێکرایەوە'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()
    ]);
}
