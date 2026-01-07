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
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ سڕینەوەی خەرجی']);
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
    $stmt = $pdo->prepare('SELECT ee.*, e.name as employee_name FROM employee_expenses ee LEFT JOIN employees e ON ee.employee_id = e.id WHERE ee.id = ?');
    $stmt->execute([$id]);
    $record = $stmt->fetch();

    if (!$record) {
        echo json_encode(['success' => false, 'message' => 'خەرجی نەدۆزرایەوە']);
        exit;
    }

    $expense_types_kurdish = [
        'salary' => 'مووچە',
        'bonus' => 'بەخشیش',
        'overtime' => 'کاروانحیسابی',
        'advance' => 'پێشەکی',
        'deduction' => 'کەمکردنەوە',
        'penalty' => 'سزا'
    ];

    // Create old values for notification
    $old_values = [
        'employee_id' => $record['employee_id'],
        'employee_name' => $record['employee_name'] ?? 'Unknown',
        'expense_type' => $record['expense_type'],
        'expense_type_kurdish' => $expense_types_kurdish[$record['expense_type']] ?? $record['expense_type'],
        'amount' => $record['amount'],
        'expense_date' => $record['expense_date'],
        'notes' => $record['notes']
    ];

    $additional_info = [
        'action_type' => 'employee_expense_deletion',
        'expense_type' => $record['expense_type'],
        'amount' => $record['amount']
    ];

    $stmt = $pdo->prepare('DELETE FROM employee_expenses WHERE id=?');
    if ($stmt->execute([$id])) {
        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'delete',
            'employee_expenses',
            $id,
            "خەرجی کارمەند سڕایەوە (کارمەند: {$record['employee_name']}, جۆر: {$old_values['expense_type_kurdish']}, بڕ: {$record['amount']} دینار)",
            $old_values,
            null,
            $additional_info,
            getUserIP()
        );

        echo json_encode(['success' => true, 'message' => 'خەرجی سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە']);
    }
} catch (PDOException $e) {
    error_log('PDOException in employee_payments/delete_expense.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی خەرجی: ' . $e->getMessage()]);
}

