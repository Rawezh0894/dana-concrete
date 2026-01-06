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
$salary = (float)($_POST['salary'] ?? 0);
$karwanhisabi_raw = (string)($_POST['karwanhisabi'] ?? '0');
$karwanhisabi = (float)str_replace(',', '', preg_replace('/[^\d\.\-]/', '', $karwanhisabi_raw));
$bonus = (float)($_POST['bonus'] ?? 0);
$penalty = (float)($_POST['penalty'] ?? 0);
$pay_month = trim($_POST['pay_month'] ?? '');
$date = trim($_POST['transaction_date'] ?? '');
$note = trim($_POST['note'] ?? '');

$payroll_total = $salary + $karwanhisabi + $bonus;

if ($employee_id <= 0 || $pay_month === '') {
    echo json_encode(['success' => false, 'message' => 'هەموو خانەکان پڕبکە']);
    exit;
}
if ($payroll_total <= 0) {
    echo json_encode(['success' => false, 'message' => 'کۆی مووچە/کاروانحیسابی/بەخشیش دەبێت لە 0 زیاتر بێت']);
    exit;
}

if ($date === '') {
    $date = date('Y-m-d');
}
// Normalize to datetime
$transaction_date = $date . ' ' . date('H:i:s');
try {
    ensureEmployeeLedgerSchema($pdo);
    // Get employee information for notification
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    $employee_name = $employee['name'] ?? 'Unknown';

    $pdo->beginTransaction();

    // 1) Payroll accrual (credit)
    $stmt = $pdo->prepare("
        INSERT INTO employee_transactions
        (employee_id, type, amount, operation, pay_month, transaction_date, description)
        VALUES (?, 'payroll', ?, 'credit', ?, ?, ?)
    ");
    $desc = trim("مووچە: $salary | کاروانحیسابی: $karwanhisabi | بەخشیش: $bonus" . ($note ? " | تێبینی: $note" : ""));
    $stmt->execute([$employee_id, $payroll_total, $pay_month, $transaction_date, $desc]);
    $payroll_txn_id = (int)$pdo->lastInsertId();

    // 2) Optional penalty (debit) as separate line item
    $penalty_txn_id = null;
    if ($penalty > 0) {
        $stmt2 = $pdo->prepare("
            INSERT INTO employee_transactions
            (employee_id, type, amount, operation, pay_month, transaction_date, description)
            VALUES (?, 'penalty', ?, 'debit', ?, ?, ?)
        ");
        $pdesc = trim("سزا: $penalty" . ($note ? " | تێبینی: $note" : ""));
        $stmt2->execute([$employee_id, $penalty, $pay_month, $transaction_date, $pdesc]);
        $penalty_txn_id = (int)$pdo->lastInsertId();
    }

    $pdo->commit();

        // Create detailed notification
        $new_values = [
            'employee_id' => $employee_id,
            'employee_name' => $employee_name,
            'salary' => $salary,
            'karwanhisabi' => $karwanhisabi,
            'bonus' => $bonus,
            'penalty' => $penalty,
            'pay_month' => $pay_month,
            'payroll_total' => $payroll_total,
            'transaction_date' => $transaction_date,
            'note' => $note
        ];

        $additional_info = [
            'action_type' => 'employee_payroll_post',
            'ledger' => [
                'payroll_transaction_id' => $payroll_txn_id,
                'penalty_transaction_id' => $penalty_txn_id
            ]
        ];

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'insert',
            'employee_transactions',
            $payroll_txn_id,
            "مووچە/حسابی کارمەند تۆمارکرا (کارمەند: $employee_name, کۆی مووچە: $payroll_total, سزا: $penalty, مانگ: $pay_month)",
            null, // No old values for insert
            $new_values,
            $additional_info,
            getUserIP()
        );

        echo json_encode(['success' => true, 'message' => 'مووچە/حساب تۆمارکرا']);
} catch (PDOException $e) {
    error_log('PDOException in employee_payments/add.php: ' . $e->getMessage());
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'هەڵە لە تۆمارکردنی مووچە/حساب!']);
}
