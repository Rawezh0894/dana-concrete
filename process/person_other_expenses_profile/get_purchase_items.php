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
    $stmt = $pdo->prepare("
        SELECT 
            pm.id,
            pm.quantity,
            pm.unit_price_usd,
            pm.unit_price_iqd,
            pm.total_price_usd,
            pm.total_price_iqd,
            lm.name AS material_name,
            pm.notes
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
    
    // Format the data
    $formattedItems = [];
    foreach ($items as $item) {
        $formattedItems[] = [
            'id' => (int)$item['id'],
            'material_name' => $item['material_name'] ?? 'کاڵای نەناسراو',
            'quantity' => $item['quantity'] ?? '-',
            'unit_price' => $item['unit_price_usd'] ? '$' . number_format($item['unit_price_usd'], 2) : 
                           ($item['unit_price_iqd'] ? number_format($item['unit_price_iqd'], 0) . ' د.ع' : '-'),
            'total_price_usd' => (float)$item['total_price_usd'],
            'total_price_iqd' => (float)$item['total_price_iqd'],
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
