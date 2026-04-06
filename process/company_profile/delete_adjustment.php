<?php
// c:\xampp\htdocs\dana-concrete\process\company_profile\delete_adjustment.php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === 0) {
        echo json_encode(['success' => false, 'msg' => 'Invalid ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM company_adjustments WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Deletion failed']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'Method not allowed']);
}
