<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('add_customer')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

$name = $_POST['name'] ?? '';
$mobile1 = $_POST['mobile1'] ?? '';
$mobile2 = $_POST['mobile2'] ?? '';
$debt_usd = $_POST['debt_usd'] ?? 0;
$debt_iqd = $_POST['debt_iqd'] ?? 0;
$opening_debt_usd = $_POST['opening_debt_usd'] ?? 0;
$opening_debt_iqd = $_POST['opening_debt_iqd'] ?? 0;

if (empty($name) || empty($mobile1)) {
    echo json_encode(['success' => false, 'message' => 'تکایە ناو و ژمارە مۆبایلی یەکەم پڕبکەوە']);
    exit;
}

// Check for duplicate customer name
$name_trimmed = trim(mb_strtolower($name));
$stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE TRIM(LOWER(name)) = ?");
$stmt->execute([$name_trimmed]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'ئەم ناوی کڕیار پێشتر تۆمارکراوە']);
    exit;
}

// Check for duplicate mobile numbers
if ($mobile2) {
    $sql = "SELECT COUNT(*) FROM customers WHERE mobile1 = ? OR mobile2 = ? OR mobile1 = ? OR mobile2 = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mobile1, $mobile1, $mobile2, $mobile2]);
} else {
    $sql = "SELECT COUNT(*) FROM customers WHERE mobile1 = ? OR mobile2 = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mobile1, $mobile1]);
}
$count = $stmt->fetchColumn();
if ($count > 0) {
    echo json_encode(['success' => false, 'message' => 'ئەم ژمارە مۆبایلە پێشتر تۆمار کراوە']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO customers (name, mobile1, mobile2, debt_usd, debt_iqd, opening_debt_usd, opening_debt_iqd) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([$name, $mobile1, $mobile2, $debt_usd, $debt_iqd, $opening_debt_usd, $opening_debt_iqd]);
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'کڕیار بە سەرکەوتوویی زیادکرا']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنەوەی کڕیار']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕووی دا: ' . $e->getMessage()]);
}
