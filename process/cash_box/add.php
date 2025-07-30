<?php
session_start();
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

// Validate
$date = $_POST['date'] ?? '';
$type = $_POST['type'] ?? '';
$amount_iqd = $_POST['amount_iqd'] ?? 0;
$amount_usd = $_POST['amount_usd'] ?? 0;
$currency = $_POST['currency'] ?? '';
$note = $_POST['note'] ?? '';
$created_by = $_SESSION['user_id'] ?? null;

if (!$date || !$type || !$currency || !$created_by) {
    echo json_encode(['success' => false, 'error' => 'هەموو خانەکان پڕ بکە']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO cash_box (date, type, amount_iqd, amount_usd, currency, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$date, $type, $amount_iqd, $amount_usd, $currency, $note, $created_by]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
