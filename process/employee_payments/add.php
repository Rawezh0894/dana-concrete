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
    // Get employee information for notification
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    $employee_name = $employee['name'] ?? 'Unknown';

    $stmt = $pdo->prepare('INSERT INTO employee_payments (employee_id, salary, karwanhisabi, bonus, total, pay_month, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
    if ($stmt->execute([$employee_id, $salary, $karwanhisabi, $bonus, $total, $pay_month])) {
        $payment_id = $pdo->lastInsertId();

        // Create detailed notification
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
            'action_type' => 'employee_payment_creation',
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
            'insert',
            'employee_payments',
            $payment_id,
            "پارەدان بە کارمەند زیادکرا (کارمەند: $employee_name, بڕ: $total دینار, مانگ: $pay_month)",
            null, // No old values for insert
            $new_values,
            $additional_info,
            getUserIP()
        );

        echo json_encode(['success' => true, 'message' => 'پارەدان زیادکرا']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $e->getMessage()]);
}
