<?php

declare(strict_types=1);

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if (!hasPermission('view_employee_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدراوە']);
    exit;
}

$can_repay = hasPermission('add_payment') || hasPermission('add_cash_box');

$rows = [];
try {
    $chk = $pdo->query("SHOW TABLES LIKE 'employee_loans'");
    if ($chk && $chk->rowCount() > 0) {
        $st = $pdo->query(
            "SELECT el.id AS loan_id, el.employee_id, e.name AS employee_name,
                    el.remaining_usd, el.remaining_iqd, el.loan_date
             FROM employee_loans el
             INNER JOIN employees e ON e.id = el.employee_id
             WHERE el.status = 'active' AND (el.remaining_usd > 0.005 OR el.remaining_iqd > 0.005)
             ORDER BY e.name ASC, el.loan_date ASC, el.id ASC"
        );
        $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    }
} catch (Exception $e) {
    error_log('active_loans_list: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە خوێندنەوەی قەرزەکان']);
    exit;
}

echo json_encode([
    'success' => true,
    'rows' => $rows,
    'can_repay' => $can_repay,
]);
