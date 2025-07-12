<?php
// Helper function to check if the current user has a specific permission
function hasPermission($perm) {
    global $pdo;
    if (!isset($_SESSION['role'])) return false;
    $role = $_SESSION['role'];
    // Always allow admin
    if ($role === 'admin') return true;
    // Debug output
    error_log('DEBUG: role=' . $role . ', perm=' . $perm);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role = ? AND p.name = ?');
    $stmt->execute([$role, $perm]);
    $result = $stmt->fetchColumn();
    error_log('DEBUG: SQL result=' . $result);
    return $result > 0;
} 