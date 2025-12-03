<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('delete_user.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for user deletion');
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('delete_user')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to delete user');
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    
    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id'");

    if (!$id) {
        error_log('No user ID provided for deletion');
        echo json_encode(['success' => false, 'message' => 'ناسنامەی بەکارهێنەر پێویستە!']);
        exit;
    }

    if ($id === intval($_SESSION['user_id'])) {
        error_log('User trying to delete themselves: User=' . $_SESSION['user_id']);
        echo json_encode(['success' => false, 'message' => 'ناتوانیت خۆت بسڕیتەوە!']);
        exit;
    }

    // Check if user exists
    $checkStmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
    $checkStmt->execute([$id]);
    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingUser) {
        error_log('User not found for deletion: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'بەکارهێنەر نەدۆزرایەوە!']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $ok = $stmt->execute([$id]);
    
    if ($ok) {
        error_log('User successfully deleted: ID=' . $id . ', Username=' . $existingUser['username']);
        echo json_encode(['success' => true, 'message' => 'بەکارهێنەر سڕایەوە!']);
    } else {
        error_log('Failed to delete user: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in delete_user.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی بەکارهێنەر!']);
} catch (Exception $e) {
    error_log('Exception in delete_user.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی بەکارهێنەر!']);
}
