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
if (!hasPermission('delete_material')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'Purchase ID is required']);
    exit;
}

try {
    $purchase_id = $_POST['id'];
    
    // Get the receipt number for this purchase
    $stmt = $pdo->prepare("SELECT receipt_number FROM purchase_materials WHERE id = ? LIMIT 1");
    $stmt->execute([$purchase_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        throw new Exception('Purchase not found');
    }
    
    $receipt_number = $result['receipt_number'];
    
    // Get purchase data to revert quantities
    $stmt = $pdo->prepare("
        SELECT material_id, unit_type, quantity, base_quantity 
        FROM purchase_materials 
        WHERE receipt_number = ?
    ");
    $stmt->execute([$receipt_number]);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Revert quantities from materials
    foreach ($purchases as $purchase) {
        // Get material info for unit conversion
        $materialStmt = $pdo->prepare("
            SELECT unit_type, pieces_per_carton, buckets_per_barrel, liters_per_bucket, liters_per_barrel,
                   price_per_piece_usd, price_per_piece_iqd, price_per_bucket_usd, price_per_bucket_iqd,
                   price_per_liter_usd, price_per_liter_iqd, quantity
            FROM list_materials WHERE id = ?
        ");
        $materialStmt->execute([$purchase['material_id']]);
        $materialInfo = $materialStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($materialInfo) {
            // Calculate quantity to subtract based on purchase
            $quantity_to_subtract = 0;
            $purchase_unit_type = $purchase['unit_type'];
            $material_unit_type = $materialInfo['unit_type'] ?? 'دانە';
            
            // Handle different unit type conversions for reverting
            if ($purchase_unit_type === 'کارتۆن' && $material_unit_type === 'کارتۆن' && $materialInfo['pieces_per_carton']) {
                $quantity_to_subtract = $purchase['quantity'] * $materialInfo['pieces_per_carton'];
            } elseif ($purchase_unit_type === 'دانە' && $material_unit_type === 'کارتۆن' && $materialInfo['pieces_per_carton']) {
                $quantity_to_subtract = $purchase['quantity'];
            } elseif ($purchase_unit_type === 'بەرمیل' && $material_unit_type === 'بەرمیل' && $materialInfo['liters_per_barrel']) {
                $quantity_to_subtract = $purchase['quantity'] * $materialInfo['liters_per_barrel'];
            } elseif ($purchase_unit_type === 'دەبە' && $material_unit_type === 'بەرمیل' && $materialInfo['buckets_per_barrel']) {
                $quantity_to_subtract = $purchase['quantity'] * $materialInfo['liters_per_bucket'];
            } elseif ($purchase_unit_type === 'لیتر' && $material_unit_type === 'بەرمیل' && $materialInfo['liters_per_barrel']) {
                $quantity_to_subtract = $purchase['quantity'];
            } elseif ($purchase_unit_type === 'دەبە' && $material_unit_type === 'دەبە' && $materialInfo['liters_per_bucket']) {
                $quantity_to_subtract = $purchase['quantity'] * $materialInfo['liters_per_bucket'];
            } elseif ($purchase_unit_type === 'لیتر' && $material_unit_type === 'دەبە' && $materialInfo['liters_per_bucket']) {
                $quantity_to_subtract = $purchase['quantity'];
            } elseif ($purchase_unit_type === 'لیتر' && $material_unit_type === 'لیتر') {
                $quantity_to_subtract = $purchase['quantity'];
            } elseif ($purchase_unit_type === 'دانە' && $material_unit_type === 'دانە') {
                $quantity_to_subtract = $purchase['quantity'];
            } else {
                $quantity_to_subtract = $purchase['quantity'];
            }
            
            // Subtract the quantity
            $new_quantity = max(0, $materialInfo['quantity'] - $quantity_to_subtract);
            $updateMaterialStmt = $pdo->prepare("
                UPDATE list_materials 
                SET quantity = ? 
                WHERE id = ?
            ");
            $updateMaterialStmt->execute([$new_quantity, $purchase['material_id']]);
        }
    }
    
    // Delete all materials for this receipt number
    $stmt = $pdo->prepare("DELETE FROM purchase_materials WHERE receipt_number = ?");
    $stmt->execute([$receipt_number]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'کڕینەکە بە سەرکەوتووی سڕایەوە'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in delete_purchase.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
