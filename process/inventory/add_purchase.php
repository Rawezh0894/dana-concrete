<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $pdo->beginTransaction();

    $invoice_number = $_POST['invoice_number'] ?? '';
    $person_id = intval($_POST['person_id'] ?? 0);
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $exchange_rate = floatval($_POST['exchange_rate'] ?? 150000); // 100 USD rate

    $supplier_name = '';
    if ($person_id > 0) {
        $stmt_p = $pdo->prepare("SELECT name FROM other_expense_persons WHERE id = ?");
        $stmt_p->execute([$person_id]);
        $supplier_name = $stmt_p->fetchColumn();
    }

    // 1. Insert Purchase Header
    $stmt = $pdo->prepare("INSERT INTO inv_purchases (invoice_number, person_id, supplier_name, purchase_date, exchange_rate) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$invoice_number, $person_id, $supplier_name, $purchase_date, $exchange_rate]);
    $purchase_id = $pdo->lastInsertId();

    $items = $_POST['items'] ?? []; // Expected format: Array of {item_id, qty, unit_price, currency, unit_used}

    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $qty = floatval($item['qty']);
        $unit_price = floatval($item['unit_price']);
        $currency = $item['currency']; // 'USD' or 'IQD'
        $unit_used = $item['unit_used'] ?? null;

        // Fetch item unit info
        $stmt_item = $pdo->prepare("SELECT unit, secondary_unit, conversion_factor FROM inv_items WHERE id = ?");
        $stmt_item->execute([$item_id]);
        $item_data = $stmt_item->fetch();

        $base_qty = $qty;
        $base_unit_price = $unit_price;

        if ($item_data && !empty($item_data['secondary_unit']) && $unit_used === $item_data['secondary_unit']) {
            $base_qty = $qty * floatval($item_data['conversion_factor']);
            $base_unit_price = $unit_price / floatval($item_data['conversion_factor']);
        }

        // 2. Standardize to USD
        $base_unit_price_usd = ($currency === 'IQD') ? ($base_unit_price / ($exchange_rate / 100)) : $base_unit_price;

        // 3. Record Purchase Item (store both used and normalized)
        $stmt = $pdo->prepare("INSERT INTO inv_purchase_items (purchase_id, item_id, qty, unit_price, currency, unit_price_usd, unit_used) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$purchase_id, $item_id, $base_qty, $base_unit_price, $currency, $base_unit_price_usd, $unit_used]);

        // 4. Update Stock and Moving Average Price (MAP)
        $stmt = $pdo->prepare("SELECT current_qty, avg_cost_usd FROM inv_stock WHERE item_id = ? FOR UPDATE");
        $stmt->execute([$item_id]);
        $stock = $stmt->fetch();

        if ($stock) {
            $current_qty = floatval($stock['current_qty']);
            $current_avg_cost = floatval($stock['avg_cost_usd']);

            $new_total_qty = $current_qty + $base_qty;
            if ($new_total_qty > 0) {
                $new_avg_cost = (($current_qty * $current_avg_cost) + ($base_qty * $base_unit_price_usd)) / $new_total_qty;
            } else {
                $new_avg_cost = $base_unit_price_usd;
            }

            $stmt = $pdo->prepare("UPDATE inv_stock SET current_qty = ?, avg_cost_usd = ? WHERE item_id = ?");
            $stmt->execute([$new_total_qty, $new_avg_cost, $item_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO inv_stock (item_id, current_qty, avg_cost_usd) VALUES (?, ?, ?)");
            $stmt->execute([$item_id, $base_qty, $base_unit_price_usd]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'Purchase recorded successfully']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'msg' => 'Error: ' . $e->getMessage()]);
}
?>
