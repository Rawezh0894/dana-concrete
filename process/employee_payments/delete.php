<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('delete_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ سڕینەوەی پارەدان']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'هەڵەی ID']);
    exit;
}
try {
    // Get record before delete
    $stmt = $pdo->prepare('SELECT * FROM employee_payments WHERE id = ?');
    $stmt->execute([$id]);
    $record = $stmt->fetch();

    if (!$record) {
        echo json_encode(['success' => false, 'message' => 'پارەدان نەدۆزرایەوە']);
        exit;
    }

    // Get employee information for notification
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
    $stmt->execute([$record['employee_id']]);
    $employee = $stmt->fetch();
    $employee_name = $employee['name'] ?? 'Unknown';

    // Create old values for notification
    $old_values = [
        'employee_id' => $record['employee_id'],
        'employee_name' => $employee_name,
        'salary' => $record['salary'],
        'karwanhisabi' => $record['karwanhisabi'],
        'bonus' => $record['bonus'],
        'total' => $record['total'],
        'pay_month' => $record['pay_month']
    ];

    $additional_info = [
        'action_type' => 'employee_payment_deletion',
        'payment_components' => [
            'salary' => $record['salary'],
            'karwanhisabi' => $record['karwanhisabi'],
            'bonus' => $record['bonus']
        ],
        'total_amount' => $record['total']
    ];

    $stmt = $pdo->prepare('DELETE FROM employee_payments WHERE id=?');
    if ($stmt->execute([$id])) {
        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'delete',
            'employee_payments',
            $id,
            "پارەدان بە کارمەند سڕایەوە (کارمەند: $employee_name, بڕ: {$record['total']} دینار, مانگ: {$record['pay_month']})",
            $old_values,
            null, // No new values for delete
            $additional_info,
            getUserIP()
        );

        echo json_encode(['success' => true, 'message' => 'پارەدان سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $e->getMessage()]);
}
