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

if (!hasPermission('view_recipient')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$recipientId = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    if ($recipientId > 0) {
        // Get customer who is also a recipient
        $stmt = $pdo->prepare("
            SELECT 
                id, 
                name, 
                mobile1 AS phone1, 
                mobile2 AS phone2, 
                0.00 AS opening_meter_total,
                NULL AS created_at,
                NULL AS updated_at
            FROM customers 
            WHERE id = :id AND is_recipient = 1
        ");
        $stmt->execute([':id' => $recipientId]);
        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($recipient) {
            echo json_encode(['success' => true, 'data' => $recipient]);
        } else {
            echo json_encode(['success' => false, 'message' => 'وەرگر نەدۆزرایەوە.']);
        }
        exit;
    }

    // Get customers who are also recipients (is_recipient = 1)
    $query = $pdo->query("
        SELECT 
            id, 
            name, 
            mobile1 AS phone1, 
            mobile2 AS phone2, 
            0.00 AS opening_meter_total,
            NULL AS created_at,
            NULL AS updated_at
        FROM customers
        WHERE is_recipient = 1
        ORDER BY id DESC
    ");
    $recipients = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $recipients]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}

