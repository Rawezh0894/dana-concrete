<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'POST only']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = trim($_POST['name'] ?? '');

if ($id <= 0 || $name === '') {
    echo json_encode(['success' => false, 'msg' => 'زانیاری ناتەواوە']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE locations SET name = ? WHERE id = ?');
    $ok = $stmt->execute([$name, $id]);

    if ($ok && $stmt->rowCount() >= 0) {
        echo json_encode(['success' => true, 'id' => $id, 'name' => $name]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس']);
}

