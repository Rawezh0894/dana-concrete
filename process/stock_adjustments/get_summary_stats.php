<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    // Get total adjustments count
    $stmt = $pdo->query("SELECT COUNT(*) as total_adjustments FROM stock_adjustments");
    $total_adjustments = $stmt->fetch(PDO::FETCH_ASSOC)['total_adjustments'];

    // Get total additions count
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_additions 
        FROM stock_adjustments 
        WHERE adjustment_type = 'addition'
    ");
    $total_additions = $stmt->fetch(PDO::FETCH_ASSOC)['total_additions'];

    // Get total subtractions count
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_subtractions 
        FROM stock_adjustments 
        WHERE adjustment_type = 'subtraction'
    ");
    $total_subtractions = $stmt->fetch(PDO::FETCH_ASSOC)['total_subtractions'];

    echo json_encode([
        'success' => true,
        'data' => [
            'total_adjustments' => intval($total_adjustments),
            'total_additions' => intval($total_additions),
            'total_subtractions' => intval($total_subtractions)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 