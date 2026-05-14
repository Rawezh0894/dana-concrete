<?php

declare(strict_types=1);

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/employee_loan_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if (!hasPermission('add_payment') && !hasPermission('add_cash_box')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدراوە']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$loan_id = (int) ($_POST['loan_id'] ?? 0);
$repay_usd = round((float) ($_POST['repay_usd'] ?? 0), 2);
$repay_iqd = round((float) ($_POST['repay_iqd'] ?? 0), 2);
$repayment_date = trim((string) ($_POST['repayment_date'] ?? ''));

if ($loan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'قەرز هەلبژێرە']);
    exit;
}
if ($repay_usd <= 0 && $repay_iqd <= 0) {
    echo json_encode(['success' => false, 'message' => 'لانیکەم یەک بڕ (دۆلار یان دینار) پێویستە']);
    exit;
}
if ($repayment_date === '') {
    $repayment_date = date('Y-m-d');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $repayment_date)) {
    echo json_encode(['success' => false, 'message' => 'بەرواری نادروست']);
    exit;
}

try {
    $pdo->beginTransaction();
    employee_loan_apply_direct_repayment(
        $pdo,
        $loan_id,
        $repay_usd,
        $repay_iqd,
        $repayment_date,
        (int) $_SESSION['user_id']
    );
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'گەڕاندنەوەی قەرز تۆمارکرا و لە قاسە وەک پارە هاتوو تۆمارکرا',
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
    if (strpos($msg, '1048') !== false || stripos($msg, 'expense_id') !== false) {
        $msg .= ' — تکایە database/migrations/loan_repayments_nullable_expense_id.sql جێبەجێ بکە.';
    }
    error_log('direct_loan_repayment: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $msg]);
}
