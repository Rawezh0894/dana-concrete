<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('update_role_permissions.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for role permissions update');
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to update role permissions (not admin)');
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $role = $_POST['role'] ?? '';
    $permission_id = intval($_POST['permission_id'] ?? 0);
    $value = intval($_POST['value'] ?? 0);

    // Log parsed variables for debugging
    error_log("Parsed vars: role='$role', permission_id='$permission_id', value='$value'");

    // Validate required fields
    if (empty($role)) {
        error_log('Role is empty');
        echo json_encode(['success' => false, 'message' => 'دەسەڵات پێویستە!']);
        exit;
    }

    if (!$permission_id) {
        error_log('Permission ID is invalid: ' . $permission_id);
        echo json_encode(['success' => false, 'message' => 'ناسنامەی دەسەڵات پێویستە!']);
        exit;
    }

    // Check if role exists in database instead of hardcoded list
    $rolesStmt = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE name = ?');
    $rolesStmt->execute([$role]);
    if ($rolesStmt->fetchColumn() == 0) {
        error_log('Invalid role provided: ' . $role);
        echo json_encode(['success' => false, 'message' => 'دەسەڵاتی نادروست!']);
        exit;
    }

    if ($value) {
        // Add permission
        $stmt = $pdo->prepare('INSERT IGNORE INTO role_permissions (role, permission_id) VALUES (?, ?)');
        $ok = $stmt->execute([$role, $permission_id]);
        
        if ($ok) {
            error_log('Permission added successfully: Role=' . $role . ', Permission ID=' . $permission_id);
            echo json_encode(['success' => true, 'message' => 'دەسەڵات زیادکرا!']);
        } else {
            error_log('Failed to add permission: Role=' . $role . ', Permission ID=' . $permission_id);
            echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی دەسەڵات!']);
        }
    } else {
        // Remove permission
        $stmt = $pdo->prepare('DELETE FROM role_permissions WHERE role = ? AND permission_id = ?');
        $ok = $stmt->execute([$role, $permission_id]);
        
        if ($ok) {
            error_log('Permission removed successfully: Role=' . $role . ', Permission ID=' . $permission_id);
            echo json_encode(['success' => true, 'message' => 'دەسەڵات لابرا!']);
        } else {
            error_log('Failed to remove permission: Role=' . $role . ', Permission ID=' . $permission_id);
            echo json_encode(['success' => false, 'message' => 'هەڵە لە لابردنی دەسەڵات!']);
        }
    }

} catch (PDOException $e) {
    error_log('PDOException in update_role_permissions.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی دەسەڵات!']);
} catch (Exception $e) {
    error_log('Exception in update_role_permissions.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی دەسەڵات!']);
} 