<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

$rate = isset($_POST['usd_iqd_rate']) ? trim($_POST['usd_iqd_rate']) : '';
if (!is_numeric($rate) || $rate < 10000) {
    echo json_encode(['success' => false, 'error' => 'نرخی دۆلار دروست نییە.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('usd_iqd_rate', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
    $stmt->execute([$rate]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()]);
} 