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

$name = trim((string) ($_POST['name'] ?? ''));
$mobile = trim((string) ($_POST['mobile'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'ناو پێویستە']);
    exit;
}

try {
    personal_loan_ensure_schema($pdo);
    $stmt = $pdo->prepare(
        'INSERT INTO personal_loan_persons (name, mobile, notes) VALUES (?, ?, ?)'
    );
    $stmt->execute([
        $name,
        $mobile !== '' ? $mobile : null,
        $notes !== '' ? $notes : null,
    ]);
    echo json_encode(['success' => true, 'message' => 'کەس زیادکرا', 'id' => (int) $pdo->lastInsertId()]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
