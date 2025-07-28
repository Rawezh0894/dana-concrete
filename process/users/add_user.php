<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('add_user.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for user addition');
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('add_user')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to add user');
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    // Log parsed variables for debugging
    error_log("Parsed vars: username='$username', role='$role', has_password='" . (!empty($password) ? 'yes' : 'no') . "'");

    // Validate required fields
    if (empty($username)) {
        error_log('Username is empty');
        echo json_encode(['success' => false, 'message' => 'ناوی بەکارهێنەر پێویستە!']);
        exit;
    }

    if (empty($password)) {
        error_log('Password is empty');
        echo json_encode(['success' => false, 'message' => 'وشەی نهێنی پێویستە!']);
        exit;
    }

    if (!in_array($role, ['admin', 'user', 'accountant', 'manager'])) {
        error_log('Invalid role provided: ' . $role);
        echo json_encode(['success' => false, 'message' => 'دەسەڵاتی نادروست!']);
        exit;
    }

    // Check for duplicate username
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        error_log('Duplicate username found: ' . $username);
        echo json_encode(['success' => false, 'message' => 'ئەم ناوی بەکارهێنەر پێشتر تۆمارکراوە!']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
    $ok = $stmt->execute([$username, $hash, $role]);
    
    if ($ok) {
        error_log('User successfully added: Username=' . $username . ', Role=' . $role);
        echo json_encode(['success' => true, 'message' => 'بەکارهێنەر زیادکرا!']);
    } else {
        error_log('Failed to add user: Username=' . $username);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in add_user.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in add_user.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
