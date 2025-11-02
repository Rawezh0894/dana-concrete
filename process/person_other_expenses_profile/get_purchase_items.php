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
    // Get individual purchase items for this specific receipt
    // Modified query to ensure we get the correct individual item prices
    $stmt = $pdo->prepare("
        SELECT 
            pm.id,
            pm.quantity,
            pm.price_per_unit_usd,
            pm.price_per_unit_iqd,
            pm.total_price_usd,
            pm.total_price_iqd,
            lm.name AS material_name,
            pm.notes,
            pm.unit_type
        FROM purchase_materials pm
        LEFT JOIN list_materials lm ON pm.material_id = lm.id
        WHERE pm.person_id = ? AND pm.receipt_number = ?
        ORDER BY pm.id ASC
    ");
    
    $stmt->execute([$person_id, $receipt_number]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'error' => 'No items found for this receipt']);
        exit;
    }
    
    // Format the data with proper price calculations
    $formattedItems = [];
    foreach ($items as $item) {
        // Ensure we're using the correct individual item prices
        $unit_price_usd = (float)$item['price_per_unit_usd'];
        $unit_price_iqd = (float)$item['price_per_unit_iqd'];
        $quantity = (float)$item['quantity'];
        
        // Calculate the correct total price for this specific item
        $calculated_total_usd = $unit_price_usd * $quantity;
        $calculated_total_iqd = $unit_price_iqd * $quantity;
        
        // Use the calculated total if it differs significantly from stored total
        // This helps identify if there's a data inconsistency
        $final_total_usd = abs($calculated_total_usd - (float)$item['total_price_usd']) < 0.01 ? 
                          (float)$item['total_price_usd'] : $calculated_total_usd;
        $final_total_iqd = abs($calculated_total_iqd - (float)$item['total_price_iqd']) < 0.01 ? 
                          (float)$item['total_price_iqd'] : $calculated_total_iqd;
        
        $formattedItems[] = [
            'id' => (int)$item['id'],
            'material_name' => $item['material_name'] ?? 'کاڵای نەناسراو',
            'quantity' => $quantity,
            'unit_type' => $item['unit_type'] ?? '-',
            'unit_price_usd' => $unit_price_usd,
            'unit_price_iqd' => $unit_price_iqd,
            'unit_price_display' => $unit_price_usd ? '$' . number_format($unit_price_usd, 2) : 
                                   ($unit_price_iqd ? number_format($unit_price_iqd, 0) . ' د.ع' : '-'),
            'total_price_usd' => $final_total_usd,
            'total_price_iqd' => $final_total_iqd,
            'notes' => $item['notes'] ?? '-'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedItems
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}
?>
