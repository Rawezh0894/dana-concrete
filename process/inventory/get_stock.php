<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $page = intval($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Total Count
    $total = $pdo->query("SELECT COUNT(*) FROM inv_items")->fetchColumn();

    $stmt = $pdo->prepare("SELECT i.id as item_id, i.name, i.category, i.unit, i.secondary_unit, i.conversion_factor,
                         IFNULL(s.current_qty, 0) as current_qty, 
                         IFNULL(s.avg_cost_usd, 0) as avg_cost_usd, 
                         s.last_updated,
                         (SELECT COUNT(*) FROM inv_issuance WHERE item_id = i.id) as issuance_count
                         FROM inv_items i 
                         LEFT JOIN inv_stock s ON i.id = s.item_id 
                         ORDER BY i.name ASC
                         LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $stock = $stmt->fetchAll();

    // Summary Stats
    $stats = $pdo->query("SELECT 
                            COUNT(*) as total_items,
                            SUM(IFNULL(s.current_qty, 0) * IFNULL(s.avg_cost_usd, 0)) as total_value,
                            SUM(CASE WHEN IFNULL(s.current_qty, 0) <= 5 THEN 1 ELSE 0 END) as low_stock
                         FROM inv_items i
                         LEFT JOIN inv_stock s ON i.id = s.item_id")->fetch();

    echo json_encode([
        'success' => true, 
        'data' => $stock, 
        'total' => $total,
        'stats' => $stats
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
