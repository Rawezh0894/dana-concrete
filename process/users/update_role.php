<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $oldName = $_POST['old_name'] ?? '';
    $newName = trim($_POST['new_name'] ?? '');

    if (empty($oldName) || empty($newName)) {
        echo json_encode(['success' => false, 'message' => 'ناوی کۆن و نوێ پێویستە!']);
        exit;
    }

    if ($oldName === $newName) {
        echo json_encode(['success' => true, 'message' => 'هیچ گۆڕانکارییەک نەکراوە.']);
        exit;
    }

    // Prevent updating core roles
    $core_roles = ['admin', 'user', 'accountant', 'manager'];
    if (in_array($oldName, $core_roles)) {
        echo json_encode(['success' => false, 'message' => 'ناتوانیت ناوی دەسەڵاتی سەرەکی سیستم بگۆڕیت!']);
        exit;
    }

    // Check if new name already exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE name = ?');
    $stmt->execute([$newName]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ئەم ناوە پێشتر هەیە!']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // 1. Update roles table
    $stmt = $pdo->prepare('UPDATE roles SET name = ? WHERE name = ?');
    $stmt->execute([$newName, $oldName]);

    // 2. Update users table
    $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE role = ?');
    $stmt->execute([$newName, $oldName]);

    // 3. Update role_permissions table
    $stmt = $pdo->prepare('UPDATE role_permissions SET role = ? WHERE role = ?');
    $stmt->execute([$newName, $oldName]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'دەسەڵاتەکە بەسەرکەوتوویی نوێکرایەوە!']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException in update_role.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی بنکەی زانیاری: ' . $e->getMessage()]);
}
