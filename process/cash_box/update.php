<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

$id = $_POST['id'] ?? null;
$date = $_POST['date'] ?? '';
$type = $_POST['type'] ?? '';
$amount_iqd = $_POST['amount_iqd'] ?? 0;
$amount_usd = $_POST['amount_usd'] ?? 0;
$currency = $_POST['currency'] ?? '';
$note = $_POST['note'] ?? '';

if (!$id || !is_numeric($id) || !$date || !$type || !$currency) {
    echo json_encode(['success' => false, 'error' => 'هەموو خانەکان پڕ بکە']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
try {
    $stmt = $pdo->prepare('UPDATE cash_box SET date=?, type=?, amount_iqd=?, amount_usd=?, currency=?, note=? WHERE id=?');
    $stmt->execute([$date, $type, $amount_iqd, $amount_usd, $currency, $note, $id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
