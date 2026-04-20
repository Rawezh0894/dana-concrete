<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $page = intval($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $total = $pdo->query("SELECT COUNT(*) FROM inv_purchases")->fetchColumn();

    $stmt = $pdo->prepare("SELECT p.*, 
                         (SELECT SUM(qty * unit_price_usd) FROM inv_purchase_items WHERE purchase_id = p.id) as total_usd
                         FROM inv_purchases p 
                         ORDER BY p.purchase_date DESC, p.id DESC
                         LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $purchases = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $purchases, 'total' => $total]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
