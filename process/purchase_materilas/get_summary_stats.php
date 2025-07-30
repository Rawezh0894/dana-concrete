<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    // Get total purchases count
    $stmt = $pdo->query("SELECT COUNT(*) as total_purchases FROM purchase_materials");
    $total_purchases = $stmt->fetch(PDO::FETCH_ASSOC)['total_purchases'];

    // Get total purchase value (sum of USD and IQD values)
    $stmt = $pdo->query("
        SELECT 
            COALESCE(SUM(total_price_usd), 0) as total_value_usd,
            COALESCE(SUM(total_price_iqd), 0) as total_value_iqd
        FROM purchase_materials
    ");
    $purchase_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_purchase_value = floatval($purchase_data['total_value_usd']) + floatval($purchase_data['total_value_iqd']);

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