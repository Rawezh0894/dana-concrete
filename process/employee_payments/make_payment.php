<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once '../../config/employee_ledger_schema.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $employee_id = $_POST['employee_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    $date = $_POST['date'] ?? date('Y-m-d');
    $note = $_POST['note'] ?? '';
    $type = $_POST['type'] ?? 'payment'; // payment | advance | penalty

    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    if (!hasPermission('add_payment')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'ڕێگەپێدراوە بۆ تۆمارکردنی پارەدان']);
        exit;
    }

    ensureEmployeeLedgerSchema($pdo);

    if (!$employee_id || !$amount) {
        throw new Exception('تکایە هەموو خانەکان پڕبکەرەوە');
    }
    $amount = (float)str_replace(',', '', preg_replace('/[^\d\.\-]/', '', (string)$amount));
    if ($amount <= 0) {
        throw new Exception('بڕ دەبێت لە 0 زیاتر بێت');
    }

    $allowedTypes = ['payment', 'advance', 'penalty'];
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'payment';
    }

    $pdo->beginTransaction();

    // Employee name for note
    $stmtEmp = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
    $stmtEmp->execute([(int)$employee_id]);
    $empName = $stmtEmp->fetchColumn() ?: 'نەناسراو';

    // Insert ledger transaction (debit)
    $stmt = $pdo->prepare("
        INSERT INTO employee_transactions 
        (employee_id, type, amount, operation, transaction_date, description) 
        VALUES 
        (?, ?, ?, 'debit', ?, ?)
    ");
    $stmt->execute([(int)$employee_id, $type, $amount, $date . ' ' . date('H:i:s'), $note]);
    $txId = (int)$pdo->lastInsertId();

    // Cash box only for real cash-out (payment / advance)
    if (in_array($type, ['payment', 'advance'], true)) {
        $ref = "[REF:EMP_TXN#{$txId}#IQD]";
        $cashNote = trim("پارەدان بە کارمەند | ناو: {$empName} | جۆر: {$type} | بڕ: {$amount} د.ع | {$ref}" . ($note ? " | تێبینی: {$note}" : ""));
        $cashStmt = $pdo->prepare("
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (?, 'withdraw', ?, 0, 'دینار', ?, ?)
        ");
        $cashStmt->execute([$date, $amount, $cashNote, null]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'پارەدان بە سەرکەوتوویی تۆمارکرا']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
