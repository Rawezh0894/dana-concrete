<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    // Get total users count
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    // Get total admins count
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_admins 
        FROM users 
        WHERE role = 'admin'
    ");
    $total_admins = $stmt->fetch(PDO::FETCH_ASSOC)['total_admins'];

    // Get total managers count
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_managers 
        FROM users 
        WHERE role = 'manager'
    ");
    $total_managers = $stmt->fetch(PDO::FETCH_ASSOC)['total_managers'];

    echo json_encode([
        'success' => true,
        'data' => [
            'total_users' => intval($total_users),
            'total_admins' => intval($total_admins),
            'total_managers' => intval($total_managers)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 