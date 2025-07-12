<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'دەسەڵات نییە!']);
    exit;
}
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

$role = $_POST['role'] ?? '';
$permission_id = intval($_POST['permission_id'] ?? 0);
$value = intval($_POST['value'] ?? 0);
if (!in_array($role, ['admin', 'user', 'accountant', 'manager']) || !$permission_id) {
    echo json_encode(['success' => false, 'message' => 'داواکاری نادروست!']);
    exit;
}
if ($value) {
    // Add permission
    $stmt = $pdo->prepare('INSERT IGNORE INTO role_permissions (role, permission_id) VALUES (?, ?)');
    $ok = $stmt->execute([$role, $permission_id]);
    echo json_encode(['success' => $ok, 'message' => $ok ? 'دەسەڵات زیادکرا!' : 'هەڵە!']);
} else {
    // Remove permission
    $stmt = $pdo->prepare('DELETE FROM role_permissions WHERE role = ? AND permission_id = ?');
    $ok = $stmt->execute([$role, $permission_id]);
    echo json_encode(['success' => $ok, 'message' => $ok ? 'دەسەڵات لابرا!' : 'هەڵە!']);
} 