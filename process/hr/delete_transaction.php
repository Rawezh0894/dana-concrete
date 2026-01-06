<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if (!hasPermission('delete_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'msg' => 'ID ناسراو نییە']);
        exit;
    }

    $pdo->beginTransaction();

    // The Trigger should handle the balance update on delete if we create it.
    // If we don't have a delete trigger yet, we should add it in the SQL query.
    // I will write the SQL for the delete trigger in the final report.

    $stmt = $pdo->prepare("DELETE FROM employee_transactions WHERE id = ?");
    $result = $stmt->execute([$id]);

    $pdo->commit();

    if ($result) {
        echo json_encode(['success' => true, 'msg' => 'مامەڵەکە سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'سڕینەوە سەرکەوتوو نەبوو']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
