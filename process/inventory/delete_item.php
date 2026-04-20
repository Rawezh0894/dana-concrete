<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $pdo->beginTransaction();

    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) throw new Exception("Invalid Item ID");

    // Check if item is used for vehicle maintenance (as requested by user)
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM inv_issuance WHERE item_id = ?");
    $stmt_check->execute([$id]);
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception("ئەم کاڵایە ناتوانرێت بسڕێتەوە چونکە بۆ سەیارە بەکارهاتووە");
    }

    // Delete from stock and items
    $pdo->prepare("DELETE FROM inv_stock WHERE item_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM inv_purchase_items WHERE item_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM inv_items WHERE id = ?")->execute([$id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'کاڵاکە بە سەرکەوتوویی سڕایەوە']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
