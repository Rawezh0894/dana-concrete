<?php
// Helper function to check if the current user has a specific permission
function hasPermission($perm) {
    global $pdo;
    if (!isset($_SESSION['role'])) {
        if (function_exists('redirectToLogin')) {
            redirectToLogin();
        }
        return false;
    }
    $role = $_SESSION['role'];
    // Always allow admin
    if ($role === 'admin') return true;
    
    // Special handling for location permissions
    if (in_array($perm, ['view_location', 'add_location', 'edit_location', 'delete_location'])) {
        // For now, allow all logged in users to manage locations
        // You can modify this logic based on your requirements
        return isset($_SESSION['user_id']);
    }
    
    // Debug output
    error_log('DEBUG: role=' . $role . ', perm=' . $perm);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role = ? AND p.name = ?');
    $stmt->execute([$role, $perm]);
    $result = $stmt->fetchColumn();
    error_log('DEBUG: SQL result=' . $result);
    return $result > 0;
} 