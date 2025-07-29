<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    // Get total purchases count
    $stmt = $pdo->query("SELECT COUNT(*) as total_purchases FROM purchase_materials");
    $total_purchases = $stmt->fetch(PDO::FETCH_ASSOC)['total_purchases'];

    // Get total purchase value
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(total_price), 0) as total_value 
        FROM purchase_materials
    ");
    $total_purchase_value = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'];

    // Get total suppliers count
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT person_id) as total_suppliers 
        FROM purchase_materials
    ");
    $total_suppliers = $stmt->fetch(PDO::FETCH_ASSOC)['total_suppliers'];

    echo json_encode([
        'success' => true,
        'data' => [
            'total_purchases' => intval($total_purchases),
            'total_purchase_value' => floatval($total_purchase_value),
            'total_suppliers' => intval($total_suppliers)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 