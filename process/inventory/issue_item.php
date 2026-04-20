<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $pdo->beginTransaction();
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
    $issued_date = $_POST['issued_date'] ?? date('Y-m-d');
    $items = $_POST['items'] ?? [];

    if (empty($items)) {
        throw new Exception("No items provided for issuance");
    }

    // Prepare statements outside the loop
    $stmt_item = $pdo->prepare("SELECT name, unit, secondary_unit, conversion_factor FROM inv_items WHERE id = ?");
    $stmt_stock = $pdo->prepare("SELECT current_qty, avg_cost_usd FROM inv_stock WHERE item_id = ? FOR UPDATE");
    $stmt_insert = $pdo->prepare("INSERT INTO inv_issuance (item_id, vehicle_id, qty, issued_date, cost_usd_at_time, unit_used) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_update_stock = $pdo->prepare("UPDATE inv_stock SET current_qty = current_qty - ? WHERE item_id = ?");

    foreach ($items as $entry) {
        $item_id = intval($entry['item_id'] ?? 0);
        $qty = floatval($entry['qty'] ?? 0);
        $unit_used = $entry['unit_used'] ?? null;

        if ($item_id <= 0 || $qty <= 0) continue;

        // Fetch item unit info
        $stmt_item->execute([$item_id]);
        $item_data = $stmt_item->fetch();
        if (!$item_data) continue;

        $base_qty = $qty;
        if (!empty($item_data['secondary_unit']) && $unit_used === $item_data['secondary_unit']) {
            $base_qty = $qty * floatval($item_data['conversion_factor']);
        }

        // 1. Check current stock and price
        $stmt_stock->execute([$item_id]);
        $stock = $stmt_stock->fetch();

        if (!$stock || $stock['current_qty'] < $base_qty) {
            throw new Exception("بڕی پێویست لە کۆگادا نییە بۆ: " . $item_data['name'] . " (بەردەست: " . ($stock ? $stock['current_qty'] : 0) . ")");
        }

        $avg_cost_usd = floatval($stock['avg_cost_usd']);

        // 2. Record Issuance
        $stmt_insert->execute([$item_id, $vehicle_id, $base_qty, $issued_date, $avg_cost_usd, $unit_used]);

        // 3. Deduct from Stock
        $stmt_update_stock->execute([$base_qty, $item_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'هەموو کاڵاکان بە سەرکەوتوویی تەرخانکران بۆ سەیارەکە']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
