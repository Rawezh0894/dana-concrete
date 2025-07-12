<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

// Check if user has permission to delete concrete receipts
if (!hasPermission('delete_concrete_receipts')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$id = $_POST['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID پێویستە']);
    exit;
}
try {
    $stmt = $pdo->prepare('DELETE FROM concrete_receipts WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'پسوڵە سڕایەوە']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
