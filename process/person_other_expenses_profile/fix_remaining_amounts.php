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
            total_price_usd,
            total_price_iqd,
            paid_amount_usd,
            paid_amount_iqd
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
    $total_remaining_usd = 0;
    $total_remaining_iqd = 0;
    
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
        
        $fixed_items[] = [
            'id' => $item['id'],
            'old_remaining_usd' => $item['total_price_usd'], // This was incorrectly stored
            'new_remaining_usd' => $correct_remaining_usd,
            'old_remaining_iqd' => $item['total_price_iqd'], // This was incorrectly stored
            'new_remaining_iqd' => $correct_remaining_iqd
        ];
        
        $total_remaining_usd += $correct_remaining_usd;
        $total_remaining_iqd += $correct_remaining_iqd;
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'پارەی ماوەکان چاککرانەوە',
        'data' => [
            'receipt_number' => $receipt_number,
            'person_id' => $person_id,
            'fixed_items_count' => count($fixed_items),
            'fixed_items' => $fixed_items,
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
