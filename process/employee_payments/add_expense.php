<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/employee_expense_cash_box_helper.php';
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
$expense_date = trim($_POST['expense_date'] ?? '');
$notes = trim($_POST['notes'] ?? '');

$salary = floatval($_POST['salary'] ?? 0);
$bonus = floatval($_POST['bonus'] ?? 0);
$overtime = floatval($_POST['overtime'] ?? 0);
$advance = floatval($_POST['advance'] ?? 0);
$deduction = floatval($_POST['deduction'] ?? 0);
$penalty = floatval($_POST['penalty'] ?? 0);
$overtime_payment = floatval($_POST['overtime_payment'] ?? 0);

$payment_amount_usd = floatval($_POST['payment_amount_usd'] ?? 0);
$payment_amount_iqd = floatval($_POST['payment_amount_iqd'] ?? 0);
$exchange_rate = floatval($_POST['exchange_rate'] ?? 0);

if ($employee_id <= 0 || $expense_date === '') {
    echo json_encode(['success' => false, 'message' => 'کارمەند و بەروار پێویستە']);
    exit;
}

$total_expenses = $salary + $bonus + $overtime + $advance + $deduction + $penalty + $overtime_payment;
if ($total_expenses <= 0) {
    echo json_encode(['success' => false, 'message' => 'لانیکەم یەک جۆری خەرجی پێویستە']);
    exit;
}

$lines = [];
if ($salary > 0) {
    $lines[] = ['type' => 'salary', 'amount' => $salary];
}
if ($bonus > 0) {
    $lines[] = ['type' => 'bonus', 'amount' => $bonus];
}
if ($overtime > 0) {
    $lines[] = ['type' => 'overtime', 'amount' => $overtime];
}
if ($advance > 0) {
    $lines[] = ['type' => 'advance', 'amount' => $advance];
}
if ($deduction > 0) {
    $lines[] = ['type' => 'deduction', 'amount' => $deduction];
}
if ($penalty > 0) {
    $lines[] = ['type' => 'penalty', 'amount' => $penalty];
}
if ($overtime_payment > 0) {
    $lines[] = ['type' => 'overtime_payment', 'amount' => $overtime_payment];
}

try {
    $splitLines = employee_expense_split_payment_amounts(
        $lines,
        $payment_amount_usd,
        $payment_amount_iqd,
        $exchange_rate
    );
} catch (RuntimeException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT name FROM employees WHERE id = ?');
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    $employee_name = $employee['name'] ?? 'Unknown';

    $expense_ids = [];

    $stmtInsert = $pdo->prepare(
        'INSERT INTO employee_expenses (employee_id, expense_type, amount, amount_usd, amount_iqd, exchange_rate, notes, created_by, expense_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($splitLines as $i => $sl) {
        $stmtInsert->execute([
            $employee_id,
            $sl['type'],
            $sl['amount'],
            $sl['amount_usd'],
            $sl['amount_iqd'],
            $sl['exchange_rate'],
            $notes,
            $_SESSION['user_id'],
            $expense_date,
        ]);
        $newId = (int) $pdo->lastInsertId();
        $expense_ids[] = ['type' => $sl['type'], 'id' => $newId, 'amount' => $sl['amount']];

        $rowForCash = [
            'id' => $newId,
            'employee_id' => $employee_id,
            'expense_type' => $sl['type'],
            'amount' => $sl['amount'],
            'amount_usd' => $sl['amount_usd'],
            'amount_iqd' => $sl['amount_iqd'],
            'exchange_rate' => $sl['exchange_rate'],
            'expense_date' => $expense_date,
            'notes' => $notes,
            'created_by' => (int) $_SESSION['user_id'],
        ];
        employee_expense_sync_cash_box($pdo, $rowForCash, $employee_name);
    }

    $expense_types_kurdish = [
        'salary' => 'مووچە',
        'bonus' => 'بەخشیش',
        'overtime' => 'کاروانحیسابی',
        'advance' => 'پێشەکی',
        'deduction' => 'کەمکردنەوە',
        'penalty' => 'سزا',
        'overtime_payment' => 'پێدانی کاروانحیسابی',
    ];

    $expense_details = [];
    foreach ($expense_ids as $exp) {
        $detail = $expense_types_kurdish[$exp['type']] . ': ' . $exp['amount'] . ' د.ع';
        if ($exp['type'] === 'advance') {
            $detail .= ' (لە مووچە دەکەم)';
        }
        $expense_details[] = $detail;
    }

    $notification_message = "خەرجی کارمەند زیادکرا (کارمەند: $employee_name, مانگ: $expense_date)\n" . implode(', ', $expense_details);

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'insert',
        'employee_expenses',
        $expense_ids[0]['id'] ?? 0,
        $notification_message,
        null,
        [
            'employee_id' => $employee_id,
            'employee_name' => $employee_name,
            'expense_date' => $expense_date,
            'expenses' => $expense_ids,
        ],
        ['action_type' => 'employee_expense_creation'],
        getUserIP()
    );

    $stmt = $pdo->prepare('SELECT COALESCE(payable_balance, 0) as payable_balance, COALESCE(receivable_balance, 0) as receivable_balance FROM employees WHERE id = ?');
    $stmt->execute([$employee_id]);
    $updated_balances = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'خەرجی کارمەند بە سەرکەوتوویی زیادکرا',
        'expense_ids' => $expense_ids,
        'updated_balances' => [
            'payable' => floatval($updated_balances['payable_balance'] ?? 0),
            'receivable' => floatval($updated_balances['receivable_balance'] ?? 0),
        ],
    ]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException in employee_payments/add_expense.php: ' . $e->getMessage());
    $msg = $e->getMessage();
    if (strpos($msg, 'Unknown column') !== false && strpos($msg, 'amount_usd') !== false) {
        $msg .= ' — تکایە فایلی database/migrations/employee_expenses_multicurrency_cashbox.sql جێبەجێ بکە.';
    }
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی خەرجی کارمەند: ' . $msg]);
}
