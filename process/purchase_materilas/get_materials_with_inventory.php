<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Check if user has permission to view materials
if (!hasPermission('view_materials')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

try {
    // Query to get materials with inventory quantities
    $query = "
        SELECT 
            im.id, 
            im.name, 
            im.unit_type, 
            im.pieces_per_carton, 
            im.bags_per_barrel, 
            im.liters_per_bag, 
            im.liters_per_barrel, 
            im.price_per_piece, 
            im.price_per_liter, 
            im.price_per_bag, 
            im.purchase_price_usd, 
            im.purchase_price_iqd,
            -- Get quantities by unit type
            COALESCE(carton_qty.quantity, 0) as carton_quantity,
            COALESCE(piece_qty.quantity, 0) as piece_quantity,
            COALESCE(barrel_qty.quantity, 0) as barrel_quantity,
            COALESCE(bag_qty.quantity, 0) as bag_quantity,
            COALESCE(liter_qty.quantity, 0) as liter_quantity,
            -- Get total quantity across all units
            COALESCE(carton_qty.quantity, 0) + COALESCE(piece_qty.quantity, 0) + 
            COALESCE(barrel_qty.quantity, 0) + COALESCE(bag_qty.quantity, 0) + 
            COALESCE(liter_qty.quantity, 0) as total_quantity
        FROM inventory_materials im
        LEFT JOIN inventory_by_unit carton_qty ON im.id = carton_qty.material_id AND carton_qty.unit_type = 'carton'
        LEFT JOIN inventory_by_unit piece_qty ON im.id = piece_qty.material_id AND piece_qty.unit_type = 'piece'
        LEFT JOIN inventory_by_unit barrel_qty ON im.id = barrel_qty.material_id AND barrel_qty.unit_type = 'barrel'
        LEFT JOIN inventory_by_unit bag_qty ON im.id = bag_qty.material_id AND bag_qty.unit_type = 'bag'
        LEFT JOIN inventory_by_unit liter_qty ON im.id = liter_qty.material_id AND liter_qty.unit_type = 'liter'
        ORDER BY im.name ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert numeric values to proper types
    foreach ($materials as &$material) {
        $material['id'] = (int)$material['id'];
        $material['pieces_per_carton'] = $material['pieces_per_carton'] ? (int)$material['pieces_per_carton'] : null;
        $material['bags_per_barrel'] = $material['bags_per_barrel'] ? (int)$material['bags_per_barrel'] : null;
        $material['liters_per_bag'] = $material['liters_per_bag'] ? (float)$material['liters_per_bag'] : null;
        $material['liters_per_barrel'] = $material['liters_per_barrel'] ? (float)$material['liters_per_barrel'] : null;
        $material['price_per_piece'] = (float)$material['price_per_piece'];
        $material['price_per_liter'] = (float)$material['price_per_liter'];
        $material['price_per_bag'] = (float)$material['price_per_bag'];
        $material['purchase_price_usd'] = (float)$material['purchase_price_usd'];
        $material['purchase_price_iqd'] = (float)$material['purchase_price_iqd'];
        $material['carton_quantity'] = (float)$material['carton_quantity'];
        $material['piece_quantity'] = (float)$material['piece_quantity'];
        $material['barrel_quantity'] = (float)$material['barrel_quantity'];
        $material['bag_quantity'] = (float)$material['bag_quantity'];
        $material['liter_quantity'] = (float)$material['liter_quantity'];
        $material['total_quantity'] = (float)$material['total_quantity'];
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $materials,
        'message' => 'Materials with inventory data retrieved successfully'
    ]);
    
} catch (PDOException $e) {
    // Log error for debugging
    error_log("Database error in get_materials_with_inventory.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred while fetching materials'
    ]);
} catch (Exception $e) {
    // Log error for debugging
    error_log("General error in get_materials_with_inventory.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing the request'
    ]);
}
?> 