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

if (!hasPermission('add_recipient')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone1 = trim($_POST['phone1'] ?? '');
$phone2 = trim($_POST['phone2'] ?? '');

if ($name === '' || $phone1 === '') {
    echo json_encode(['success' => false, 'message' => 'تکایە ناو و ژمارەی مۆبایلی یەکەم پڕبکەرەوە.']);
    exit;
}

try {
    // Check if customer with this mobile already exists
    $stmt = $pdo->prepare("SELECT id, is_recipient FROM customers WHERE mobile1 = ?");
    $stmt->execute([$phone1]);
    $existingCustomer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingCustomer) {
        // Customer exists, just update is_recipient to 1
        if ($existingCustomer['is_recipient'] == 1) {
            echo json_encode(['success' => false, 'message' => 'ئەم کڕیارە پێشتر بە وەرگر تۆمارکراوە.']);
            exit;
        }
        
        $update = $pdo->prepare("UPDATE customers SET is_recipient = 1, name = ?, mobile2 = ? WHERE id = ?");
        $result = $update->execute([
            $name,
            $phone2 !== '' ? $phone2 : null,
            $existingCustomer['id']
        ]);
        
        if ($result) {
            $stmt = $pdo->prepare("SELECT id, name, mobile1 AS phone1, mobile2 AS phone2 FROM customers WHERE id = ?");
            $stmt->execute([$existingCustomer['id']]);
            $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'وەرگر زیادکرا.',
                'recipient' => $recipient
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'نەتوانرا وەرگر زیادبکرێت.']);
        }
    } else {
        // Customer doesn't exist, create new customer with is_recipient = 1
        $insert = $pdo->prepare("
            INSERT INTO customers (name, mobile1, mobile2, is_recipient, opening_debt_usd, opening_debt_iqd)
            VALUES (?, ?, ?, 1, 0, 0)
        ");
        $result = $insert->execute([
            $name,
            $phone1,
            $phone2 !== '' ? $phone2 : null
        ]);

        if ($result) {
            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT id, name, mobile1 AS phone1, mobile2 AS phone2 FROM customers WHERE id = ?");
            $stmt->execute([$newId]);
            $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'وەرگر زیادکرا.',
                'recipient' => $recipient
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'نەتوانرا وەرگر زیادبکرێت.']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}

