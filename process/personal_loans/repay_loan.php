<?php

declare(strict_types=1);

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/personal_loan_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !personal_loan_can_manage()) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێنەدراوە']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$loanId = (int) ($_POST['loan_id'] ?? 0);
$receivedUsd = round((float) ($_POST['received_usd'] ?? 0), 2);
$receivedIqd = round((float) ($_POST['received_iqd'] ?? 0), 2);
$changeUsd = round((float) ($_POST['change_back_usd'] ?? 0), 2);
$changeIq = round((float) ($_POST['change_back_iq'] ?? 0), 2);
$dolarRate = round((float) ($_POST['dolar_rate'] ?? 150000), 2);
$repaymentDate = trim((string) ($_POST['repayment_date'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($loanId <= 0) {
    echo json_encode(['success' => false, 'message' => 'قەرز هەلبژێرە']);
    exit;
}
if ($repaymentDate === '') {
    $repaymentDate = date('Y-m-d');
}

try {
    personal_loan_ensure_schema($pdo);
    $pdo->beginTransaction();
    personal_loan_apply_repayment(
        $pdo,
        $loanId,
        $receivedUsd,
        $receivedIqd,
        $changeUsd,
        $changeIq,
        $dolarRate,
        $repaymentDate,
        $notes !== '' ? $notes : null,
        (int) $_SESSION['user_id']
    );
    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'گەڕاندنەوەی قەرز تۆمارکرا (قاسە + باقی ئەگەر هەبوو)',
    ]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('repay_loan personal: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە تۆمارکردن']);
}
