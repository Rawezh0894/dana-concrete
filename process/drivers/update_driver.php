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
$name = trim($_POST['name'] ?? '');
$load_capacity = !empty($_POST['load_capacity']) ? floatval($_POST['load_capacity']) : null;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID نەدروستە']);
    exit;
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'ناوی شۆفێر پێویستە']);
    exit;
}

try {
    // Check if driver exists
    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'شۆفێر نەدۆزرایەوە']);
        exit;
    }

    // Check if name already exists for another driver
    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ناوی شۆفێر هەیە']);
        exit;
    }

    // Update driver
    $stmt = $pdo->prepare("UPDATE drivers SET name = ?, load_capacity = ? WHERE id = ?");
    $stmt->execute([$name, $load_capacity, $id]);
    
    echo json_encode(['success' => true, 'message' => 'شۆفێر بە سەرکەوتوویی نوێکرایەوە']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 