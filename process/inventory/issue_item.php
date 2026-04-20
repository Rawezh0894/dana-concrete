<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $pdo->beginTransaction();

    $item_id = intval($_POST['item_id'] ?? 0);
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
    $qty = floatval($_POST['qty'] ?? 0);
    $unit_used = $_POST['unit_used'] ?? null;
    $issued_date = $_POST['issued_date'] ?? date('Y-m-d');

    if ($qty <= 0) throw new Exception("Quantity must be greater than zero");

    // Fetch item unit info
    $stmt_item = $pdo->prepare("SELECT unit, secondary_unit, conversion_factor FROM inv_items WHERE id = ?");
    $stmt_item->execute([$item_id]);
    $item_data = $stmt_item->fetch();

    $base_qty = $qty;

    if ($item_data && !empty($item_data['secondary_unit']) && $unit_used === $item_data['secondary_unit']) {
        $base_qty = $qty * floatval($item_data['conversion_factor']);
    }

    // 1. Check current stock and price
    $stmt = $pdo->prepare("SELECT current_qty, avg_cost_usd FROM inv_stock WHERE item_id = ? FOR UPDATE");
    $stmt->execute([$item_id]);
    $stock = $stmt->fetch();

    if (!$stock || $stock['current_qty'] < $base_qty) {
        throw new Exception("Insufficient stock available (" . ($stock ? $stock['current_qty'] : 0) . " base units available)");
    }

    $avg_cost_usd = floatval($stock['avg_cost_usd']);

    // 2. Record Issuance
    $stmt = $pdo->prepare("INSERT INTO inv_issuance (item_id, vehicle_id, qty, issued_date, cost_usd_at_time, unit_used) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$item_id, $vehicle_id, $base_qty, $issued_date, $avg_cost_usd, $unit_used]);

    // 3. Deduct from Stock
    $stmt = $pdo->prepare("UPDATE inv_stock SET current_qty = current_qty - ? WHERE item_id = ?");
    $stmt->execute([$base_qty, $item_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'Item issued to vehicle successfully']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
