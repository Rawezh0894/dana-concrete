<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if (!hasPermission('add_payment')) { // Using existing permission for now
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ ئەم کارە (HR Transaction)']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $type = $_POST['type'] ?? '';
    $amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
    $amount_usd = floatval($_POST['amount_usd'] ?? 0);
    $pay_month = $_POST['pay_month'] ?? date('Y-m');
    $date = $_POST['date'] ?? date('Y-m-d');
    $note = $_POST['note'] ?? '';

    if ($employee_id <= 0 || empty($type) || ($amount_iqd == 0 && $amount_usd == 0)) {
        echo json_encode(['success' => false, 'msg' => 'تکایە هەموو خانە پێویستەکان پڕبکەرەوە']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO employee_transactions (employee_id, type, amount_iqd, amount_usd, pay_month, date, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$employee_id, $type, $amount_iqd, $amount_usd, $pay_month, $date, $note, $_SESSION['user_id']]);
    $transaction_id = $pdo->lastInsertId();

    // The Trigger handle_employee_transaction_insert will automatically update the employee balance

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'msg' => 'مامەڵەکە بە سەرکەوتوویی تۆمارکرا',
        'id' => $transaction_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error adding employee transaction: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()]);
}
