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
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $paid_usd = $_POST['paid_usd'] ?? 0;
    $paid_iqd = $_POST['paid_iqd'] ?? 0;
    $exchange_rate = $_POST['exchange_rate'] ?? 150000;
    $note = $_POST['note'] ?? '';

    if (!$id || ($paid_usd <= 0 && $paid_iqd <= 0)) {
        echo json_encode(['success' => false, 'message' => 'تکایە بڕی پارەکە دیاری بکە']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE service_debt_payments SET payment_date = ?, paid_usd = ?, paid_iqd = ?, exchange_rate = ?, note = ? WHERE id = ?");
    $stmt->execute([$payment_date, $paid_usd, $paid_iqd, $exchange_rate, $note, $id]);

    echo json_encode(['success' => true, 'message' => 'زانیارییەکان بەسەرکەوتوویی نوێکرانەوە']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
