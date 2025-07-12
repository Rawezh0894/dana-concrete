<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('delete_customer')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی هەڵە']);
    exit;
}
$id = (int)$_POST['id'];

// Prevent delete if customer has payments or sales
$hasPayments = $pdo->prepare('SELECT COUNT(*) FROM customer_debt_payments WHERE customer_id = ?');
$hasPayments->execute([$id]);
if ($hasPayments->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'ناتوانرێت کڕیار بسڕدرێت چونکە پارەدان بۆ تۆمارکراوە']);
    exit;
}
$hasSales = $pdo->prepare('SELECT COUNT(*) FROM sales WHERE customer_id = ?');
$hasSales->execute([$id]);
if ($hasSales->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'ناتوانرێت کڕیار بسڕدرێت چونکە مامەڵەی فرۆشتن بۆ تۆمارکراوە']);
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount()) {
        echo json_encode(['success' => true, 'message' => 'کڕیار سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'کڕیار نەدۆزرایەوە']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕووی دا: ' . $e->getMessage()]);
}
