<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('update_customer')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

$id = $_POST['id'] ?? '';
$name = $_POST['name'] ?? '';
$mobile1 = $_POST['mobile1'] ?? '';
$mobile2 = $_POST['mobile2'] ?? '';
$opening_debt_usd = isset($_POST['opening_debt_usd']) ? $_POST['opening_debt_usd'] : null;
$opening_debt_iqd = isset($_POST['opening_debt_iqd']) ? $_POST['opening_debt_iqd'] : null;

if (empty($id) || empty($name) || empty($mobile1)) {
    echo json_encode(['success' => false, 'message' => 'تکایە هەموو خانەکان پڕبکەوە']);
    exit;
}

// Prevent duplicate mobile numbers (except for this customer)
if ($mobile2) {
    $sql = "SELECT COUNT(*) FROM customers WHERE id != ? AND (mobile1 = ? OR mobile2 = ? OR mobile1 = ? OR mobile2 = ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $mobile1, $mobile1, $mobile2, $mobile2]);
} else {
    $sql = "SELECT COUNT(*) FROM customers WHERE id != ? AND (mobile1 = ? OR mobile2 = ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $mobile1, $mobile1]);
}
$count = $stmt->fetchColumn();
if ($count > 0) {
    echo json_encode(['success' => false, 'message' => 'ئەم ژمارە مۆبایلە پێشتر تۆمار کراوە']);
    exit;
}

try {
    $fields = "name=?, mobile1=?, mobile2=?";
    $params = [$name, $mobile1, $mobile2];
    if ($opening_debt_usd !== null && $opening_debt_usd !== '') {
        $fields .= ", opening_debt_usd=?";
        $params[] = $opening_debt_usd;
    }
    if ($opening_debt_iqd !== null && $opening_debt_iqd !== '') {
        $fields .= ", opening_debt_iqd=?";
        $params[] = $opening_debt_iqd;
    }
    $fields .= " WHERE id=?";
    $params[] = $id;
    $stmt = $pdo->prepare("UPDATE customers SET $fields");
    $result = $stmt->execute($params);
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'کڕیار نوێکرایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی کڕیار']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕووی دا: ' . $e->getMessage()]);
}
