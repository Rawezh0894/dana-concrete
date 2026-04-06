<?php
// c:\xampp\htdocs\dana-concrete\process\factory_trucks\delete.php
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
    $id = $_POST['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'msg' => 'ID بارهەڵگر پێویستە']);
        exit;
    }

    try {
        // Check if truck is used in purchases
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE factory_truck_id = ?");
        $check_stmt->execute([$id]);
        if ($check_stmt->fetchColumn() > 0) {
            // Option 1: Prevent deletion
            echo json_encode(['success' => false, 'msg' => 'ئەم بارهەڵگرە لە هەندێک کڕینەکاندا بەکارهێنراوە و ناتوانیت بیسڕیتەوە!']);
            exit;
            
            // Option 2: Just deactivate it instead of deleting
            // $stmt = $pdo->prepare("UPDATE factory_trucks SET is_active = 0 WHERE id = ?");
            // $stmt->execute([$id]);
            // echo json_encode(['success' => true]);
            // exit;
        }

        $stmt = $pdo->prepare("DELETE FROM factory_trucks WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە']);
        }
    } catch (PDOException $e) {
        error_log('Truck Delete Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'msg' => 'هەڵەیەکی داتابەیس ڕوویدا: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەنەدراوە']);
}
