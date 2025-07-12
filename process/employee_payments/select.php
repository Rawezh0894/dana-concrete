<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('view_employee_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ بینینی پارەدان بە کارمەند']);
    exit;
}
$sql = 'SELECT p.id, p.employee_id, e.name as employee_name, p.salary, p.karwanhisabi, p.bonus, p.total, p.pay_month, p.created_at FROM employee_payments p JOIN employees e ON p.employee_id = e.id ORDER BY p.id DESC';
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows);
