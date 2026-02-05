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
$openingMeterTotal = isset($_POST['opening_meter_total']) ? (float)$_POST['opening_meter_total'] : 0.0;

$name = $name === '' ? null : $name;
$phone1 = $phone1 === '' ? null : $phone1;
$phone2 = $phone2 === '' ? null : $phone2;

if ($name === null && $phone1 === null) {
    echo json_encode(['success' => false, 'message' => 'پێویستە ناو یان ژمارەی مۆبایل بنووسیت.']);
    exit;
}

try {
    if ($phone1 !== null) {
        // Check if customer with this mobile already exists (any customer, regardless of is_recipient)
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE mobile1 = ?");
        $stmt->execute([$phone1]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی مۆبایلە پێشتر وەک کڕیار داخڵ کراوە.']);
            exit;
        }
        
        // Check if recipient with this mobile already exists in recipients table
        $recipientStmt = $pdo->prepare("SELECT id FROM recipients WHERE phone1 = ?");
        $recipientStmt->execute([$phone1]);
        if ($recipientStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی مۆبایلە پێشتر وەک وەرگر تۆمارکراوە.']);
            exit;
        }
    } elseif ($phone1 === null) {
        $nameStmt = $pdo->prepare("SELECT id FROM recipients WHERE name = ?");
        $nameStmt->execute([$name]);
        if ($nameStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ئەم ناوە پێشتر تۆمارکراوە.']);
            exit;
        }
    }
    
    // If no customer or recipient exists, create new recipient in recipients table only
    $insert = $pdo->prepare("
        INSERT INTO recipients (name, phone1, phone2, opening_meter_total)
        VALUES (?, ?, ?, ?)
    ");
    $result = $insert->execute([
        $name,
        $phone1,
        $phone2,
        $openingMeterTotal
    ]);

    if ($result) {
        $newId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT id, name, phone1, phone2 FROM recipients WHERE id = ?");
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
} catch (Exception $e) {
    error_log('Exception in recipients/add.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی وەرگر!']);
}

