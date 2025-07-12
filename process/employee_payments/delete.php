<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('delete_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ سڕینەوەی پارەدان']);
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
try {
    $stmt = $pdo->prepare('DELETE FROM employee_payments WHERE id=?');
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'پارەدان سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $e->getMessage()]);
}
