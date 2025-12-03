<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('update_user.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for user update');
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('edit_user')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to edit user');
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', username='$username', role='$role', has_password='" . (!empty($password) ? 'yes' : 'no') . "'");

    // Validate required fields
    if (!$id) {
        error_log('No user ID provided for update');
        echo json_encode(['success' => false, 'message' => 'ناسنامەی بەکارهێنەر پێویستە!']);
        exit;
    }

    if (empty($username)) {
        error_log('Username is empty');
        echo json_encode(['success' => false, 'message' => 'ناوی بەکارهێنەر پێویستە!']);
        exit;
    }

    if (!in_array($role, ['admin', 'user', 'accountant', 'manager'])) {
        error_log('Invalid role provided: ' . $role);
        echo json_encode(['success' => false, 'message' => 'دەسەڵاتی نادروست!']);
        exit;
    }

    // Check if user is trying to change their own role
    if ($id == $_SESSION['user_id'] && $role !== $_SESSION['role']) {
        error_log('User trying to change their own role: User=' . $_SESSION['user_id'] . ', Current Role=' . $_SESSION['role'] . ', New Role=' . $role);
        echo json_encode(['success' => false, 'message' => 'ناتوانیت دەسەڵاتی خۆت بگۆڕیت!']);
        exit;
    }

    // Check if user exists
    $checkStmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
    $checkStmt->execute([$id]);
    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingUser) {
        error_log('User not found: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'بەکارهێنەر نەدۆزرایەوە!']);
        exit;
    }

    // Check for duplicate username (except self)
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
    $stmt->execute([$username, $id]);
    if ($stmt->fetch()) {
        error_log('Duplicate username found: ' . $username . ' (excluding user ID: ' . $id . ')');
        echo json_encode(['success' => false, 'message' => 'ئەم ناوی بەکارهێنەر پێشتر تۆمارکراوە!']);
        exit;
    }

    // Update user
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?');
        $ok = $stmt->execute([$username, $hash, $role, $id]);
        error_log('User updated with password: ID=' . $id . ', Username=' . $username . ', Role=' . $role);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET username = ?, role = ? WHERE id = ?');
        $ok = $stmt->execute([$username, $role, $id]);
        error_log('User updated without password: ID=' . $id . ', Username=' . $username . ', Role=' . $role);
    }

    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'زانیاری بەکارهێنەر نوێکرایەوە!']);
    } else {
        error_log('Failed to update user: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in update_user.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی بەکارهێنەر!']);
} catch (Exception $e) {
    error_log('Exception in update_user.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی بەکارهێنەر!']);
}
