<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !hasPermission('delete_company')) {
    echo json_encode(['success' => false, 'message' => 'دەسەڵات نییە!']);
    exit;
}
$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی کۆمپانیا نادروستە!']);
    exit;
}
// Check for related purchases
$stmt = $pdo->prepare('SELECT COUNT(*) FROM purchases WHERE company_id = ?');
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'ناتوانرێت کۆمپانیا بسڕدرێت: مامەڵەی کڕین یان قەرز پێوە تۆمارکراوە!']);
    exit;
}
// Check for related debts
$stmt = $pdo->prepare('SELECT COUNT(*) FROM debt_payments WHERE company_id = ?');
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'ناتوانرێت کۆمپانیا بسڕدرێت: مامەڵەی کڕین یان قەرز پێوە تۆمارکراوە!']);
    exit;
}
$stmt = $pdo->prepare('DELETE FROM company WHERE id = ?');
$ok = $stmt->execute([$id]);
if ($ok) {
    echo json_encode(['success' => true, 'message' => 'کۆمپانیا سڕایەوە!']);
} else {
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە!']);
}
