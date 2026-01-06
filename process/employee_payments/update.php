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
$type = trim($_POST['type'] ?? '');
$operation = trim($_POST['operation'] ?? '');
$amount = (float)str_replace(',', '', preg_replace('/[^\d\.\-]/', '', (string)($_POST['amount'] ?? 0)));
$pay_month = trim($_POST['pay_month'] ?? '');
$transaction_date = trim($_POST['transaction_date'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($id <= 0 || $employee_id <= 0 || $type === '' || !in_array($operation, ['credit','debit'], true) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'هەموو خانەکان پڕبکە']);
    exit;
}
if ($transaction_date === '') {
    $transaction_date = date('Y-m-d H:i:s');
}
try {
    ensureEmployeeLedgerSchema($pdo);
    // Get old values BEFORE updating
    $stmt = $pdo->prepare("SELECT * FROM employee_transactions WHERE id = ?");
    $stmt->execute([$id]);
    $old_record = $stmt->fetch();
    if (!$old_record) {
        echo json_encode(['success' => false, 'message' => 'مامەڵە نەدۆزرایەوە']);
        exit;
    }

    // Get old employee information
    $old_employee_name = 'Unknown';
    if ($old_record['employee_id']) {
        $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
        $stmt->execute([$old_record['employee_id']]);
        $old_employee = $stmt->fetch();
        $old_employee_name = $old_employee['name'] ?? 'Unknown';
    }

    $old_values = [
        'employee_id' => $old_record['employee_id'],
        'employee_name' => $old_employee_name,
        'type' => $old_record['type'],
        'operation' => $old_record['operation'],
        'amount' => $old_record['amount'],
        'pay_month' => $old_record['pay_month'],
        'transaction_date' => $old_record['transaction_date'],
        'description' => $old_record['description']
    ];

    // Now perform the update
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE employee_transactions SET employee_id=?, type=?, amount=?, operation=?, pay_month=?, transaction_date=?, description=? WHERE id=?');
    if ($stmt->execute([$employee_id, $type, $amount, $operation, ($pay_month ?: null), $transaction_date, ($description ?: null), $id])) {
        // Get employee information for notification
        $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch();
        $employee_name = $employee['name'] ?? 'Unknown';

        $new_values = [
            'employee_id' => $employee_id,
            'employee_name' => $employee_name,
            'type' => $type,
            'operation' => $operation,
            'amount' => $amount,
            'pay_month' => $pay_month,
            'transaction_date' => $transaction_date,
            'description' => $description
        ];

        $additional_info = [
            'action_type' => 'employee_transaction_update'
        ];

        // Sync cash_box row for payment/advance edits (by reference tag)
        $pdo->prepare("DELETE FROM cash_box WHERE note LIKE ?")
            ->execute(['%[REF:EMP_TXN#' . $id . '#IQD]%']);
        if (in_array($type, ['payment','advance'], true) && $operation === 'debit') {
            $ref = "[REF:EMP_TXN#{$id}#IQD]";
            $dateOnly = substr($transaction_date, 0, 10);
            $cashNote = trim("پارەدان بە کارمەند | ناو: {$employee_name} | جۆر: {$type} | بڕ: {$amount} د.ع | {$ref}" . ($description ? " | تێبینی: {$description}" : ""));
            $cashStmt = $pdo->prepare("
                INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
                VALUES (?, 'withdraw', ?, 0, 'دینار', ?, ?)
            ");
            $cashStmt->execute([$dateOnly, $amount, $cashNote, null]);
        }

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'update',
            'employee_transactions',
            $id,
            "مامەڵەی کارمەند نوێکرایەوە (کارمەند: $employee_name, جۆر: $type, بڕ: $amount, مانگ: $pay_month)",
            $old_values,
            $new_values,
            $additional_info,
            getUserIP()
        );

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'پارەدان نوێکرایەوە']);
    } else {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('PDOException in employee_payments/update.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی پارەدان!']);
}
