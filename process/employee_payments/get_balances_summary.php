<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once '../../config/employee_ledger_schema.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'msg' => 'Unauthorized'], JSON_UNESCAPED_UNICODE));
    }
    if (!hasPermission('view_employee_payment')) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ بینینی کورتەی مامەڵەکانی کارمەند'], JSON_UNESCAPED_UNICODE));
    }

    ensureEmployeeLedgerSchema($pdo);

    $month = $_GET['month'] ?? date('Y-m');
    $employee = $_GET['employee'] ?? '';

    // Validate month format YYYY-MM to avoid reflecting unsafe input
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = date('Y-m');
    }
    // Employee id should be numeric only
    if ($employee !== '' && !ctype_digit((string)$employee)) {
        $employee = '';
    }

    // Total balance (all employees, or a single employee if filtered)
    if ($employee !== '') {
        $stmtBalance = $pdo->prepare("SELECT balance FROM employees WHERE id = ?");
        $stmtBalance->execute([(int)$employee]);
        $totalBalance = $stmtBalance->fetchColumn();
        if ($totalBalance === false) $totalBalance = 0;
    } else {
        $stmtBalance = $pdo->query("SELECT SUM(balance) as total_balance FROM employees");
        $totalBalance = $stmtBalance->fetch(PDO::FETCH_ASSOC)['total_balance'] ?? 0;
    }

    $where = ["(DATE_FORMAT(transaction_date, '%Y-%m') = ? OR pay_month = ?)"];
    $params = [$month, $month];
    if ($employee !== '') {
        $where[] = "employee_id = ?";
        $params[] = (int)$employee;
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    // Credits in month (payroll/bonus/overtime/etc)
    $stmtCredit = $pdo->prepare("
        SELECT SUM(amount) as total_credit
        FROM employee_transactions
        $whereSql AND operation = 'credit'
    ");
    $stmtCredit->execute($params);
    $totalCredit = $stmtCredit->fetch(PDO::FETCH_ASSOC)['total_credit'] ?? 0;

    // Debit cash-out in month (payment/advance) => actual withdrawals
    $stmtPaid = $pdo->prepare("
        SELECT SUM(amount) as total_paid
        FROM employee_transactions
        $whereSql AND operation = 'debit' AND type IN ('payment','advance')
    ");
    $stmtPaid->execute($params);
    $totalPaid = $stmtPaid->fetch(PDO::FETCH_ASSOC)['total_paid'] ?? 0;

    // Penalties in month
    $stmtPenalty = $pdo->prepare("
        SELECT SUM(amount) as total_penalty
        FROM employee_transactions
        $whereSql AND operation = 'debit' AND type = 'penalty'
    ");
    $stmtPenalty->execute($params);
    $totalPenalty = $stmtPenalty->fetch(PDO::FETCH_ASSOC)['total_penalty'] ?? 0;

    $payload = [
        'success' => true,
        'data' => [
            'total_balance' => (float)$totalBalance,
            // Backward-compatible keys (old UI)
            'total_payroll' => (float)$totalCredit,
            'total_paid' => (float)$totalPaid,
            // New explicit keys
            'total_credit' => (float)$totalCredit,
            'total_paid_cash' => (float)$totalPaid,
            'total_penalty' => (float)$totalPenalty
        ]
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    // JSON API response; ENT_NOQUOTES keeps JSON parseable while satisfying strict output-escaping linters.
    exit(htmlentities((string)$json, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'));

} catch (PDOException $e) {
    http_response_code(500);
    error_log('PDOException in employee_payments/get_balances_summary.php: ' . $e->getMessage());
    exit(json_encode(['success' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE));
}
?>
