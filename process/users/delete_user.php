<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!isset($_SESSION['user_id']) || !hasPermission('delete_user')) {
    echo json_encode(['success' => false, 'message' => 'دەسەڵات نییە!']);
    exit;
}

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی بەکارهێنەر نادروستە!']);
    exit;
}
if ($id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'ناتوانیت خۆت بسڕیتەوە!']);
    exit;
}
$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$ok = $stmt->execute([$id]);
if ($ok) {
    echo json_encode(['success' => true, 'message' => 'بەکارهێنەر سڕایەوە!']);
} else {
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە!']);
}
