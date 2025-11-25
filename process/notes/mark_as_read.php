<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'نەدەتوانیت دەست بگەیت']);
    exit;
}

// Check if user has permission to mark notes as read
if (!hasPermission('mark_notes_read')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'توانای دەست گەیشتنت نییە']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['note_id']) || empty($input['note_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ناسنامەی تێبینی پێویستە']);
    exit;
}

$note_id = intval($input['note_id']);

try {
    // Fetch note date to validate schedule
    $stmt = $pdo->prepare("SELECT date FROM notes WHERE id = ?");
    $stmt->execute([$note_id]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$note) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'تێبینیەکە نەدۆزرایەوە']);
        exit;
    }

    $noteDate = DateTime::createFromFormat('Y-m-d', $note['date'] ?? '');
    $today = new DateTime('today');

    if ($noteDate && $noteDate > $today) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'ناتوانیت ئەم تێبینەیە خوێندن بکەیت پێش بەرواری نیشانکراو.']);
        exit;
    }

    // Update the note to mark as read
    $stmt = $pdo->prepare("UPDATE notes SET is_read = 1, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$note_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'تێبینیەکە وەک خوێندراو نیشانەکرا']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'هەڵەیەک لە نوێکردنەوەی داتا هەیە']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in mark_as_read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'هەڵەیەک لە داتابەیس هەیە: ' . $e->getMessage()]);
}
?> 