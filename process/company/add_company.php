<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !hasPermission('add_company')) {
    echo json_encode(['success' => false, 'message' => 'دەسەڵات نییە!']);
    exit;
}
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

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'ناوی کۆمپانیا پێویستە!']);
    exit;
}
// Check for duplicate company name
$stmt = $pdo->prepare('SELECT id FROM company WHERE name = ?');
$stmt->execute([$name]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'ئەم ناوی کۆمپانیا پێشتر تۆمارکراوە!']);
    exit;
}
$stmt = $pdo->prepare('INSERT INTO company (name, debt_usd, debt_iqd, opening_debt_usd, opening_debt_iqd, currency_type) VALUES (?, ?, ?, ?, ?, ?)');
$ok = $stmt->execute([$name, $debt_usd, $debt_iqd, $opening_debt_usd, $opening_debt_iqd, $currency_type]);
if ($ok) {
    echo json_encode(['success' => true, 'message' => 'کۆمپانیا زیادکرا!']);
} else {
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن!']);
}
