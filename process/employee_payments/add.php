<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('add_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ زیادکردنی پارەدان']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}
$employee_id = intval($_POST['employee_id'] ?? 0);
$salary = floatval($_POST['salary'] ?? 0);
$karwanhisabi = floatval($_POST['karwanhisabi'] ?? 0);
$bonus = floatval($_POST['bonus'] ?? 0);
$pay_month = trim($_POST['pay_month'] ?? '');
$total = $salary + $karwanhisabi + $bonus;
if ($employee_id <= 0 || $salary === 0 || $karwanhisabi === 0 || $pay_month === '') {
    echo json_encode(['success' => false, 'message' => 'هەموو خانەکان پڕبکە']);
    exit;
}
try {
    $stmt = $pdo->prepare('INSERT INTO employee_payments (employee_id, salary, karwanhisabi, bonus, total, pay_month, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
    if ($stmt->execute([$employee_id, $salary, $karwanhisabi, $bonus, $total, $pay_month])) {
        echo json_encode(['success' => true, 'message' => 'پارەدان زیادکرا']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $e->getMessage()]);
}
