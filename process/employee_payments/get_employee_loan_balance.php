<?php

declare(strict_types=1);

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/employee_loan_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('view_employee_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە']);
    exit;
}

$employee_id = (int) ($_GET['employee_id'] ?? 0);
if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'employee_id پێویستە']);
    exit;
}

try {
    $chk = $pdo->query("SHOW TABLES LIKE 'employee_loans'");
    if (!$chk || $chk->rowCount() === 0) {
        echo json_encode([
            'success' => true,
            'outstanding_usd' => 0.0,
            'outstanding_iqd' => 0.0,
            'loans' => [],
            'migration_required' => true,
        ]);
        exit;
    }

    $tot = employee_loan_outstanding_totals($pdo, $employee_id);

    $stmt = $pdo->prepare(
        "SELECT id, loan_usd, loan_iqd, remaining_usd, remaining_iqd, loan_date, status, notes, created_at
         FROM employee_loans
         WHERE employee_id = ? AND status = 'active'
         ORDER BY loan_date ASC, id ASC"
    );
    $stmt->execute([$employee_id]);
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'outstanding_usd' => $tot['usd'],
        'outstanding_iqd' => $tot['iqd'],
        'loans' => $loans,
    ]);
} catch (PDOException $e) {
    error_log('get_employee_loan_balance: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
