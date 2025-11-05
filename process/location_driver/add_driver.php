<?php
session_start();
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    
    if ($name === '') {
        echo json_encode(['success' => false, 'msg' => 'ناوی شۆفێر پێویستە']);
        exit;
    }
    
    try {
        // Check if driver name already exists
        $stmt = $pdo->prepare("SELECT id FROM drivers WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'msg' => 'ناوی شۆفێر هەیە']);
            exit;
        }
        
        // Insert new driver
        $stmt = $pdo->prepare('INSERT INTO drivers (name) VALUES (?)');
        $ok = $stmt->execute([$name]);
        
        if ($ok) {
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'name' => $name]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردن']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردن: ' . $e->getMessage()]);
    }
}
