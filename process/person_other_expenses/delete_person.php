<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!hasPermission('delete_person_other_expenses')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'msg' => 'ID پێویستە']);
        exit;
    }
    // Check for debts
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM person_other_expenses_debt_payments WHERE person_id = ?');
    $stmt->execute([$id]);
    $debtCount = $stmt->fetchColumn();
    if ($debtCount > 0) {
        echo json_encode(['success' => false, 'msg' => 'ناتوانرێت ئەم کەسە بسڕدرێت چونکە مێژووی دانەوەی هەیە']);
        exit;
    }
    // Check for related other_expenses
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM other_expenses WHERE person_id = ?');
    $stmt->execute([$id]);
    $expenseCount = $stmt->fetchColumn();
    if ($expenseCount > 0) {
        echo json_encode(['success' => false, 'msg' => 'ناتوانرێت ئەم کەسە بسڕدرێت چونکە مامەڵەی خەرجی هەیە']);
        exit;
    }
    $stmt = $pdo->prepare('DELETE FROM other_expense_persons WHERE id = ?');
    $ok = $stmt->execute([$id]);
    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە']);
    }
    exit;
}
echo json_encode(['success' => false, 'msg' => 'POST تەنها ڕێگەپێدراوە']);
