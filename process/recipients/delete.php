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
    // First check if recipient exists in recipients table
    $recipientStmt = $pdo->prepare("SELECT id, name FROM recipients WHERE id = :id");
    $recipientStmt->execute([':id' => $id]);
    $recipient = $recipientStmt->fetch(PDO::FETCH_ASSOC);
    
    $recipientName = null;
    $isFromRecipientsTable = false;
    $isFromCustomersTable = false;
    
    if ($recipient) {
        // Recipient exists in recipients table
        $recipientName = $recipient['name'];
        $isFromRecipientsTable = true;
    } else {
        // Check if customer exists and is a recipient
        $customerStmt = $pdo->prepare("SELECT id, name FROM customers WHERE id = :id AND is_recipient = 1");
        $customerStmt->execute([':id' => $id]);
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            $recipientName = $customer['name'];
            $isFromCustomersTable = true;
        } else {
            echo json_encode(['success' => false, 'message' => 'وەرگر نەدۆزرایەوە.']);
            exit;
        }
    }
    
    // Check if recipient has sales receipts
    if ($isFromRecipientsTable) {
        // Check sales by recipient name
        $hasSales = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE recipient = :recipient_name");
        $hasSales->execute([':recipient_name' => $recipientName]);
    } else {
        // Check sales by customer_id OR recipient name
        $hasSales = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = :customer_id OR recipient = :recipient_name");
        $hasSales->execute([
            ':customer_id' => $id,
            ':recipient_name' => $recipientName
        ]);
    }
    
    if ($hasSales->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ناتوانرێت وەرگر بسڕدرێتەوە چونکە پسووڵەی فرۆشتن بۆ تۆمارکراوە']);
        exit;
    }
    
    // Check if recipient has concrete receipts
    if ($isFromRecipientsTable) {
        // Check concrete receipts by receiver_name only
        $hasConcreteReceipts = $pdo->prepare("SELECT COUNT(*) FROM concrete_receipts WHERE receiver_name = :recipient_name");
        $hasConcreteReceipts->execute([':recipient_name' => $recipientName]);
    } else {
        // Check concrete receipts by customer_id OR receiver_name
        $hasConcreteReceipts = $pdo->prepare("SELECT COUNT(*) FROM concrete_receipts WHERE customer_id = :customer_id OR receiver_name = :recipient_name");
        $hasConcreteReceipts->execute([
            ':customer_id' => $id,
            ':recipient_name' => $recipientName
        ]);
    }
    
    if ($hasConcreteReceipts->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ناتوانرێت وەرگر بسڕدرێتەوە چونکە پسووڵەی کۆنکرێت بۆ تۆمارکراوە']);
        exit;
    }
    
    // Check if recipient has notes
    if ($isFromRecipientsTable) {
        // Check notes by recipient name only
        $hasNotes = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE recipient = :recipient_name");
        $hasNotes->execute([':recipient_name' => $recipientName]);
    } else {
        // Check notes by customer_id OR recipient name
        $hasNotes = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE customer_id = :customer_id OR recipient = :recipient_name");
        $hasNotes->execute([
            ':customer_id' => $id,
            ':recipient_name' => $recipientName
        ]);
    }
    
    if ($hasNotes->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ناتوانرێت وەرگر بسڕدرێتەوە چونکە تێبینی بۆ تۆمارکراوە']);
        exit;
    }
    
    // If recipient is from recipients table, delete it
    if ($isFromRecipientsTable) {
        $delete = $pdo->prepare("DELETE FROM recipients WHERE id = :id");
        $result = $delete->execute([':id' => $id]);
        
        if ($result && $delete->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'وەرگر سڕایەوە.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'نەتوانرا وەرگر بسڕدرێتەوە.']);
        }
    } else {
        // If recipient is from customers table, set is_recipient to 0
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

