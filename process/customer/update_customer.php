<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!hasPermission('edit_customer')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$name = $_POST['name'] ?? '';
$mobile1 = $_POST['mobile1'] ?? '';
$mobile2 = $_POST['mobile2'] ?? '';
$opening_debt_usd = floatval($_POST['opening_debt_usd'] ?? 0);
$opening_debt_iqd = floatval($_POST['opening_debt_iqd'] ?? 0);

// Validate required fields
if (empty($name) || empty($mobile1) || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'تکایە هەموو خانە پێویستەکان پڕبکەرەوە']);
    exit;
}

// Only one of opening_debt_usd or opening_debt_iqd should be nonzero
if ($opening_debt_usd > 0) $opening_debt_iqd = 0;
if ($opening_debt_iqd > 0) $opening_debt_usd = 0;

try {
    // Check for duplicate mobile number (excluding current customer)
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE mobile1 = ? AND id != ?");
    $stmt->execute([$mobile1, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی مۆبایل پێشتر تۆمارکراوە!']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE customers SET name = ?, mobile1 = ?, mobile2 = ?, opening_debt_usd = ?, opening_debt_iqd = ? WHERE id = ?");
    $result = $stmt->execute([$name, $mobile1, $mobile2, $opening_debt_usd, $opening_debt_iqd, $id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'کڕیار بەسەرکەوتوویی نوێکرایەوە!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە!']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}
?>
