<?php
// c:\xampp\htdocs\dana-concrete\process\factory_trucks\update.php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $truck_name = trim($_POST['truck_name'] ?? '');
    $plate_number = trim($_POST['plate_number'] ?? '');
    $driver_name = trim($_POST['driver_name'] ?? '');
    $commission_per_trip = (float)($_POST['commission_per_trip'] ?? 20000);
    $is_active = (int)($_POST['is_active'] ?? 1);

    if (empty($truck_name) || $id === 0) {
        echo json_encode(['success' => false, 'msg' => 'زانیارییەکان تەواو نین']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE factory_trucks SET truck_name = ?, plate_number = ?, driver_name = ?, commission_per_trip = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$truck_name, $plate_number, $driver_name, $commission_per_trip, $is_active, $id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'نوێکردنەوە سەرکەوتوو نەبوو']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => 'هەڵە: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'Invalid Request']);
}
