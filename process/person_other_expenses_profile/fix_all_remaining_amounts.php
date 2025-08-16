<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$person_id = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;

if (!$person_id) {
    echo json_encode(['success' => false, 'error' => 'Person ID is required']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get all purchase receipts for this person
    $stmt = $pdo->prepare("
        SELECT DISTINCT receipt_number
        FROM purchase_materials 
        WHERE person_id = ?
        ORDER BY receipt_number
    ");
    
    $stmt->execute([$person_id]);
    $receipts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($receipts)) {
        throw new Exception('No purchase receipts found for this person');
    }
    
    $total_fixed_receipts = 0;
    $total_fixed_items = 0;
    $total_remaining_usd = 0;
    $total_remaining_iqd = 0;
    
    // Fix each receipt
    foreach ($receipts as $receipt_number) {
        // Get all items for this receipt
        $itemStmt = $pdo->prepare("
            SELECT 
                id,
                total_price_usd,
                total_price_iqd,
                paid_amount_usd,
                paid_amount_iqd
            FROM purchase_materials 
            WHERE person_id = ? AND receipt_number = ?
            ORDER BY id ASC
        ");
        
        $itemStmt->execute([$person_id, $receipt_number]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $receipt_fixed_items = 0;
        $receipt_remaining_usd = 0;
        $receipt_remaining_iqd = 0;
        
        // Fix each item's remaining amount
        foreach ($items as $item) {
            // Calculate correct remaining amount
            $correct_remaining_usd = max(0, $item['total_price_usd'] - $item['paid_amount_usd']);
            $correct_remaining_iqd = max(0, $item['total_price_iqd'] - $item['paid_amount_iqd']);
            
            // Update the item with correct remaining amounts
            $updateStmt = $pdo->prepare("
                UPDATE purchase_materials 
                SET remaining_amount_usd = ?, remaining_amount_iqd = ?
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $correct_remaining_usd,
                $correct_remaining_iqd,
                $item['id']
            ]);
            
            $receipt_fixed_items++;
            $receipt_remaining_usd += $correct_remaining_usd;
            $receipt_remaining_iqd += $correct_remaining_iqd;
        }
        
        if ($receipt_fixed_items > 0) {
            $total_fixed_receipts++;
            $total_fixed_items += $receipt_fixed_items;
            $total_remaining_usd += $receipt_remaining_usd;
            $total_remaining_iqd += $receipt_remaining_iqd;
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'هەموو پارەی ماوەکان چاککرانەوە',
        'data' => [
            'person_id' => $person_id,
            'total_receipts' => count($receipts),
            'fixed_receipts' => $total_fixed_receipts,
            'total_fixed_items' => $total_fixed_items,
            'total_remaining_usd' => $total_remaining_usd,
            'total_remaining_iqd' => $total_remaining_iqd
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
