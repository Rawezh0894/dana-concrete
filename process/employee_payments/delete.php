<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once '../../config/employee_ledger_schema.php';
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
    ensureEmployeeLedgerSchema($pdo);

    // Get record before delete
    $stmt = $pdo->prepare('SELECT * FROM employee_transactions WHERE id = ?');
    $stmt->execute([$id]);
    $record = $stmt->fetch();

    if (!$record) {
        echo json_encode(['success' => false, 'message' => 'مامەڵە نەدۆزرایەوە']);
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
        'type' => $record['type'],
        'operation' => $record['operation'],
        'amount' => $record['amount'],
        'pay_month' => $record['pay_month'],
        'transaction_date' => $record['transaction_date'],
        'description' => $record['description']
    ];

    $additional_info = [
        'action_type' => 'employee_transaction_deletion'
    ];

    $pdo->beginTransaction();

    // Remove linked cash_box row if exists
    $pdo->prepare("DELETE FROM cash_box WHERE note LIKE ?")
        ->execute(['%[REF:EMP_TXN#' . $id . '#IQD]%']);

    $stmt = $pdo->prepare('DELETE FROM employee_transactions WHERE id=?');
    if ($stmt->execute([$id])) {
        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'delete',
            'employee_transactions',
            $id,
            "مامەڵەی کارمەند سڕایەوە (کارمەند: $employee_name, جۆر: {$record['type']}, بڕ: {$record['amount']}, مانگ: {$record['pay_month']})",
            $old_values,
            null, // No new values for delete
            $additional_info,
            getUserIP()
        );

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'پارەدان سڕایەوە']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('PDOException in employee_payments/delete.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی پارەدان!']);
}
