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
// Get filter parameters
$month_filter = $_GET['month'] ?? '';
$employee_filter = $_GET['employee'] ?? '';

// Build WHERE conditions
$where_conditions = [];
$params = [];

if ($month_filter) {
    $where_conditions[] = "p.pay_month = ?";
    $params[] = $month_filter;
}

if ($employee_filter) {
    $where_conditions[] = "p.employee_id = ?";
    $params[] = $employee_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT p.id, p.employee_id, e.name as employee_name, p.salary, p.karwanhisabi, p.bonus, p.total, p.pay_month, p.created_at 
        FROM employee_payments p 
        JOIN employees e ON p.employee_id = e.id 
        $where_clause 
        ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
