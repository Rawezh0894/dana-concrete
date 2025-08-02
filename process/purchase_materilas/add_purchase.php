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
    $required_fields = ['receipt_number', 'person_id', 'purchase_date', 'currency_type'];
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
            !isset($material['price_per_unit_usd']) || !isset($material['price_per_unit_iqd'])) {
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
        (receipt_number, material_id, unit_type, person_id, quantity, price_per_unit_usd, price_per_unit_iqd, 
         total_price_usd, total_price_iqd, currency_type, purchase_date, notes, transfer_loss, other_loss, usd_to_iqd_rate, 
         pieces_per_carton, bags_per_barrel, liters_per_bag, liters_per_barrel, price_per_piece, price_per_liter, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $total_usd = 0;
    $total_iqd = 0;
    
    foreach ($materials as $material) {
        // Get material details to calculate unit-specific prices
        $materialStmt = $pdo->prepare("SELECT unit_type, pieces_per_carton, bags_per_barrel, liters_per_bag, liters_per_barrel, price_per_piece, price_per_liter, price_per_bag FROM inventory_materials WHERE id = ?");
        $materialStmt->execute([$material['material_id']]);
        $materialDetails = $materialStmt->fetch(PDO::FETCH_ASSOC);
        
        $total_price_usd = $material['quantity'] * $material['price_per_unit_usd'];
        $total_price_iqd = $material['quantity'] * $material['price_per_unit_iqd'];
        
        // Calculate unit-specific prices based on material type
        $price_per_piece = 0;
        $price_per_liter = 0;
        $price_per_bag = 0;
        
        if ($materialDetails) {
            switch ($materialDetails['unit_type']) {
                case 'carton':
                    if ($materialDetails['pieces_per_carton'] > 0) {
                        $price_per_piece = $material['price_per_unit_usd'] / $materialDetails['pieces_per_carton'];
                    }
                    break;
                case 'barrel':
                    if ($materialDetails['bags_per_barrel'] > 0 && $materialDetails['liters_per_bag'] > 0) {
                        $price_per_bag = $material['price_per_unit_usd'] / $materialDetails['bags_per_barrel'];
                        $price_per_liter = $material['price_per_unit_usd'] / ($materialDetails['bags_per_barrel'] * $materialDetails['liters_per_bag']);
                    }
                    break;
                case 'bag':
                    if ($materialDetails['liters_per_bag'] > 0) {
                        $price_per_liter = $material['price_per_unit_usd'] / $materialDetails['liters_per_bag'];
                        $price_per_bag = $material['price_per_unit_usd'];
                    }
                    break;
                case 'piece':
                    $price_per_piece = $material['price_per_unit_usd'];
                    break;
                case 'liter':
                    $price_per_liter = $material['price_per_unit_usd'];
                    break;
            }
        }
        
        $stmt->execute([
            $_POST['receipt_number'],
            $material['material_id'],
            $materialDetails['unit_type'] ?? 'piece',
            $_POST['person_id'],
            $material['quantity'],
            $material['price_per_unit_usd'],
            $material['price_per_unit_iqd'],
            $total_price_usd,
            $total_price_iqd,
            $_POST['currency_type'],
            $_POST['purchase_date'],
            $_POST['notes'] ?? '',
            $transfer_loss,
            $other_loss,
            $usd_to_iqd_rate,
            $materialDetails['pieces_per_carton'] ?? null,
            $materialDetails['bags_per_barrel'] ?? null,
            $materialDetails['liters_per_bag'] ?? null,
            $materialDetails['liters_per_barrel'] ?? null,
            $price_per_piece,
            $price_per_liter,
            $price_per_bag,
            $_SESSION['user_id']
        ]);
        
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
