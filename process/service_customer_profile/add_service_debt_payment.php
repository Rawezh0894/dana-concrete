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
    $customer_id = $_POST['customer_id'] ?? null;
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $paid_usd = $_POST['paid_usd'] ?? 0;
    $paid_iqd = $_POST['paid_iqd'] ?? 0;
    $exchange_rate = $_POST['exchange_rate'] ?? 150000;
    $note = $_POST['note'] ?? '';

    if (!$customer_id || ($paid_usd <= 0 && $paid_iqd <= 0)) {
        echo json_encode(['success' => false, 'message' => 'تکایە بڕی پارەکە دیاری بکە']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO service_debt_payments (customer_id, payment_date, paid_usd, paid_iqd, exchange_rate, note) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$customer_id, $payment_date, $paid_usd, $paid_iqd, $exchange_rate, $note]);

    echo json_encode(['success' => true, 'message' => 'پارەکە بەسەرکەوتوویی وەرگیرا']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
