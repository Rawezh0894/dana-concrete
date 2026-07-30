<?php
session_start();
require_once '../../config/db_conected.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $stmt = $pdo->prepare('INSERT INTO locations (name) VALUES (?)');
        $ok = $stmt->execute([$name]);
        if ($ok) {
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'name' => $name]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردن']);
        }
    } else {
        echo json_encode(['success' => false, 'msg' => 'ناوی سەرچاوە پێویستە']);
    }
}
