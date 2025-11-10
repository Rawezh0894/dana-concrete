<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Check permission - allow if user has delete_cash_box permission or is admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && !hasPermission('delete_cash_box'))) {
    echo json_encode(['success' => false, 'error' => 'توانای دەست گەیشتنت نییە بۆ سڕینەوەی هەموو ریکۆردەکان']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

// Get confirmation from POST
$confirm = $_POST['confirm'] ?? false;
if ($confirm !== 'true' && $confirm !== true) {
    echo json_encode(['success' => false, 'error' => 'دڵنیایی پێویستە']);
    exit;
}

try {
    // Get count before deletion
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM cash_box");
    $total_count = $count_stmt->fetchColumn();
    
    // Delete all records
    $stmt = $pdo->prepare("DELETE FROM cash_box");
    $stmt->execute();
    
    $deleted_count = $stmt->rowCount();
    
    // Also reset the manual total if exists
    $reset_stmt = $pdo->prepare("DELETE FROM settings WHERE name = 'cash_box_total_usd_all'");
    $reset_stmt->execute();
    
    echo json_encode([
        'success' => true, 
        'message' => "هەموو $deleted_count ریکۆرد بە سەرکەوتوویی سڕایەوە",
        'deleted_count' => $deleted_count,
        'total_count' => $total_count
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

