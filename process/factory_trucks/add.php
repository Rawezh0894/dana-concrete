<?php
// c:\xampp\htdocs\dana-concrete\process\factory_trucks\add.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $truck_name = trim($_POST['truck_name'] ?? '');
    $plate_number = trim($_POST['plate_number'] ?? '');
    $driver_name = trim($_POST['driver_name'] ?? '');

    if (empty($truck_name)) {
        echo json_encode(['success' => false, 'msg' => 'ناوی بارهەڵگر پێویستە']);
        exit;
    }

    try {
        // Prevent duplicate truck names
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM factory_trucks WHERE truck_name = ?");
        $check_stmt->execute([$truck_name]);
        if ($check_stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'msg' => 'ئەم ناوەی بارهەڵگرە پێشتر تۆمارکراوە']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO factory_trucks (truck_name, plate_number, driver_name, is_active) VALUES (?, ?, ?, 1)");
        if ($stmt->execute([$truck_name, $plate_number, $driver_name])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'هەڵە لە پاشەکەوتکردن']);
        }
    } catch (PDOException $e) {
        error_log('Truck Add Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'msg' => 'هەڵەیەکی داتابەیس ڕوویدا: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەنەدراوە']);
}
