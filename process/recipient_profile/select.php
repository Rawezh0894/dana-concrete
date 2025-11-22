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
    // Get customer who is also a recipient
    $stmt = $pdo->prepare('SELECT id, name FROM customers WHERE id = :id AND is_recipient = 1');
    $stmt->execute([':id' => $recipient_id]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        echo json_encode(['success' => false, 'message' => 'وەرگر نەدۆزرایەوە']);
        exit;
    }

    // Get sales where this person is either the customer OR the recipient
    $salesStmt = $pdo->prepare('
        SELECT 
            s.*, 
            c.name AS customer_name,
            f.name AS formula_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN concrete_formulas f ON s.formula_id = f.id
        WHERE (s.customer_id = :customer_id OR s.recipient = :recipient_name)
        ORDER BY s.id DESC
    ');
    $salesStmt->execute([
        ':customer_id' => $recipient['id'],
        ':recipient_name' => $recipient['name']
    ]);
    $sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $sales]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}


