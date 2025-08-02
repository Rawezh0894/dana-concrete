<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check permission
if (!hasPermission('view_materials')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

try {
    // Get materials from inventory_materials table
    $stmt = $pdo->prepare("
        SELECT id, name, current_quantity as quantity, currency_type, purchase_price_usd, purchase_price_iqd, unit_type, pieces_per_carton, bags_per_barrel, liters_per_bag, liters_per_barrel, price_per_piece, price_per_liter, price_per_bag
        FROM inventory_materials 
        ORDER BY name ASC
    ");
    $stmt->execute();
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $materials
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_materials.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?> 