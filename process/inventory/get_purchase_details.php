<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) throw new Exception("Invalid ID");

    $stmt = $pdo->prepare("SELECT * FROM inv_purchases WHERE id = ?");
    $stmt->execute([$id]);
    $purchase = $stmt->fetch();

    if (!$purchase) throw new Exception("Purchase not found");

    $stmt_items = $pdo->prepare("SELECT * FROM inv_purchase_items WHERE purchase_id = ?");
    $stmt_items->execute([$id]);
    $items = $stmt_items->fetchAll();

    echo json_encode([
        'success' => true, 
        'purchase' => $purchase, 
        'items' => $items
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
