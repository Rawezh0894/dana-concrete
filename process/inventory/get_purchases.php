<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT p.*, 
                         (SELECT SUM(qty * unit_price_usd) FROM inv_purchase_items WHERE purchase_id = p.id) as total_usd
                         FROM inv_purchases p 
                         ORDER BY p.purchase_date DESC, p.id DESC");
    $purchases = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $purchases]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
