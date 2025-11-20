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
$opening_meter_total = isset($_POST['opening_meter_total']) ? floatval($_POST['opening_meter_total']) : 0;

if ($name === '' || $phone1 === '') {
    echo json_encode(['success' => false, 'message' => 'تکایە ناو و ژمارەی مۆبایلی یەکەم پڕبکەرەوە.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM recipients WHERE phone1 = ?");
    $stmt->execute([$phone1]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەیە پێشتر بە وەرگرێک تۆمارکراوە.']);
        exit;
    }

    $insert = $pdo->prepare("
        INSERT INTO recipients (name, phone1, phone2, opening_meter_total)
        VALUES (:name, :phone1, :phone2, :opening_meter_total)
    ");
    $result = $insert->execute([
        ':name' => $name,
        ':phone1' => $phone1,
        ':phone2' => $phone2 !== '' ? $phone2 : null,
        ':opening_meter_total' => max(0, $opening_meter_total)
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'وەرگر زیادکرا.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'نەتوانرا وەرگر زیادبکرێت.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}

