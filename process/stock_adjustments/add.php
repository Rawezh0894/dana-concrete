<?php
error_reporting(E_ALL & ~E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');
try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $bin_id = intval($_POST['bin_id'] ?? 0);
    $adjustment = floatval($_POST['adjustment'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $price_usd = floatval($_POST['price_usd'] ?? 0);
    $price_iqd = floatval($_POST['price_iqd'] ?? 0);
    $user_id = $_SESSION['user_id'];
    if ($bin_id <= 0 || $adjustment == 0 || $reason === '') {
        echo json_encode(['success' => false, 'message' => 'هەموو خانەکان پڕبکە']);
        exit;
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO stock_adjustments (bin_id, adjustment, reason, user_id, price_usd, price_iqd) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$bin_id, $adjustment, $reason, $user_id, $price_usd, $price_iqd]);
    $stmt2 = $pdo->prepare('UPDATE bins_silos SET amount = amount + ? WHERE id = ?');
    $stmt2->execute([$adjustment, $bin_id]);
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'گۆڕانکاری زیادکرا']);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Exception in stock_adjustments/add.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی گۆڕانکاری!']);
}
