<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی پارەدان نەدۆزرایەوە']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM service_debt_payments WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'پارەدانەکە بەسەرکەوتوویی سڕایەوە']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
