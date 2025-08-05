<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها POST method ڕێگەپێدراوە']);
    exit;
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID نەدروستە']);
    exit;
}

try {
    // Check if driver exists
    $stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = ?");
    $stmt->execute([$id]);
    $driver = $stmt->fetch();
    if (!$driver) {
        echo json_encode(['success' => false, 'message' => 'شۆفێر نەدۆزرایەوە']);
        exit;
    }

    // Check if driver is used in purchases
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE driver = ?");
    $stmt->execute([$driver['name']]);
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'ناتوانرێت شۆفێر بسڕدرێتەوە چونکە لە کڕینەکاندا بەکارهاتووە']);
        exit;
    }

    // Delete driver
    $stmt = $pdo->prepare("DELETE FROM drivers WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true, 'message' => 'شۆفێر بە سەرکەوتوویی سڕایەوە']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 