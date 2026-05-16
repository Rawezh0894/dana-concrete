<?php

declare(strict_types=1);

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/personal_loan_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !personal_loan_can_view()) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێنەدراوە']);
    exit;
}

try {
    personal_loan_ensure_schema($pdo);

    $persons = $pdo->query(
        'SELECT p.id, p.name, p.mobile, p.notes,
                COALESCE(SUM(CASE WHEN l.status = \'active\' THEN l.remaining_usd ELSE 0 END), 0) AS active_remaining_usd,
                COALESCE(SUM(CASE WHEN l.status = \'active\' THEN l.remaining_iqd ELSE 0 END), 0) AS active_remaining_iqd
         FROM personal_loan_persons p
         LEFT JOIN personal_loans l ON l.person_id = p.id
         GROUP BY p.id, p.name, p.mobile, p.notes
         ORDER BY p.name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

    $totals = personal_loan_outstanding_totals($pdo);

    echo json_encode([
        'success' => true,
        'persons' => $persons,
        'summary' => $totals,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('select_persons personal_loans: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
