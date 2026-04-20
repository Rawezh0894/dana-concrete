<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $pdo->beginTransaction();

    $purchase_id = intval($_POST['id'] ?? 0);
    if ($purchase_id <= 0) throw new Exception("Invalid Purchase ID");

    // 1. Get all items in this purchase
    $stmt = $pdo->prepare("SELECT item_id, qty, unit_price_usd FROM inv_purchase_items WHERE purchase_id = ?");
    $stmt->execute([$purchase_id]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $item_id = $item['item_id'];
        $purchase_qty = floatval($item['qty']);
        $purchase_price_usd = floatval($item['unit_price_usd']);

        // 2. Fetch current stock
        $stmt_stock = $pdo->prepare("SELECT current_qty, avg_cost_usd FROM inv_stock WHERE item_id = ? FOR UPDATE");
        $stmt_stock->execute([$item_id]);
        $stock = $stmt_stock->fetch();

        if ($stock) {
            $current_qty = floatval($stock['current_qty']);
            $current_avg_cost = floatval($stock['avg_cost_usd']);

            $new_total_qty = $current_qty - $purchase_qty;
            
            if ($new_total_qty > 0) {
                // Reverse MAP: (TotalValue - ItemValue) / NewQty
                $current_total_value = $current_qty * $current_avg_cost;
                $item_total_value = $purchase_qty * $purchase_price_usd;
                $new_avg_cost = ($current_total_value - $item_total_value) / $new_total_qty;
                
                // If for some reason new_avg_cost is negative (rounding issues), cap at 0
                if ($new_avg_cost < 0) $new_avg_cost = 0;
            } else {
                $new_avg_cost = 0;
                // We allow negative quantity if it happens, to keep math consistent
            }

            $stmt_update = $pdo->prepare("UPDATE inv_stock SET current_qty = ?, avg_cost_usd = ? WHERE item_id = ?");
            $stmt_update->execute([$new_total_qty, $new_avg_cost, $item_id]);
        }
    }

    // 3. Delete records
    $stmt_del_items = $pdo->prepare("DELETE FROM inv_purchase_items WHERE purchase_id = ?");
    $stmt_del_items->execute([$purchase_id]);

    $stmt_del_p = $pdo->prepare("DELETE FROM inv_purchases WHERE id = ?");
    $stmt_del_p->execute([$purchase_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'کڕینەکە بە سەرکەوتوویی سڕایەوە و کۆگا نوێکرایەوە']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'msg' => 'هەڵە: ' . $e->getMessage()]);
}
?>
