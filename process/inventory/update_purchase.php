<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $pdo->beginTransaction();

    $purchase_id = intval($_POST['purchase_id'] ?? 0);
    if ($purchase_id <= 0) throw new Exception("Invalid Purchase ID");

    // --- PART 1: REVERT OLD PURCHASE FROM STOCK ---
    $stmt_old = $pdo->prepare("SELECT item_id, qty, unit_price_usd FROM inv_purchase_items WHERE purchase_id = ?");
    $stmt_old->execute([$purchase_id]);
    $old_items = $stmt_old->fetchAll();

    foreach ($old_items as $old_item) {
        $item_id = $old_item['item_id'];
        $old_qty = floatval($old_item['qty']);
        $old_price_usd = floatval($old_item['unit_price_usd']);

        $stmt_stock = $pdo->prepare("SELECT current_qty, avg_cost_usd FROM inv_stock WHERE item_id = ? FOR UPDATE");
        $stmt_stock->execute([$item_id]);
        $stock = $stmt_stock->fetch();

        if ($stock) {
            $current_qty = floatval($stock['current_qty']);
            $current_avg_cost = floatval($stock['avg_cost_usd']);
            $new_total_qty = $current_qty - $old_qty;
            
            if ($new_total_qty > 0) {
                $new_avg_cost = (($current_qty * $current_avg_cost) - ($old_qty * $old_price_usd)) / $new_total_qty;
                if ($new_avg_cost < 0) $new_avg_cost = 0;
            } else {
                $new_avg_cost = 0;
            }

            $stmt_upd = $pdo->prepare("UPDATE inv_stock SET current_qty = ?, avg_cost_usd = ? WHERE item_id = ?");
            $stmt_upd->execute([$new_total_qty, $new_avg_cost, $item_id]);
        }
    }

    // --- PART 2: DELETE OLD ITEMS AND UPDATE HEADER ---
    $stmt_del = $pdo->prepare("DELETE FROM inv_purchase_items WHERE purchase_id = ?");
    $stmt_del->execute([$purchase_id]);

    $invoice_number = $_POST['invoice_number'] ?? '';
    $person_id = intval($_POST['person_id'] ?? 0);
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $exchange_rate = floatval($_POST['exchange_rate'] ?? 150000);

    $supplier_name = '';
    if ($person_id > 0) {
        $stmt_p = $pdo->prepare("SELECT name FROM other_expense_persons WHERE id = ?");
        $stmt_p->execute([$person_id]);
        $supplier_name = $stmt_p->fetchColumn();
    }

    $stmt_upd_p = $pdo->prepare("UPDATE inv_purchases SET invoice_number = ?, person_id = ?, supplier_name = ?, purchase_date = ?, exchange_rate = ? WHERE id = ?");
    $stmt_upd_p->execute([$invoice_number, $person_id, $supplier_name, $purchase_date, $exchange_rate, $purchase_id]);

    // --- PART 3: INSERT NEW ITEMS AND APPLY TO STOCK ---
    $items = $_POST['items'] ?? [];
    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $qty = floatval($item['qty']);
        $unit_price = floatval($item['unit_price']);
        $currency = $item['currency'];
        $unit_used = $item['unit_used'] ?? null;

        $stmt_item = $pdo->prepare("SELECT unit, secondary_unit, conversion_factor FROM inv_items WHERE id = ?");
        $stmt_item->execute([$item_id]);
        $item_data = $stmt_item->fetch();

        $base_qty = $qty;
        if ($item_data && !empty($item_data['secondary_unit']) && $unit_used === $item_data['secondary_unit']) {
            $base_qty = $qty * floatval($item_data['conversion_factor']);
        }
        
        $base_unit_price_usd = ($currency === 'IQD') ? ($unit_price / ($exchange_rate / 100)) : $unit_price;
        // If it was secondary unit, price per base unit is divided by factor
        if ($item_data && !empty($item_data['secondary_unit']) && $unit_used === $item_data['secondary_unit']) {
            $base_unit_price_usd = $base_unit_price_usd / floatval($item_data['conversion_factor']);
        }

        $stmt_ins = $pdo->prepare("INSERT INTO inv_purchase_items (purchase_id, item_id, qty, unit_price, currency, unit_price_usd, unit_used) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_ins->execute([$purchase_id, $item_id, $base_qty, $unit_price, $currency, $base_unit_price_usd, $unit_used]);

        // Update Stock
        $stmt_s = $pdo->prepare("SELECT current_qty, avg_cost_usd FROM inv_stock WHERE item_id = ? FOR UPDATE");
        $stmt_s->execute([$item_id]);
        $stock = $stmt_s->fetch();

        if ($stock) {
            $current_qty = floatval($stock['current_qty']);
            $current_avg_cost = floatval($stock['avg_cost_usd']);
            $new_total_qty = $current_qty + $base_qty;
            
            if ($new_total_qty > 0) {
                $new_avg_cost = (($current_qty * $current_avg_cost) + ($base_qty * $base_unit_price_usd)) / $new_total_qty;
            } else {
                $new_avg_cost = $base_unit_price_usd;
            }

            $stmt_upd_s = $pdo->prepare("UPDATE inv_stock SET current_qty = ?, avg_cost_usd = ? WHERE item_id = ?");
            $stmt_upd_s->execute([$new_total_qty, $new_avg_cost, $item_id]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'کڕینەکە بە سەرکەوتوویی دەستکاری کرا و کۆگا نوێکرایەوە']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'msg' => 'هەڵە: ' . $e->getMessage()]);
}
?>
