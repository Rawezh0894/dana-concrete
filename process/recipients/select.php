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
        $stmt = $pdo->prepare("SELECT * FROM recipients WHERE id = :id");
        $stmt->execute([':id' => $recipientId]);
        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($recipient) {
            echo json_encode(['success' => true, 'data' => $recipient]);
        } else {
            echo json_encode(['success' => false, 'message' => 'وەرگر نەدۆزرایەوە.']);
        }
        exit;
    }

    $query = $pdo->query("
        SELECT id, name, phone1, phone2, opening_meter_total, created_at, updated_at
        FROM recipients
        ORDER BY id DESC
    ");
    $recipients = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $recipients]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}

