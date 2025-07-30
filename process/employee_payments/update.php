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
        // Get employee information for notification
        $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch();
        $employee_name = $employee['name'] ?? 'Unknown';

        // Get old values for notification
        $stmt = $pdo->prepare("SELECT * FROM employee_payments WHERE id = ?");
        $stmt->execute([$id]);
        $old_record = $stmt->fetch();

        $old_values = [
            'employee_id' => $old_record['employee_id'],
            'salary' => $old_record['salary'],
            'karwanhisabi' => $old_record['karwanhisabi'],
            'bonus' => $old_record['bonus'],
            'total' => $old_record['total'],
            'pay_month' => $old_record['pay_month']
        ];

        $new_values = [
            'employee_id' => $employee_id,
            'employee_name' => $employee_name,
            'salary' => $salary,
            'karwanhisabi' => $karwanhisabi,
            'bonus' => $bonus,
            'total' => $total,
            'pay_month' => $pay_month
        ];

        $additional_info = [
            'action_type' => 'employee_payment_update',
            'payment_components' => [
                'salary' => $salary,
                'karwanhisabi' => $karwanhisabi,
                'bonus' => $bonus
            ],
            'total_amount' => $total
        ];

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'update',
            'employee_payments',
            $id,
            "پارەدان بە کارمەند نوێکرایەوە (کارمەند: $employee_name, بڕ: $total دینار, مانگ: $pay_month)",
            $old_values,
            $new_values,
            $additional_info,
            getUserIP()
        );

        echo json_encode(['success' => true, 'message' => 'پارەدان نوێکرایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $e->getMessage()]);
}
