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
if (!hasPermission('add_material')) {
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

try {
    // Validate required fields
    $required_fields = ['receipt_number', 'person_id', 'purchase_date', 'currency_type', 'payment_type'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Check if receipt number already exists
    $stmt = $pdo->prepare("SELECT id FROM purchase_materials WHERE receipt_number = ? LIMIT 1");
    $stmt->execute([$_POST['receipt_number']]);
    if ($stmt->fetch()) {
        throw new Exception("ژمارەی پسووڵە دووبارەیە، تکایە ژمارەیەکی تر هەڵبژێرە");
    }
    
    // Get optional fields
    $transfer_loss = $_POST['transfer_loss'] ?? 0;
    $other_loss = $_POST['other_loss'] ?? 0;
    $usd_to_iqd_rate = $_POST['usd_to_iqd_rate'] ?? 0;
    $paid_amount_usd = $_POST['paid_amount_usd'] ?? 0;
    $paid_amount_iqd = $_POST['paid_amount_iqd'] ?? 0;
    $remaining_amount_usd = $_POST['remaining_amount_usd'] ?? 0;
    $remaining_amount_iqd = $_POST['remaining_amount_iqd'] ?? 0;
    
    // Validate materials data
    if (!isset($_POST['materials']) || empty($_POST['materials'])) {
        throw new Exception('At least one material is required');
    }
    
    $materials = json_decode($_POST['materials'], true);
    if (!is_array($materials) || empty($materials)) {
        throw new Exception('Invalid materials data');
    }
    
    // Validate each material
    foreach ($materials as $material) {
        if (!isset($material['material_id']) || !isset($material['quantity']) || 
            !isset($material['price_per_unit_usd']) || !isset($material['price_per_unit_iqd']) ||
            !isset($material['unit_type'])) {
            throw new Exception('Invalid material data structure');
        }
        
        if (empty($material['material_id']) || $material['quantity'] <= 0) {
            throw new Exception('Invalid material quantity or ID');
        }
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert main purchase record with unit system support
    $stmt = $pdo->prepare("
        INSERT INTO purchase_materials 
        (receipt_number, material_id, person_id, unit_type, quantity, price_per_unit_usd, price_per_unit_iqd, 
         total_price_usd, total_price_iqd, currency_type, payment_type, paid_amount_usd, paid_amount_iqd,
         remaining_amount_usd, remaining_amount_iqd, purchase_date, notes, transfer_loss, other_loss, 
         usd_to_iqd_rate, base_quantity, base_price_per_unit_usd, base_price_per_unit_iqd, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $total_usd = 0;
    $total_iqd = 0;
    
    foreach ($materials as $material) {
        // Get material info for unit conversion
        $materialStmt = $pdo->prepare("
            SELECT unit_type, pieces_per_carton, buckets_per_barrel, liters_per_bucket, liters_per_barrel,
                   price_per_piece_usd, price_per_piece_iqd, price_per_bucket_usd, price_per_bucket_iqd,
                   price_per_liter_usd, price_per_liter_iqd, quantity
            FROM list_materials WHERE id = ?
        ");
        $materialStmt->execute([$material['material_id']]);
        $materialInfo = $materialStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$materialInfo) {
            throw new Exception('Material not found');
        }
        
        // Calculate base quantity and base prices based on unit type
        $base_quantity = $material['quantity'];
        $base_price_per_unit_usd = $material['price_per_unit_usd'];
        $base_price_per_unit_iqd = $material['price_per_unit_iqd'];
        
        // Convert to base units for inventory tracking
        $purchase_unit_type = $material['unit_type'];
        $material_unit_type = $materialInfo['unit_type'] ?? 'دانە';
        
        // Calculate quantity to add to inventory based on unit type
        $quantity_to_add = 0;
        
        // Handle different unit type conversions
        if ($purchase_unit_type === 'کارتۆن' && $material_unit_type === 'کارتۆن' && $materialInfo['pieces_per_carton']) {
            // Purchasing cartons, convert to pieces for base tracking
            $base_quantity = $material['quantity'] * $materialInfo['pieces_per_carton'];
            $base_price_per_unit_usd = $material['price_per_unit_usd'] / $materialInfo['pieces_per_carton'];
            $base_price_per_unit_iqd = $material['price_per_unit_iqd'] / $materialInfo['pieces_per_carton'];
            $quantity_to_add = $material['quantity'] * $materialInfo['pieces_per_carton']; // Add pieces to inventory
        } elseif ($purchase_unit_type === 'دانە' && $material_unit_type === 'کارتۆن' && $materialInfo['pieces_per_carton']) {
            // Purchasing pieces from carton-based material
            $base_quantity = $material['quantity'];
            $base_price_per_unit_usd = $material['price_per_unit_usd'];
            $base_price_per_unit_iqd = $material['price_per_unit_iqd'];
            $quantity_to_add = $material['quantity']; // Add pieces to inventory
        } elseif ($purchase_unit_type === 'بەرمیل' && $material_unit_type === 'بەرمیل') {
            // Purchasing barrels, convert to liters for base tracking
            if ($materialInfo['liters_per_barrel']) {
                $base_quantity = $material['quantity'] * $materialInfo['liters_per_barrel'];
                $base_price_per_unit_usd = $material['price_per_unit_usd'] / $materialInfo['liters_per_barrel'];
                $base_price_per_unit_iqd = $material['price_per_unit_iqd'] / $materialInfo['liters_per_barrel'];
                $quantity_to_add = $material['quantity'] * $materialInfo['liters_per_barrel']; // Add liters to inventory
            }
        } elseif ($purchase_unit_type === 'دەبە' && $material_unit_type === 'بەرمیل' && $materialInfo['buckets_per_barrel']) {
            // Purchasing buckets from barrel-based material
            $base_quantity = $material['quantity'] * $materialInfo['liters_per_bucket'];
            $base_price_per_unit_usd = $material['price_per_unit_usd'] / $materialInfo['liters_per_bucket'];
            $base_price_per_unit_iqd = $material['price_per_unit_iqd'] / $materialInfo['liters_per_bucket'];
            $quantity_to_add = $material['quantity'] * $materialInfo['liters_per_bucket']; // Add liters to inventory
        } elseif ($purchase_unit_type === 'لیتر' && $material_unit_type === 'بەرمیل' && $materialInfo['liters_per_barrel']) {
            // Purchasing liters from barrel-based material
            $base_quantity = $material['quantity'];
            $base_price_per_unit_usd = $material['price_per_unit_usd'];
            $base_price_per_unit_iqd = $material['price_per_unit_iqd'];
            $quantity_to_add = $material['quantity']; // Add liters to inventory
        } elseif ($purchase_unit_type === 'دەبە' && $material_unit_type === 'دەبە' && $materialInfo['liters_per_bucket']) {
            // Purchasing buckets, convert to liters for base tracking
            $base_quantity = $material['quantity'] * $materialInfo['liters_per_bucket'];
            $base_price_per_unit_usd = $material['price_per_unit_usd'] / $materialInfo['liters_per_bucket'];
            $base_price_per_unit_iqd = $material['price_per_unit_iqd'] / $materialInfo['liters_per_bucket'];
            $quantity_to_add = $material['quantity'] * $materialInfo['liters_per_bucket']; // Add liters to inventory
        } elseif ($purchase_unit_type === 'لیتر' && $material_unit_type === 'دەبە' && $materialInfo['liters_per_bucket']) {
            // Purchasing liters from bucket-based material
            $base_quantity = $material['quantity'];
            $base_price_per_unit_usd = $material['price_per_unit_usd'];
            $base_price_per_unit_iqd = $material['price_per_unit_iqd'];
            $quantity_to_add = $material['quantity']; // Add liters to inventory
        } elseif ($purchase_unit_type === 'لیتر' && $material_unit_type === 'لیتر') {
            // Purchasing liters from liter-based material
            $base_quantity = $material['quantity'];
            $base_price_per_unit_usd = $material['price_per_unit_usd'];
            $base_price_per_unit_iqd = $material['price_per_unit_iqd'];
            $quantity_to_add = $material['quantity']; // Add liters to inventory
        } elseif ($purchase_unit_type === 'دانە' && $material_unit_type === 'دانە') {
            // Purchasing pieces from piece-based material
            $base_quantity = $material['quantity'];
            $base_price_per_unit_usd = $material['price_per_unit_usd'];
            $base_price_per_unit_iqd = $material['price_per_unit_iqd'];
            $quantity_to_add = $material['quantity']; // Add pieces to inventory
        } else {
            // Fallback: use the purchase values as base values
            $base_quantity = $material['quantity'];
            $base_price_per_unit_usd = $material['price_per_unit_usd'];
            $base_price_per_unit_iqd = $material['price_per_unit_iqd'];
            $quantity_to_add = $material['quantity']; // Add the quantity as is
        }
        
        $total_price_usd = $material['quantity'] * $material['price_per_unit_usd'];
        $total_price_iqd = $material['quantity'] * $material['price_per_unit_iqd'];
        
        $stmt->execute([
            $_POST['receipt_number'],
            $material['material_id'],
            $_POST['person_id'],
            $material['unit_type'],
            $material['quantity'],
            $material['price_per_unit_usd'],
            $material['price_per_unit_iqd'],
            $total_price_usd,
            $total_price_iqd,
            $_POST['currency_type'],
            $_POST['payment_type'],
            $paid_amount_usd,
            $paid_amount_iqd,
            $remaining_amount_usd,
            $remaining_amount_iqd,
            $_POST['purchase_date'],
            $_POST['notes'] ?? '',
            $transfer_loss,
            $other_loss,
            $usd_to_iqd_rate,
            $base_quantity,
            $base_price_per_unit_usd,
            $base_price_per_unit_iqd,
            $_SESSION['user_id']
        ]);
        
        // Update material quantity in list_materials table
        $new_quantity = $materialInfo['quantity'] + $quantity_to_add;
        $updateMaterialStmt = $pdo->prepare("
            UPDATE list_materials 
            SET quantity = ? 
            WHERE id = ?
        ");
        $updateMaterialStmt->execute([$new_quantity, $material['material_id']]);
        
        $total_usd += $total_price_usd;
        $total_iqd += $total_price_iqd;
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'کڕینەکە بە سەرکەوتووی زیاد کراوە',
        'data' => [
            'total_usd' => $total_usd,
            'total_iqd' => $total_iqd
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in add_purchase.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
