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
    // Fetch all roles from dynamic roles table
    $rolesStmt = $pdo->query('SELECT name FROM roles ORDER BY id ASC');
    $roles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // If no roles found in table, use default set to ensure system doesn't break
    if (empty($roles)) {
        $roles = ['admin', 'user', 'accountant', 'manager'];
    }

    $perms = $pdo->query('SELECT * FROM permissions ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

    foreach ($perms as &$perm) {
        foreach ($roles as $role) {
            $stmt = $pdo->prepare('SELECT 1 FROM role_permissions WHERE role = ? AND permission_id = ?');
            $stmt->execute([$role, $perm['id']]);
            $perm[$role] = $stmt->fetch() ? true : false;
        }
    }
    
    $result = [
        'roles' => $roles,
        'permissions' => $perms
    ];
    
    error_log('Permissions and roles retrieved successfully');
    echo json_encode($result);
    
} catch (PDOException $e) {
    error_log('PDOException in select_permissions.php: ' . $e->getMessage());
    echo json_encode([]);
} catch (Exception $e) {
    error_log('Exception in select_permissions.php: ' . $e->getMessage());
    echo json_encode([]);
} 