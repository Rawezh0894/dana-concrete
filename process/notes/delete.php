<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!hasPermission('delete_notes')) {
    echo json_encode(['success' => false, 'error' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get note ID
$note_id = $_POST['id'] ?? '';

if (empty($note_id)) {
    echo json_encode(['success' => false, 'error' => 'ناسنامەی تێبینی پێویستە']);
    exit;
}

try {
    // Get note data for logging
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
    $stmt->execute([$note_id]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$note) {
        echo json_encode(['success' => false, 'error' => 'تێبینی نەدۆزرایەوە']);
        exit;
    }
    
    // Delete the note
    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
    $stmt->execute([$note_id]);
    
    // Log the activity
    if (function_exists('log_user_activity')) {
        log_user_activity(
            $_SESSION['user_id'],
            $_SESSION['username'],
            'delete',
            'notes',
            'سڕینەوەی تێبینی',
            $note_id,
            'notes',
            json_encode($note),
            null,
            $_SERVER['REMOTE_ADDR'] ?? ''
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تێبینی بەسەرکەوتوویی سڕایەوە'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()
    ]);
}
