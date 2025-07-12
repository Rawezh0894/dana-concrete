<?php
session_start();
require_once '../../config/db_conected.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = $_POST['car_id'] ?? null;
    $car_name = $_POST['car_name'] ?? null;
    if ($car_id && $car_name) {
        $stmt = $pdo->prepare("UPDATE cars SET name = ? WHERE id = ?");
        $ok = $stmt->execute([$car_name, $car_id]);
        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە']);
        }
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان پڕ بکە']);
    }
}
