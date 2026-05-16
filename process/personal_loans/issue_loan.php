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

$personId = (int) ($_POST['person_id'] ?? 0);
$loanUsd = round((float) ($_POST['loan_usd'] ?? 0), 2);
$loanIqd = round((float) ($_POST['loan_iqd'] ?? 0), 2);
$loanDate = trim((string) ($_POST['loan_date'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($personId <= 0) {
    echo json_encode(['success' => false, 'message' => 'کەس هەلبژێرە']);
    exit;
}
if ($loanDate === '') {
    $loanDate = date('Y-m-d');
}

try {
    personal_loan_ensure_schema($pdo);
    $pdo->beginTransaction();
    $loanId = personal_loan_issue(
        $pdo,
        $personId,
        $loanUsd,
        $loanIqd,
        $loanDate,
        $notes !== '' ? $notes : null,
        (int) $_SESSION['user_id']
    );
    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'قەرز دراو و لە قاسە تۆمارکرا',
        'loan_id' => $loanId,
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
    error_log('issue_loan personal: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە تۆمارکردن']);
}
