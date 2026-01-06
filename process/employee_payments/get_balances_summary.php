<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    // 1. Total Balance (Sum of all employee balances)
    $stmtBalance = $pdo->query("SELECT SUM(balance) as total_balance FROM employees");
    $totalBalance = $stmtBalance->fetch(PDO::FETCH_ASSOC)['total_balance'] ?? 0;

    // 2. Total Payroll This Month (Credit transactions in current month)
    $currentMonth = date('Y-m');
    $stmtPayroll = $pdo->prepare("
        SELECT SUM(amount) as total_payroll 
        FROM employee_transactions 
        WHERE operation = 'credit' 
        AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
    ");
    $stmtPayroll->execute([$currentMonth]);
    $totalPayroll = $stmtPayroll->fetch(PDO::FETCH_ASSOC)['total_payroll'] ?? 0;

    // 3. Total Paid This Month (Debit transactions in current month)
    $stmtPaid = $pdo->prepare("
        SELECT SUM(amount) as total_paid 
        FROM employee_transactions 
        WHERE operation = 'debit' 
        AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
    ");
    $stmtPaid->execute([$currentMonth]);
    $totalPaid = $stmtPaid->fetch(PDO::FETCH_ASSOC)['total_paid'] ?? 0;

    echo json_encode([
        'total_balance' => $totalBalance,
        'total_payroll' => $totalPayroll,
        'total_paid' => $totalPaid
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
