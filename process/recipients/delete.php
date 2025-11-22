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
    // First try to get from recipients table
    $checkStmt = $pdo->prepare("SELECT id, name FROM recipients WHERE id = :id");
    $checkStmt->execute([':id' => $id]);
    $recipient = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $isFromRecipientsTable = true;

    // If not found, try to get from customers table (is_recipient = 1)
    if (!$recipient) {
        $checkStmt = $pdo->prepare("SELECT id, name FROM customers WHERE id = :id AND is_recipient = 1");
        $checkStmt->execute([':id' => $id]);
        $recipient = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $isFromRecipientsTable = false;
    }

    if (!$recipient) {
        echo json_encode(['success' => false, 'message' => 'وەرگر نەدۆزرایەوە.']);
        exit;
    }

    $recipientName = $recipient['name'];

    // Check if recipient has concrete receipts
    $hasConcreteReceipts = $pdo->prepare("SELECT COUNT(*) FROM concrete_receipts WHERE receiver_name = :recipient_name");
    $hasConcreteReceipts->execute([':recipient_name' => $recipientName]);
    if ($hasConcreteReceipts->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ناتوانرێت وەرگر بسڕدرێت چونکە پسوڵەی کۆنکرێت بۆ تۆمارکراوە']);
        exit;
    }

    // Check if recipient has notes
    $hasNotes = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE recipient = :recipient_name");
    $hasNotes->execute([':recipient_name' => $recipientName]);
    if ($hasNotes->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ناتوانرێت وەرگر بسڕدرێت چونکە تێبینی بۆ تۆمارکراوە']);
        exit;
    }

    // Check if recipient has sales (as recipient or as customer)
    $hasSales = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE recipient = :recipient_name OR customer_id = :customer_id");
    $hasSales->execute([':recipient_name' => $recipientName, ':customer_id' => $id]);
    if ($hasSales->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ناتوانرێت وەرگر بسڕدرێت چونکە مامەڵەی فرۆشتن بۆ تۆمارکراوە']);
        exit;
    }

    // Delete based on source table
    if ($isFromRecipientsTable) {
        // Delete from recipients table
        $delete = $pdo->prepare("DELETE FROM recipients WHERE id = :id");
        $result = $delete->execute([':id' => $id]);
        
        if ($result && $delete->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'وەرگر سڕایەوە.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'نەتوانرا وەرگر بسڕدرێتەوە.']);
        }
    } else {
        // Set is_recipient to 0 instead of deleting (to keep customer data)
        $update = $pdo->prepare("UPDATE customers SET is_recipient = 0 WHERE id = :id");
        $result = $update->execute([':id' => $id]);

        if ($result && $update->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'وەرگر سڕایەوە.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'نەتوانرا وەرگر بسڕدرێتەوە.']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}

