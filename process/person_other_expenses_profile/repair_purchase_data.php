<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$person_id = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;
$receipt_number = isset($_GET['receipt_number']) ? $_GET['receipt_number'] : '';

if (!$person_id || !$receipt_number) {
    echo json_encode(['success' => false, 'error' => 'Person ID and Receipt Number are required']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get all items for this receipt
    $stmt = $pdo->prepare("
        SELECT 
            id,
            quantity,
            price_per_unit_usd,
            price_per_unit_iqd,
            total_price_usd,
            total_price_iqd
        FROM purchase_materials 
        WHERE person_id = ? AND receipt_number = ?
        ORDER BY id ASC
    ");
    
    $stmt->execute([$person_id, $receipt_number]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        throw new Exception('No items found for this receipt');
    }
    
    $fixed_items = [];
    $total_fixed_usd = 0;
    $total_fixed_iqd = 0;
    
    // Fix each item
    foreach ($items as $item) {
        $calculated_total_usd = $item['quantity'] * $item['price_per_unit_usd'];
        $calculated_total_iqd = $item['quantity'] * $item['price_per_unit_iqd'];
        
        // Check if there's a significant difference
        $usd_difference = abs($calculated_total_usd - $item['total_price_usd']);
        $iqd_difference = abs($calculated_total_iqd - $item['total_price_iqd']);
        
        if ($usd_difference > 0.01 || $iqd_difference > 0.01) {
            // Update the item with correct totals
            $updateStmt = $pdo->prepare("
                UPDATE purchase_materials 
                SET total_price_usd = ?, total_price_iqd = ?
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $calculated_total_usd,
                $calculated_total_iqd,
                $item['id']
            ]);
            
            $fixed_items[] = [
                'id' => $item['id'],
                'old_total_usd' => $item['total_price_usd'],
                'new_total_usd' => $calculated_total_usd,
                'old_total_iqd' => $item['total_price_iqd'],
                'new_total_iqd' => $calculated_total_iqd
            ];
        }
        
        $total_fixed_usd += $calculated_total_usd;
        $total_fixed_iqd += $calculated_total_iqd;
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'داتاکان چاککرانەوە',
        'data' => [
            'receipt_number' => $receipt_number,
            'person_id' => $person_id,
            'fixed_items_count' => count($fixed_items),
            'fixed_items' => $fixed_items,
            'total_fixed_usd' => $total_fixed_usd,
            'total_fixed_iqd' => $total_fixed_iqd
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
