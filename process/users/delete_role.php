<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $role = $_POST['role'] ?? '';

    if (empty($role)) {
        echo json_encode(['success' => false, 'message' => 'ناوی دەسەڵات دیاری نەکراوە!']);
        exit;
    }

    // Prevent deleting core roles
    $core_roles = ['admin', 'user', 'accountant', 'manager'];
    if (in_array($role, $core_roles)) {
        echo json_encode(['success' => false, 'message' => 'ناتوانیت دەسەڵاتی سەرەکی سیستم بسڕیتەوە!']);
        exit;
    }

    // Check if any users are using this role
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
    $stmt->execute([$role]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ناتوانیت ئەم دەسەڵاتە بسڕیتەوە چونکە بەکارهێنەر هەیە ئەم دەسەڵاتەی پێدراوە!']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // 1. Delete from role_permissions
    $stmt = $pdo->prepare('DELETE FROM role_permissions WHERE role = ?');
    $stmt->execute([$role]);

    // 2. Delete from roles table
    $stmt = $pdo->prepare('DELETE FROM roles WHERE name = ?');
    $stmt->execute([$role]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'دەسەڵاتەکە بەسەرکەوتوویی سڕایەوە!']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException in delete_role.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک لە بنکەی زانیاری ڕوویدا: ' . $e->getMessage()]);
}
