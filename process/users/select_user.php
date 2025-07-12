<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!isset($_SESSION['user_id']) || !hasPermission('view_users')) {
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json');

$stmt = $pdo->query('SELECT id, username, role FROM users ORDER BY id ASC');
$users = $stmt->fetchAll();
echo json_encode($users);
