<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT i.name, i.category, i.unit, s.current_qty, s.avg_cost_usd, s.last_updated 
                         FROM inv_stock s 
                         JOIN inv_items i ON s.item_id = i.id 
                         ORDER BY i.name ASC");
    $stock = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $stock]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
