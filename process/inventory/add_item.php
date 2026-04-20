<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $pdo->beginTransaction();

    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? 'Other';
    $unit = $_POST['unit'] ?? 'pcs';
    $secondary_unit = $_POST['secondary_unit'] ?? null;
    $conversion_factor = $_POST['conversion_factor'] ?? 1;
    
    $opening_qty = floatval($_POST['opening_qty'] ?? 0);
    $opening_cost = floatval($_POST['opening_cost'] ?? 0);
    $opening_currency = $_POST['opening_currency'] ?? 'USD';
    $opening_exchange_rate = floatval($_POST['opening_exchange_rate'] ?? 150000);

    if (empty($name)) throw new Exception("Item name is required");

    // Convert opening cost to USD if in IQD
    $opening_cost_usd = ($opening_currency === 'IQD') ? ($opening_cost / ($opening_exchange_rate / 100)) : $opening_cost;

    $stmt = $pdo->prepare("INSERT INTO inv_items (name, category, unit, secondary_unit, conversion_factor) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category, $unit, empty($secondary_unit) ? null : $secondary_unit, $conversion_factor]);
    $item_id = $pdo->lastInsertId();

    // If opening stock is provided, initialize the stock table
    if ($opening_qty > 0) {
        $stmt_stock = $pdo->prepare("INSERT INTO inv_stock (item_id, current_qty, avg_cost_usd) VALUES (?, ?, ?)");
        $stmt_stock->execute([$item_id, $opening_qty, $opening_cost_usd]);
    } else {
        // Even if 0, better to create the record to avoid complex logic later
        $stmt_stock = $pdo->prepare("INSERT INTO inv_stock (item_id, current_qty, avg_cost_usd) VALUES (?, 0, 0)");
        $stmt_stock->execute([$item_id]);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'Item added successfully', 'id' => $item_id]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
