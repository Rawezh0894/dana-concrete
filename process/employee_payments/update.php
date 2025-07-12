<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('edit_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ دەستکاری پارەدان']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}
$id = intval($_POST['id'] ?? 0);
$employee_id = intval($_POST['employee_id'] ?? 0);
$salary = floatval($_POST['salary'] ?? 0);
$karwanhisabi = floatval($_POST['karwanhisabi'] ?? 0);
$bonus = floatval($_POST['bonus'] ?? 0);
$pay_month = trim($_POST['pay_month'] ?? '');
$total = $salary + $karwanhisabi + $bonus;
if ($id <= 0 || $employee_id <= 0 || $salary === 0 || $karwanhisabi === 0 || $pay_month === '') {
    echo json_encode(['success' => false, 'message' => 'هەموو خانەکان پڕبکە']);
    exit;
}
try {
    $stmt = $pdo->prepare('UPDATE employee_payments SET employee_id=?, salary=?, karwanhisabi=?, bonus=?, total=?, pay_month=?, updated_at=NOW() WHERE id=?');
    if ($stmt->execute([$employee_id, $salary, $karwanhisabi, $bonus, $total, $pay_month, $id])) {
        echo json_encode(['success' => true, 'message' => 'پارەدان نوێکرایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $e->getMessage()]);
}
