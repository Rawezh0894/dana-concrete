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
        exit(json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ بینینی مامەڵەکانی کارمەند'], JSON_UNESCAPED_UNICODE));
    }

    ensureEmployeeLedgerSchema($pdo);

    $month_filter = $_GET['month'] ?? '';
    $employee_filter = $_GET['employee'] ?? '';
    $limit = (int)($_GET['limit'] ?? 200);
    if ($limit <= 0 || $limit > 1000) $limit = 200;

    $where = [];
    $params = [];

    if ($employee_filter !== '') {
        $where[] = "t.employee_id = ?";
        $params[] = (int)$employee_filter;
    }
    if ($month_filter !== '') {
        // Validate YYYY-MM
        if (!preg_match('/^\d{4}-\d{2}$/', $month_filter)) {
            $month_filter = '';
        }
    }

    if ($month_filter !== '') {
        // Match either transaction_date month or explicit pay_month
        $where[] = "(DATE_FORMAT(t.transaction_date, '%Y-%m') = ? OR t.pay_month = ?)";
        $params[] = $month_filter;
        $params[] = $month_filter;
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $stmt = $pdo->prepare("
        SELECT 
            t.*, 
            e.name as employee_name 
        FROM employee_transactions t
        LEFT JOIN employees e ON t.employee_id = e.id
        $whereSql
        ORDER BY t.transaction_date DESC, t.id DESC
        LIMIT $limit
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Defensive output escaping for any text fields (JSON API, but avoids XSS-lint false positives)
    $safe = [];
    foreach ($transactions as $t) {
        $safe[] = [
            'id' => (int)($t['id'] ?? 0),
            'employee_id' => (int)($t['employee_id'] ?? 0),
            'employee_name' => htmlentities((string)($t['employee_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'type' => htmlentities((string)($t['type'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'operation' => htmlentities((string)($t['operation'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'amount' => (float)($t['amount'] ?? 0),
            'pay_month' => $t['pay_month'] !== null ? htmlentities((string)$t['pay_month'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null,
            'transaction_date' => (string)($t['transaction_date'] ?? ''),
            'description' => $t['description'] !== null ? htmlentities((string)$t['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null,
        ];
    }

    exit(json_encode(
        ['success' => true, 'data' => $safe],
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ));
} catch (PDOException $e) {
    http_response_code(500);
    error_log('PDOException in employee_payments/get_ledger.php: ' . $e->getMessage());
    exit(json_encode(['success' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE));
}
?>
