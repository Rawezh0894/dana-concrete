<?php
require_once '../../config/db_conected.php';

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

    if (!$employee_id || !$amount) {
        throw new Exception('تکایە هەموو خانەکان پڕبکەرەوە');
    }

    $pdo->beginTransaction();

    // Insert Transaction
    $stmt = $pdo->prepare("
        INSERT INTO employee_transactions 
        (employee_id, type, amount, operation, transaction_date, description) 
        VALUES 
        (?, 'payment', ?, 'debit', ?, ?)
    ");
    $stmt->execute([$employee_id, $amount, $date, $note]);

    // Note: The trigger will automatically update the employee balance

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'پارەدان بە سەرکەوتوویی تۆمارکرا']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
