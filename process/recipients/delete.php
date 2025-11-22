<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasPermission('delete_recipient')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ناسێنراوی دروست نییە.']);
    exit;
}

try {
    // Check if customer exists and is a recipient
    $checkStmt = $pdo->prepare("SELECT id FROM customers WHERE id = :id AND is_recipient = 1");
    $checkStmt->execute([':id' => $id]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'وەرگر نەدۆزرایەوە.']);
        exit;
    }

    // Set is_recipient to 0 instead of deleting (to keep customer data)
    $update = $pdo->prepare("UPDATE customers SET is_recipient = 0 WHERE id = :id");
    $result = $update->execute([':id' => $id]);

    if ($result && $update->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'وەرگر سڕایەوە.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'نەتوانرا وەرگر بسڕدرێتەوە.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}

