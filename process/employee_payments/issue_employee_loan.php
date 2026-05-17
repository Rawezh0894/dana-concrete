<?php

declare(strict_types=1);

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/employee_loan_helper.php';
require_once __DIR__ . '/../cash_box/cash_box_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('add_payment') && !hasPermission('add_cash_box')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$employee_id = (int) ($_POST['employee_id'] ?? 0);
$loan_usd = round((float) ($_POST['loan_usd'] ?? 0), 2);
$loan_iqd = round((float) ($_POST['loan_iqd'] ?? 0), 2);
$loan_date = trim((string) ($_POST['loan_date'] ?? ''));
$notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : '';

if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'کارمەند هەلبژێرە']);
    exit;
}
if ($loan_usd <= 0 && $loan_iqd <= 0) {
    echo json_encode(['success' => false, 'message' => 'لانیکەم یەک بڕ (دۆلار یان دینار) پێویستە']);
    exit;
}
if ($loan_date === '') {
    $loan_date = date('Y-m-d');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $loan_date)) {
    echo json_encode(['success' => false, 'message' => 'بەرواری نادروست']);
    exit;
}

cash_box_ensure_no_withdraw_balance_block($pdo);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT id, name FROM employees WHERE id = ?');
    $stmt->execute([$employee_id]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$emp) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'کارمەند نەدۆزرایەوە']);
        exit;
    }
    $name = (string) $emp['name'];

    $ins = $pdo->prepare(
        'INSERT INTO employee_loans (employee_id, loan_usd, loan_iqd, remaining_usd, remaining_iqd, loan_date, status, notes, created_by)
         VALUES (?, ?, ?, ?, ?, ?, \'active\', ?, ?)'
    );
    $ins->execute([
        $employee_id,
        $loan_usd,
        $loan_iqd,
        $loan_usd,
        $loan_iqd,
        $loan_date,
        $notes !== '' ? $notes : null,
        (int) $_SESSION['user_id'],
    ]);
    $loanId = (int) $pdo->lastInsertId();

    employee_loan_insert_cash_withdrawals(
        $pdo,
        $loanId,
        $name,
        $loan_date,
        $loan_usd,
        $loan_iqd,
        (int) $_SESSION['user_id']
    );

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'قەرز تۆمارکرا و لە قاسە دەرکرا',
        'loan_id' => $loanId,
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
    $msg = $e->getMessage();
    if (strpos($msg, 'employee_loans') !== false || strpos($msg, 'Unknown table') !== false) {
        $msg .= ' — تکایە database/migrations/employee_loans.sql جێبەجێ بکە.';
    }
    if (strpos($msg, 'Duplicate column') !== false) {
        $msg = 'ستوونی employee_loan_id لە cash_box هەیە — تەنها خشتەکانی قەرز دروست بکە.';
    }
    error_log('issue_employee_loan: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $msg]);
}
