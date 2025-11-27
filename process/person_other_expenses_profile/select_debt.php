<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!hasPermission('view_person_other_expenses_profile')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}

if (isset($_GET['debt_id'])) {
    $debt_id = intval($_GET['debt_id']);
    $stmt = $pdo->prepare('SELECT * FROM person_other_expenses_debt_payments WHERE id = ?');
    $stmt->execute([$debt_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => (bool)$row,
        'data' => $row ?: null
    ]);
    exit;
}

$person_id = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM person_other_expenses_debt_payments WHERE person_id = ? ORDER BY id DESC");
$stmt->execute([$person_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
