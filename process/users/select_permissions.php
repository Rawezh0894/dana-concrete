<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';

// Log session data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for permissions retrieval');
    echo json_encode([]);
    exit;
}

try {
    $roles = ['admin', 'user', 'accountant', 'manager'];
    $perms = $pdo->query('SELECT * FROM permissions ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

    foreach ($perms as &$perm) {
        foreach ($roles as $role) {
            $stmt = $pdo->prepare('SELECT 1 FROM role_permissions WHERE role = ? AND permission_id = ?');
            $stmt->execute([$role, $perm['id']]);
            $perm[$role] = $stmt->fetch() ? true : false;
        }
    }
    
    error_log('Permissions retrieved successfully: Count=' . count($perms));
    echo json_encode($perms);
    
} catch (PDOException $e) {
    error_log('PDOException in select_permissions.php: ' . $e->getMessage());
    echo json_encode([]);
} catch (Exception $e) {
    error_log('Exception in select_permissions.php: ' . $e->getMessage());
    echo json_encode([]);
} 