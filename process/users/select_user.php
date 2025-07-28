<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for users retrieval');
    echo json_encode([]);
    exit;
}

if (!hasPermission('view_users')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to view users');
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->query('SELECT id, username, role FROM users ORDER BY id ASC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Users retrieved successfully: Count=' . count($users));
    echo json_encode($users);
    
} catch (PDOException $e) {
    error_log('PDOException in select_user.php: ' . $e->getMessage());
    echo json_encode([]);
} catch (Exception $e) {
    error_log('Exception in select_user.php: ' . $e->getMessage());
    echo json_encode([]);
}
