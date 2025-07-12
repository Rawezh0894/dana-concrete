<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('delete_employee')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ سڕینەوەی کارمەند']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'هەڵەی ID']);
    exit;
}
$stmt = $pdo->prepare('DELETE FROM employees WHERE id=?');
if ($stmt->execute([$id])) {
    echo json_encode(['success' => true, 'message' => 'سڕایەوە']);
} else {
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە']);
}
