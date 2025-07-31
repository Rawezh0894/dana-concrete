<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if user has permission to view notes
if (!hasPermission('view_notes')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

try {
    // Get count of unread notes
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notes WHERE is_read = 0");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $unreadCount = (int)$result['count'];
    
    echo json_encode([
        'success' => true,
        'unread_count' => $unreadCount
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?> 