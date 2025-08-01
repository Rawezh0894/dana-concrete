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

// Check if it's a note (starts with 'note_')
if (strpos($id, 'note_') === 0) {
    $note_id = substr($id, 5); // Remove 'note_' prefix
    $stmt = $pdo->prepare('UPDATE notes SET is_read=1 WHERE id=?');
    $stmt->execute([$note_id]);
} else {
    // Regular notification
    $stmt = $pdo->prepare('UPDATE notifications SET seen=1 WHERE id=?');
    $stmt->execute([$id]);
}

echo json_encode(['success' => true]); 