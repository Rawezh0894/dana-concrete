<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !hasPermission('delete_sale')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare('DELETE FROM recycle_bin_sales WHERE id = ?');
    $ok = $stmt->execute([$id]);
    if ($ok && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە']);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
} 