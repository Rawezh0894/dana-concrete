<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT i.id as item_id, i.name, i.category, i.unit, i.secondary_unit, i.conversion_factor,
                         IFNULL(s.current_qty, 0) as current_qty, 
                         IFNULL(s.avg_cost_usd, 0) as avg_cost_usd, 
                         s.last_updated,
                         (SELECT COUNT(*) FROM inv_issuance WHERE item_id = i.id) as issuance_count
                         FROM inv_items i 
                         LEFT JOIN inv_stock s ON i.id = s.item_id 
                         ORDER BY i.name ASC");
    $stock = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $stock]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
