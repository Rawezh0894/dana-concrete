<?php
session_start();
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

$roles = ['admin', 'user', 'accountant', 'manager'];
$perms = $pdo->query('SELECT * FROM permissions ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

foreach ($perms as &$perm) {
    foreach ($roles as $role) {
        $stmt = $pdo->prepare('SELECT 1 FROM role_permissions WHERE role = ? AND permission_id = ?');
        $stmt->execute([$role, $perm['id']]);
        $perm[$role] = $stmt->fetch() ? true : false;
    }
}
echo json_encode($perms); 