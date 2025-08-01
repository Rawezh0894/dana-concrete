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

$notification_ids = [];
$note_ids = [];

// Separate notifications and notes
foreach ($_POST['ids'] as $id) {
    if (strpos($id, 'note_') === 0) {
        $note_ids[] = substr($id, 5); // Remove 'note_' prefix
    } else {
        $notification_ids[] = intval($id);
    }
}

try {
    $pdo->beginTransaction();
    
    // Delete notifications
    if (!empty($notification_ids)) {
        $in = implode(',', array_fill(0, count($notification_ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id IN ($in)");
        $stmt->execute($notification_ids);
    }
    
    // Delete notes
    if (!empty($note_ids)) {
        $in = implode(',', array_fill(0, count($note_ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id IN ($in)");
        $stmt->execute($note_ids);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'هەڵە لە سڕینەوە: ' . $e->getMessage()]);
} 