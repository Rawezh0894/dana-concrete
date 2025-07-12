<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('view_employee')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ بینینی لیستی کارمەندەکان']);
    exit;
}
$stmt = $pdo->query('SELECT id, name, mobile, role, salary FROM employees ORDER BY id DESC');
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($employees);
