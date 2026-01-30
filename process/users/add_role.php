<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $name = $_POST['name'] ?? '';

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'ناوی دەسەڵات پێویستە!']);
        exit;
    }

    // Check if role already exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE name = ?');
    $stmt->execute([$name]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ئەم دەسەڵاتە پێشتر هەیە!']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO roles (name) VALUES (?)');
    if ($stmt->execute([$name])) {
        echo json_encode(['success' => true, 'message' => 'دەسەڵات بەسەرکەوتوویی زیادکرا!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا لە کاتی زیادکردن!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in add_role.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی بنکەی زانیاری: ' . $e->getMessage()]);
}
