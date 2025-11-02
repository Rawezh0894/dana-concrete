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
    // Get detailed purchase data for debugging
    $stmt = $pdo->prepare("
        SELECT 
            pm.id,
            pm.material_id,
            pm.quantity,
            pm.price_per_unit_usd AS unit_price_usd,
            pm.price_per_unit_iqd AS unit_price_iqd,
            pm.total_price_usd,
            pm.total_price_iqd,
            pm.unit_type,
            lm.name AS material_name,
            (pm.quantity * pm.price_per_unit_usd) as calculated_total_usd,
            (pm.quantity * pm.price_per_unit_iqd) as calculated_total_iqd,
            ABS((pm.quantity * pm.price_per_unit_usd) - pm.total_price_usd) as usd_difference,
            ABS((pm.quantity * pm.price_per_unit_iqd) - pm.total_price_iqd) as iqd_difference
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
    
    // Calculate totals
    $total_stored_usd = 0;
    $total_stored_iqd = 0;
    $total_calculated_usd = 0;
    $total_calculated_iqd = 0;
    
    foreach ($items as $item) {
        $total_stored_usd += (float)$item['total_price_usd'];
        $total_stored_iqd += (float)$item['total_price_iqd'];
        $total_calculated_usd += (float)$item['calculated_total_usd'];
        $total_calculated_iqd += (float)$item['calculated_total_iqd'];
    }
    
    $debug_data = [
        'receipt_number' => $receipt_number,
        'person_id' => $person_id,
        'items' => $items,
        'summary' => [
            'total_stored_usd' => $total_stored_usd,
            'total_stored_iqd' => $total_stored_iqd,
            'total_calculated_usd' => $total_calculated_usd,
            'total_calculated_iqd' => $total_calculated_iqd,
            'usd_difference' => abs($total_stored_usd - $total_calculated_usd),
            'iqd_difference' => abs($total_stored_iqd - $total_calculated_iqd),
            'items_count' => count($items)
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $debug_data
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
