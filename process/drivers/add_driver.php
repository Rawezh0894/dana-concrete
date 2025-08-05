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

$name = trim($_POST['name'] ?? '');
$load_capacity = !empty($_POST['load_capacity']) ? floatval($_POST['load_capacity']) : null;

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'ناوی شۆفێر پێویستە']);
    exit;
}

try {
    // Check if driver name already exists
    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ناوی شۆفێر هەیە']);
        exit;
    }

    // Insert new driver
    $stmt = $pdo->prepare("INSERT INTO drivers (name, load_capacity) VALUES (?, ?)");
    $stmt->execute([$name, $load_capacity]);
    
    echo json_encode(['success' => true, 'message' => 'شۆفێر بە سەرکەوتوویی زیاد کرا']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 