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
    $supplier_name = $_POST['supplier_name'] ?? '';
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $exchange_rate = floatval($_POST['exchange_rate'] ?? 150000); // 100 USD rate

    // 1. Insert Purchase Header
    $stmt = $pdo->prepare("INSERT INTO inv_purchases (invoice_number, supplier_name, purchase_date, exchange_rate) VALUES (?, ?, ?, ?)");
    $stmt->execute([$invoice_number, $supplier_name, $purchase_date, $exchange_rate]);
    $purchase_id = $pdo->lastInsertId();

    $items = $_POST['items'] ?? []; // Expected format: Array of {item_id, qty, unit_price, currency}

    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $qty = floatval($item['qty']);
        $unit_price = floatval($item['unit_price']);
        $currency = $item['currency']; // 'USD' or 'IQD'

        // 2. Standardize to USD
        $unit_price_usd = ($currency === 'IQD') ? ($unit_price / ($exchange_rate / 100)) : $unit_price;

        // 3. Record Purchase Item
        $stmt = $pdo->prepare("INSERT INTO inv_purchase_items (purchase_id, item_id, qty, unit_price, currency, unit_price_usd) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$purchase_id, $item_id, $qty, $unit_price, $currency, $unit_price_usd]);

        // 4. Update Stock and Moving Average Price (MAP)
        // Check current stock
        $stmt = $pdo->prepare("SELECT current_qty, avg_cost_usd FROM inv_stock WHERE item_id = ? FOR UPDATE");
        $stmt->execute([$item_id]);
        $stock = $stmt->fetch();

        if ($stock) {
            $current_qty = floatval($stock['current_qty']);
            $current_avg_cost = floatval($stock['avg_cost_usd']);

            // Calculate new MAP
            $new_total_qty = $current_qty + $qty;
            if ($new_total_qty > 0) {
                $new_avg_cost = (($current_qty * $current_avg_cost) + ($qty * $unit_price_usd)) / $new_total_qty;
            } else {
                $new_avg_cost = $unit_price_usd;
            }

            // Update inv_stock
            $stmt = $pdo->prepare("UPDATE inv_stock SET current_qty = ?, avg_cost_usd = ? WHERE item_id = ?");
            $stmt->execute([$new_total_qty, $new_avg_cost, $item_id]);
        } else {
            // First time entry for this item
            $stmt = $pdo->prepare("INSERT INTO inv_stock (item_id, current_qty, avg_cost_usd) VALUES (?, ?, ?)");
            $stmt->execute([$item_id, $qty, $unit_price_usd]);
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
