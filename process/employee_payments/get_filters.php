<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once '../../config/employee_ledger_schema.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('view_employee_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ بینینی مامەڵەکانی کارمەند']);
    exit;
}

try {
    ensureEmployeeLedgerSchema($pdo);

    $employees = $pdo->query("SELECT id, name FROM employees ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    // Build month list from both transaction_date and pay_month
    $months = [];

    $stmt = $pdo->query("SELECT DISTINCT DATE_FORMAT(transaction_date, '%Y-%m') as m FROM employee_transactions ORDER BY m DESC");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!empty($row['m'])) $months[$row['m']] = true;
    }
    $stmt2 = $pdo->query("SELECT DISTINCT pay_month as m FROM employee_transactions WHERE pay_month IS NOT NULL AND pay_month <> '' ORDER BY m DESC");
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!empty($row['m'])) $months[$row['m']] = true;
    }

    $monthList = array_keys($months);
    rsort($monthList);

    echo json_encode([
        'success' => true,
        'data' => [
            'employees' => $employees,
            'months' => array_map(fn($m) => ['month' => $m], $monthList)
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


