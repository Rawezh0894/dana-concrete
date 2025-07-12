<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!isset($_SESSION['user_id']) || !hasPermission('add_user')) {
    echo json_encode(['success' => false, 'message' => 'دەسەڵات نییە!']);
    exit;
}

header('Content-Type: application/json');

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'user';

if (!$username || !$password || !in_array($role, ['admin', 'user', 'accountant', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'هەموو خانەکان پڕبکەوە!']);
    exit;
}

// Check for duplicate username
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'ئەم ناوی بەکارهێنەر پێشتر تۆمارکراوە!']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
$ok = $stmt->execute([$username, $hash, $role]);
if ($ok) {
    echo json_encode(['success' => true, 'message' => 'بەکارهێنەر زیادکرا!']);
} else {
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن!']);
}
