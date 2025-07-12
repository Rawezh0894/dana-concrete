<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !hasPermission('edit_company')) {
    echo json_encode(['success' => false, 'message' => 'دەسەڵات نییە!']);
    exit;
}
$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$debt_usd = floatval($_POST['debt_usd'] ?? 0);
$debt_iqd = floatval($_POST['debt_iqd'] ?? 0);
$opening_debt_usd = floatval($_POST['opening_debt_usd'] ?? 0);
$opening_debt_iqd = floatval($_POST['opening_debt_iqd'] ?? 0);
$currency_type = $_POST['currency_type'] ?? 'دینار';

// Only one of debt_usd or debt_iqd should be nonzero
if ($debt_usd > 0) $debt_iqd = 0;
if ($debt_iqd > 0) $debt_usd = 0;
if ($opening_debt_usd > 0) $opening_debt_iqd = 0;
if ($opening_debt_iqd > 0) $opening_debt_usd = 0;

if (!$id || !$name) {
    echo json_encode(['success' => false, 'message' => 'هەموو خانەکان پڕبکەوە!']);
    exit;
}
// Check for duplicate name (except self)
$stmt = $pdo->prepare('SELECT id FROM company WHERE name = ? AND id != ?');
$stmt->execute([$name, $id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'ئەم ناوە پێشتر تۆمارکراوە!']);
    exit;
}
$stmt = $pdo->prepare('UPDATE company SET name = ?, debt_usd = ?, debt_iqd = ?, opening_debt_usd = ?, opening_debt_iqd = ?, currency_type = ? WHERE id = ?');
$ok = $stmt->execute([$name, $debt_usd, $debt_iqd, $opening_debt_usd, $opening_debt_iqd, $currency_type, $id]);
if ($ok) {
    echo json_encode(['success' => true, 'message' => 'زانیاری کۆمپانیا نوێکرایەوە!']);
} else {
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە!']);
}
