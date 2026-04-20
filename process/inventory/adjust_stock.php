<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $item_id = intval($_POST['item_id'] ?? 0);
    $new_qty = floatval($_POST['new_qty'] ?? 0);
    $unit_used = $_POST['unit_used'] ?? null;
    $avg_cost_usd = isset($_POST['avg_cost_usd']) && $_POST['avg_cost_usd'] !== '' ? floatval($_POST['avg_cost_usd']) : null;

    // Fetch item unit info
    $stmt_item = $pdo->prepare("SELECT unit, secondary_unit, conversion_factor FROM inv_items WHERE id = ?");
    $stmt_item->execute([$item_id]);
    $item_data = $stmt_item->fetch();

    $base_qty = $new_qty;
    if ($item_data && !empty($item_data['secondary_unit']) && $unit_used === $item_data['secondary_unit']) {
        $base_qty = $new_qty * floatval($item_data['conversion_factor']);
    }

    // Update inv_stock
    $stmt = $pdo->prepare("SELECT item_id FROM inv_stock WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        $query = "UPDATE inv_stock SET current_qty = ?";
        $params = [$base_qty];
        if ($avg_cost_usd !== null) {
            $query .= ", avg_cost_usd = ?";
            $params[] = $avg_cost_usd;
        }
        $query .= " WHERE item_id = ?";
        $params[] = $item_id;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("INSERT INTO inv_stock (item_id, current_qty, avg_cost_usd) VALUES (?, ?, ?)");
        $stmt->execute([$item_id, $base_qty, $avg_cost_usd ?? 0]);
    }

    echo json_encode(['success' => true, 'msg' => 'Stock adjusted successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
