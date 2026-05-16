<?php

declare(strict_types=1);

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/personal_loan_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !personal_loan_can_manage()) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێنەدراوە']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی هەڵە']);
    exit;
}

try {
    personal_loan_ensure_schema($pdo);
    $chk = $pdo->prepare(
        "SELECT COUNT(*) FROM personal_loans WHERE person_id = ? AND status = 'active'"
    );
    $chk->execute([$id]);
    if ((int) $chk->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ناتوانیت بسڕیتەوە — قەرزی چالاک هەیە']);
        exit;
    }
    $pdo->prepare('DELETE FROM personal_loan_persons WHERE id = ?')->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'سڕایەوە']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
