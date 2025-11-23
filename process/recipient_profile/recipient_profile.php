<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_recipient')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$recipient_id = isset($_GET['recipient_id']) ? intval($_GET['recipient_id']) : 0;
if ($recipient_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی وەرگر دیاری نەکراوە']);
    exit;
}

try {
    // First try to get from recipients table
    $stmt = $pdo->prepare('SELECT id, name FROM recipients WHERE id = :id');
    $stmt->execute([':id' => $recipient_id]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
    $is_customer = false;

    // If not found, try to get from customers table (is_recipient = 1)
    if (!$recipient) {
        $stmt = $pdo->prepare('SELECT id, name FROM customers WHERE id = :id AND is_recipient = 1');
        $stmt->execute([':id' => $recipient_id]);
        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
        $is_customer = true;
    }

    if (!$recipient) {
        echo json_encode(['success' => false, 'message' => 'وەرگر نەدۆزرایەوە']);
        exit;
    }

    // Get summary of sales where this person is either the customer OR the recipient
    // If recipient is also a customer, check both customer_id and recipient name
    // If recipient is only in recipients table, only check recipient name
    if ($is_customer) {
        $summaryStmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS sales_count,
                COALESCE(SUM(quantity), 0) AS total_quantity,
                COALESCE(SUM(remaining_amount), 0) AS total_remaining
            FROM sales
            WHERE (customer_id = :customer_id OR recipient = :recipient_name)
        ");
        $summaryStmt->execute([
            ':customer_id' => $recipient['id'],
            ':recipient_name' => $recipient['name']
        ]);
    } else {
        $summaryStmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS sales_count,
                COALESCE(SUM(quantity), 0) AS total_quantity,
                COALESCE(SUM(remaining_amount), 0) AS total_remaining
            FROM sales
            WHERE recipient = :recipient_name
        ");
        $summaryStmt->execute([
            ':recipient_name' => $recipient['name']
        ]);
    }
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'sales_count' => (int)($summary['sales_count'] ?? 0),
            'total_quantity' => (float)($summary['total_quantity'] ?? 0),
            'total_remaining' => (float)($summary['total_remaining'] ?? 0)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}


