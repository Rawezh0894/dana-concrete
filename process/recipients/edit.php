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
    $stmt = $pdo->prepare("SELECT id FROM recipients WHERE phone1 = :phone1 AND id != :id");
    $stmt->execute([':phone1' => $phone1, ':id' => $id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەیە بە وەرگرێکی دیکە تۆمارکراوە.']);
        exit;
    }

    $update = $pdo->prepare("
        UPDATE recipients
        SET name = :name,
            phone1 = :phone1,
            phone2 = :phone2,
            opening_meter_total = :opening_meter_total
        WHERE id = :id
    ");
    $result = $update->execute([
        ':name' => $name,
        ':phone1' => $phone1,
        ':phone2' => $phone2 !== '' ? $phone2 : null,
        ':opening_meter_total' => max(0, $opening_meter_total),
        ':id' => $id
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'زانیاری وەرگر نوێکرایەوە.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'نوێکردنەوە سەرکەوتوو نەبوو.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}

