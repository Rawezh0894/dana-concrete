<?php
session_start();
require_once '../../config/db_conected.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_name = $_POST['car_name'] ?? null;
    if ($car_name) {
        // Prevent duplicate car names
        $check = $pdo->prepare("SELECT COUNT(*) FROM cars WHERE name = ?");
        $check->execute([$car_name]);
        if ($check->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'msg' => 'ئەم ناوە پێشتر تۆمارکراوە']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO cars (name) VALUES (?)");
        $ok = $stmt->execute([$car_name]);
        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردن']);
        }
    } else {
        echo json_encode(['success' => false, 'msg' => 'خانەی ناو پڕ بکە']);
    }
}
