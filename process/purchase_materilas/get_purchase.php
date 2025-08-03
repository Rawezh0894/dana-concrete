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

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Purchase ID is required']);
    exit;
}

try {
    $purchase_id = $_GET['id'];
    
    // Get purchase details (first record for this receipt) with unit system support
    $stmt = $pdo->prepare("
        SELECT 
            pm.id,
            pm.receipt_number,
            pm.person_id,
            pm.purchase_date,
            pm.currency_type,
            pm.unit_type,
            pm.notes,
            pm.transfer_loss,
            pm.other_loss,
            pm.usd_to_iqd_rate,
            oep.name as person_name
        FROM purchase_materials pm
        LEFT JOIN other_expense_persons oep ON pm.person_id = oep.id
        WHERE pm.id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$purchase_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$purchase) {
        echo json_encode(['success' => false, 'error' => 'Purchase not found']);
        exit;
    }
    
    // Get ALL materials for this receipt number with complete unit system support
    $stmt = $pdo->prepare("
        SELECT 
            pm.material_id,
            pm.unit_type,
            pm.quantity,
            pm.price_per_unit_usd,
            pm.price_per_unit_iqd,
            pm.total_price_usd,
            pm.total_price_iqd,
            pm.base_quantity,
            pm.base_price_per_unit_usd,
            pm.base_price_per_unit_iqd,
            lm.name as material_name,
            lm.unit_type as material_unit_type,
            lm.pieces_per_carton,
            lm.buckets_per_barrel,
            lm.liters_per_bucket,
            lm.liters_per_barrel,
            lm.price_per_piece_usd,
            lm.price_per_piece_iqd,
            lm.price_per_bucket_usd,
            lm.price_per_bucket_iqd,
            lm.price_per_liter_usd,
            lm.price_per_liter_iqd
        FROM purchase_materials pm
        LEFT JOIN list_materials lm ON pm.material_id = lm.id
        WHERE pm.receipt_number = ?
        ORDER BY pm.id ASC
    ");
    
    $stmt->execute([$purchase['receipt_number']]);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $purchase['materials'] = $materials;
    
    echo json_encode([
        'success' => true,
        'data' => $purchase
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_purchase.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 