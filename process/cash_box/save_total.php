<?php
session_start();
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

$total_usd_all = isset($_POST['total_usd_all']) ? floatval($_POST['total_usd_all']) : 0;

try {
    // Check if setting exists
    $stmt_check = $pdo->prepare("SELECT id FROM settings WHERE name = 'cash_box_total_usd_all' LIMIT 1");
    $stmt_check->execute();
    $existing = $stmt_check->fetch();
    
    if ($existing) {
        // Update existing
        $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE name = 'cash_box_total_usd_all'");
        $stmt->execute([strval($total_usd_all)]);
    } else {
        // Insert new
        $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('cash_box_total_usd_all', ?)");
        $stmt->execute([strval($total_usd_all)]);
    }
    
    echo json_encode(['success' => true, 'message' => 'کۆی پارە بە سەرکەوتوویی پاشەکەوت کرا']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

