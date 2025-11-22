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

try {
    // Get summary from customers who are also recipients
    $summaryStmt = $pdo->query("
        SELECT 
            COUNT(*) AS total_recipients,
            0.00 AS total_opening_meter,
            0 AS recipients_with_meter
        FROM customers
        WHERE is_recipient = 1
    ");
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_recipients' => (int)($summary['total_recipients'] ?? 0),
            'total_opening_meter' => (float)($summary['total_opening_meter'] ?? 0),
            'recipients_with_meter' => (int)($summary['recipients_with_meter'] ?? 0)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}

