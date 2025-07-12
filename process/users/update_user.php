<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!isset($_SESSION['user_id']) || !hasPermission('edit_user')) {
    echo json_encode(['success' => false, 'message' => 'دەسەڵات نییە!']);
    exit;
}

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'user';

if (!$id || !$username || !in_array($role, ['admin', 'user', 'accountant', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'هەموو خانەکان پڕبکەوە!']);
    exit;
}
if ($id == $_SESSION['user_id'] && $role !== $_SESSION['role']) {
    echo json_encode(['success' => false, 'message' => 'ناتوانیت دەسەڵاتی خۆت بگۆڕیت!']);
    exit;
}
// Check for duplicate username (except self)
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
$stmt->execute([$username, $id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'ئەم ناوی بەکارهێنەر پێشتر تۆمارکراوە!']);
    exit;
}
if ($password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?');
    $ok = $stmt->execute([$username, $hash, $role, $id]);
} else {
    $stmt = $pdo->prepare('UPDATE users SET username = ?, role = ? WHERE id = ?');
    $ok = $stmt->execute([$username, $role, $id]);
}
if ($ok) {
    echo json_encode(['success' => true, 'message' => 'زانیاری بەکارهێنەر نوێکرایەوە!']);
} else {
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە!']);
}
