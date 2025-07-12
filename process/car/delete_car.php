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
    if ($car_id) {
        $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
        $ok = $stmt->execute([$car_id]);
        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە']);
        }
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵەی ID']);
    }
}
