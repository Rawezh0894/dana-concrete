<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('edit_employee')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ دەستکاری کارمەند']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}
$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$role = trim($_POST['role'] ?? '');
$salary = trim($_POST['salary'] ?? '');
if ($id <= 0 || $name === '' || $mobile === '' || $role === '' || $salary === '') {
    echo json_encode(['success' => false, 'message' => 'هەموو خانەکان پڕبکە']);
    exit;
}
$stmt = $pdo->prepare('UPDATE employees SET name=?, mobile=?, role=?, salary=? WHERE id=?');
if ($stmt->execute([$name, $mobile, $role, $salary, $id])) {
    echo json_encode(['success' => true, 'message' => 'نوێکرایەوە']);
} else {
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە']);
}
