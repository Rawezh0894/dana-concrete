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
    $rows = $pdo->query(
        "SELECT pl.id AS loan_id, pl.person_id, p.name AS person_name, pl.loan_date,
                pl.remaining_usd, pl.remaining_iqd, pl.loan_usd, pl.loan_iqd, pl.notes
         FROM personal_loans pl
         INNER JOIN personal_loan_persons p ON p.id = pl.person_id
         WHERE pl.status = 'active'
         ORDER BY pl.loan_date DESC, pl.id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'loans' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
