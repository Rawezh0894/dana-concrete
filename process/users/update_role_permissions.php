<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $role = $_POST['role'] ?? '';
    $permission_id = intval($_POST['permission_id'] ?? 0);
    $value = intval($_POST['value'] ?? 0);

    if (empty($role) || !$permission_id) {
        echo json_encode(['success' => false, 'message' => 'زانیاری ناتەواو!']);
        exit;
    }

    // Check if role exists
    $rolesStmt = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE name = ?');
    $rolesStmt->execute([$role]);
    if ($rolesStmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'دەسەڵاتی نادروست!']);
        exit;
    }

    if ($value) {
        // Add permission using ON DUPLICATE KEY UPDATE to be safe
        $stmt = $pdo->prepare('INSERT INTO role_permissions (role, permission_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE role = role');
        $stmt->execute([$role, $permission_id]);
        echo json_encode(['success' => true, 'message' => 'دەسەڵات درا بەم ڕۆڵە!']);
    } else {
        // Remove permission
        $stmt = $pdo->prepare('DELETE FROM role_permissions WHERE role = ? AND permission_id = ?');
        $stmt->execute([$role, $permission_id]);
        echo json_encode(['success' => true, 'message' => 'دەسەڵات لەم ڕۆڵە لابرا!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in update_role_permissions.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی دەسەڵات!']);
} catch (Exception $e) {
    error_log('Exception in update_role_permissions.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی دەسەڵات!']);
} 