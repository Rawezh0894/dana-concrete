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
        // First try to get from recipients table
        $stmt = $pdo->prepare("SELECT id, name, phone1, phone2, opening_meter_total, created_at, updated_at, 'recipient_only' AS recipient_type FROM recipients WHERE id = :id");
        $stmt->execute([':id' => $recipientId]);
        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

        // If not found, try to get from customers table (is_recipient = 1)
        if (!$recipient) {
            $stmt = $pdo->prepare("
                SELECT 
                    id, 
                    name, 
                    mobile1 AS phone1, 
                    mobile2 AS phone2, 
                    0.00 AS opening_meter_total,
                    NULL AS created_at,
                    NULL AS updated_at,
                    'customer_and_recipient' AS recipient_type
                FROM customers 
                WHERE id = :id AND is_recipient = 1
            ");
            $stmt->execute([':id' => $recipientId]);
            $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($recipient) {
            echo json_encode(['success' => true, 'data' => $recipient]);
        } else {
            echo json_encode(['success' => false, 'message' => 'وەرگر نەدۆزرایەوە.']);
        }
        exit;
    }

    // Get recipients from both tables: recipients table and customers with is_recipient = 1
    $recipients_from_table = $pdo->query("
        SELECT id, name, phone1, phone2, opening_meter_total, created_at, updated_at, 'recipient_only' AS recipient_type
        FROM recipients
        ORDER BY name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $recipients_from_customers = $pdo->query("
        SELECT 
            id, 
            name, 
            mobile1 AS phone1, 
            mobile2 AS phone2, 
            0.00 AS opening_meter_total,
            NULL AS created_at,
            NULL AS updated_at,
            'customer_and_recipient' AS recipient_type
        FROM customers
        WHERE is_recipient = 1
        ORDER BY name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Combine both and remove duplicates by name
    $recipients = [];
    $recipient_names = [];
    
    // Add recipients from recipients table first
    foreach ($recipients_from_table as $r) {
        $recipients[] = $r;
        $recipient_names[] = strtolower(trim($r['name']));
    }
    
    // Add recipients from customers table (avoid duplicates)
    foreach ($recipients_from_customers as $r) {
        if (!in_array(strtolower(trim($r['name'])), $recipient_names)) {
            $recipients[] = $r;
            $recipient_names[] = strtolower(trim($r['name']));
        }
    }
    
    // Sort by name
    usort($recipients, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });

    echo json_encode(['success' => true, 'data' => $recipients]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}

