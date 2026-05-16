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
$name = trim((string) ($_POST['name'] ?? ''));
$mobile = trim((string) ($_POST['mobile'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($id <= 0 || $name === '') {
    echo json_encode(['success' => false, 'message' => 'زانیاری ناتەواوە']);
    exit;
}

try {
    personal_loan_ensure_schema($pdo);
    $stmt = $pdo->prepare('UPDATE personal_loan_persons SET name = ?, mobile = ?, notes = ? WHERE id = ?');
    $stmt->execute([
        $name,
        $mobile !== '' ? $mobile : null,
        $notes !== '' ? $notes : null,
        $id,
    ]);
    echo json_encode(['success' => true, 'message' => 'نوێکرایەوە']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
