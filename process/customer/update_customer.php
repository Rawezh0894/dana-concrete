<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/_customer_flags.php';

header('Content-Type: application/json; charset=utf-8');

if (!hasPermission('update_customer')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$mobile1 = trim($_POST['mobile1'] ?? '');
$mobile2 = trim($_POST['mobile2'] ?? '');
$opening_debt_usd = floatval($_POST['opening_debt_usd'] ?? 0);
$opening_debt_iqd = floatval($_POST['opening_debt_iqd'] ?? 0);
$is_recipient = customer_post_flag('is_recipient');
$is_company = customer_post_flag('is_company');

if ($name === '' || $mobile1 === '' || $id <= 0) {
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
    $stmt = $pdo->prepare('SELECT id FROM customers WHERE mobile1 = ? AND id != ?');
    $stmt->execute([$mobile1, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی مۆبایل پێشتر تۆمارکراوە!']);
        exit;
    }

    $stmt = $pdo->prepare(
        'UPDATE customers
         SET name = ?, mobile1 = ?, mobile2 = ?, opening_debt_usd = ?, opening_debt_iqd = ?,
             is_recipient = ?, is_company = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $name,
        $mobile1,
        $mobile2,
        $opening_debt_usd,
        $opening_debt_iqd,
        $is_recipient,
        $is_company,
        $id,
    ]);

    // Verify what was actually stored
    $check = $pdo->prepare('SELECT is_company, is_recipient FROM customers WHERE id = ?');
    $check->execute([$id]);
    $stored = $check->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'message' => 'کڕیار بەسەرکەوتوویی نوێکرایەوە!',
        'is_company' => (int)($stored['is_company'] ?? $is_company),
        'is_recipient' => (int)($stored['is_recipient'] ?? $is_recipient),
        'received_is_company' => $is_company,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Exception in update_customer.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'هەڵە لە نوێکردنەوەی کڕیار: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
