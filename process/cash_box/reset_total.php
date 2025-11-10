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

try {
    // Remove saved total from settings (reset to calculated value)
    $stmt = $pdo->prepare("DELETE FROM settings WHERE name = 'cash_box_total_usd_all'");
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'کۆی پارە بە سەرکەوتوویی سفر کرا']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

