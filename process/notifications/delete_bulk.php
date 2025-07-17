<?php
session_start();
require_once '../../config/db_conected.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'error' => 'دەسەڵات نییە']);
    exit;
}
if (!isset($_POST['ids']) || !is_array($_POST['ids']) || count($_POST['ids']) == 0) {
    echo json_encode(['success' => false, 'error' => 'هیچ تۆمارێک هەڵبژێردراو نییە']);
    exit;
}
$ids = array_map('intval', $_POST['ids']);
$in = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("DELETE FROM notifications WHERE id IN ($in)");
if ($stmt->execute($ids)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'هەڵە لە سڕینەوە']);
} 