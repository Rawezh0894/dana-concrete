<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('add_employee')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ زیادکردنی کارمەند']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$role = trim($_POST['role'] ?? '');
$salary = trim($_POST['salary'] ?? '');

if ($name === '' || $mobile === '' || $role === '' || $salary === '') {
    echo json_encode(['success' => false, 'message' => 'هەموو خانەکان پڕبکە']);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO employees (name, mobile, role, salary) VALUES (?, ?, ?, ?)');
if ($stmt->execute([$name, $mobile, $role, $salary])) {
    echo json_encode(['success' => true, 'message' => 'کارمەند زیادکرا']);
} else {
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن']);
}
