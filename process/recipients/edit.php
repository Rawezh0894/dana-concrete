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

if (!hasPermission('edit_recipient')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = trim($_POST['name'] ?? '');
$phone1 = trim($_POST['phone1'] ?? '');
$phone2 = trim($_POST['phone2'] ?? '');
$opening_meter_total = isset($_POST['opening_meter_total']) ? floatval($_POST['opening_meter_total']) : 0;

if ($id <= 0 || $name === '' || $phone1 === '') {
    echo json_encode(['success' => false, 'message' => 'تکایە هەموو خانە پێویستەکان پڕبکەرەوە.']);
    exit;
}

try {
    // Check if customer exists and is a recipient
    $checkStmt = $pdo->prepare("SELECT id FROM customers WHERE id = :id AND is_recipient = 1");
    $checkStmt->execute([':id' => $id]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'وەرگر نەدۆزرایەوە.']);
        exit;
    }

    // Check for duplicate mobile number (excluding current customer)
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE mobile1 = :phone1 AND id != :id");
    $stmt->execute([':phone1' => $phone1, ':id' => $id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەیە بە کڕیارێکی دیکە تۆمارکراوە.']);
        exit;
    }

    // Update customer (recipient data - note: opening_meter_total is not in customers table)
    $update = $pdo->prepare("
        UPDATE customers
        SET name = :name,
            mobile1 = :phone1,
            mobile2 = :phone2
        WHERE id = :id AND is_recipient = 1
    ");
    $result = $update->execute([
        ':name' => $name,
        ':phone1' => $phone1,
        ':phone2' => $phone2 !== '' ? $phone2 : null,
        ':id' => $id
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'زانیاری وەرگر نوێکرایەوە.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'نوێکردنەوە سەرکەوتوو نەبوو.']);
    }
} catch (Exception $e) {
    error_log('Exception in recipients/edit.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی وەرگر!']);
}

