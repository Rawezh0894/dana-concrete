<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/_customer_flags.php';

header('Content-Type: application/json; charset=utf-8');

if (!hasPermission('add_customer')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$mobile1 = trim($_POST['mobile1'] ?? '');
$mobile2 = trim($_POST['mobile2'] ?? '');
$opening_debt_usd = floatval($_POST['opening_debt_usd'] ?? 0);
$opening_debt_iqd = floatval($_POST['opening_debt_iqd'] ?? 0);
$is_recipient = customer_post_flag('is_recipient');
$is_company = customer_post_flag('is_company');

if ($name === '' || $mobile1 === '') {
    echo json_encode(['success' => false, 'message' => 'تکایە هەموو خانە پێویستەکان پڕبکەرەوە']);
    exit;
}

if ($opening_debt_usd > 0) {
    $opening_debt_iqd = 0;
}
if ($opening_debt_iqd > 0) {
    $opening_debt_usd = 0;
}

try {
    $stmt = $pdo->prepare('SELECT id FROM customers WHERE mobile1 = ?');
    $stmt->execute([$mobile1]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی مۆبایل پێشتر تۆمارکراوە!']);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO customers (name, mobile1, mobile2, opening_debt_usd, opening_debt_iqd, is_recipient, is_company)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $name,
        $mobile1,
        $mobile2,
        $opening_debt_usd,
        $opening_debt_iqd,
        $is_recipient,
        $is_company,
    ]);

    $newId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'کڕیار بەسەرکەوتوویی زیادکرا!',
        'id' => $newId,
        'is_company' => $is_company,
        'is_recipient' => $is_recipient,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Exception in add_customer.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'هەڵە لە زیادکردنی کڕیار: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
