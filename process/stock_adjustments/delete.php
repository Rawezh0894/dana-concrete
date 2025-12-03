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
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID پێویستە']);
        exit;
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT bin_id, adjustment FROM stock_adjustments WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'گۆڕانکاری نەدۆزرایەوە']);
        exit;
    }
    $bin_id = $row['bin_id'];
    $adjustment = $row['adjustment'];
    $stmt2 = $pdo->prepare('DELETE FROM stock_adjustments WHERE id = ?');
    $stmt2->execute([$id]);
    $stmt3 = $pdo->prepare('UPDATE bins_silos SET amount = amount - ? WHERE id = ?');
    $stmt3->execute([$adjustment, $bin_id]);
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'گۆڕانکاری سڕایەوە']);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Exception in stock_adjustments/delete.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی گۆڕانکاری!']);
}
