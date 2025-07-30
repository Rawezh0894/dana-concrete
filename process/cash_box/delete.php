<?php
session_start();
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

$id = $_POST['id'] ?? null;
if (!$id || !is_numeric($id)) {
    echo json_encode(['success' => false, 'error' => 'ID نادروستە']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
try {
    $stmt = $pdo->prepare('DELETE FROM cash_box WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
