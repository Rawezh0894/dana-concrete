<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!hasPermission('add_cash_box') && !hasPermission('delete_cash_box')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'ڕێگە پێنەدراو']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('DELETE FROM cash_box');
    $stmt->execute();
    $deletedCount = $stmt->rowCount();

    // Remove manual override total so it can be recalculated cleanly
    $stmtSettings = $pdo->prepare("DELETE FROM settings WHERE name = 'cash_box_total_usd_all'");
    $stmtSettings->execute();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'deleted_count' => $deletedCount,
        'message' => 'هەموو مامەڵەکان بە سەرکەوتوویی سڕایەوە'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}

