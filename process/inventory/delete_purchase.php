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
    if ($id <= 0) throw new Exception("Invalid Purchase ID");

    // 1. Get items in this purchase to revert stock
    $stmt_items = $pdo->prepare("SELECT item_id, qty FROM inv_purchase_items WHERE purchase_id = ?");
    $stmt_items->execute([$id]);
    $items = $stmt_items->fetchAll();

    foreach ($items as $item) {
        $item_id = $item['item_id'];
        $qty = floatval($item['qty']);

        // Check if current stock allows deletion
        $stmt_stock = $pdo->prepare("SELECT current_qty FROM inv_stock WHERE item_id = ? FOR UPDATE");
        $stmt_stock->execute([$item_id]);
        $stock = $stmt_stock->fetch();

        if (!$stock || $stock['current_qty'] < $qty) {
            // We could block here, or just allow it and let stock go negative. 
            // Blocking is safer.
            $item_name = $pdo->query("SELECT name FROM inv_items WHERE id = $item_id")->fetchColumn();
            throw new Exception("ناتوانرێت ئەم کڕینە بسڕێتەوە چونکە بەشێک لەم کاڵایە (${item_name}) بەکارهێنراوە و لە کۆکا کەمترە لەو بڕەی کڕاوە.");
        }

        // Deduct from stock
        $stmt_deduct = $pdo->prepare("UPDATE inv_stock SET current_qty = current_qty - ? WHERE item_id = ?");
        $stmt_deduct->execute([$qty, $item_id]);
    }

    // 2. Delete detail and master
    $pdo->prepare("DELETE FROM inv_purchase_items WHERE purchase_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM inv_purchases WHERE id = ?")->execute([$id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'کڕینەکە بە سەرکەوتوویی سڕایەوە و بڕی کاڵاکان لە کۆگا کەمکرانەوە']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
