<?php
session_start();
require_once '../../config/db_conected.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$id = $_POST['id'] ?? 0;
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'No ID']);
    exit;
}
$stmt = $pdo->prepare('UPDATE notifications SET seen=1 WHERE id=?');
$stmt->execute([$id]);
echo json_encode(['success' => true]); 