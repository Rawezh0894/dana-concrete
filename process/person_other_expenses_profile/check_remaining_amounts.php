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
    // Get detailed information about remaining amounts for this receipt
    $stmt = $pdo->prepare("
        SELECT 
            pm.id,
            pm.material_id,
            pm.quantity,
            pm.unit_price_usd,
            pm.unit_price_iqd,
            pm.total_price_usd,
            pm.total_price_iqd,
            pm.paid_amount_usd,
            pm.paid_amount_iqd,
            pm.remaining_amount_usd,
            pm.remaining_amount_iqd,
            pm.unit_type,
            lm.name AS material_name,
            (pm.total_price_usd - pm.paid_amount_usd) as calculated_remaining_usd,
            (pm.total_price_iqd - pm.paid_amount_iqd) as calculated_remaining_iqd,
            ABS((pm.total_price_usd - pm.paid_amount_usd) - pm.remaining_amount_usd) as usd_difference,
            ABS((pm.total_price_iqd - pm.paid_amount_iqd) - pm.remaining_amount_iqd) as iqd_difference
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
    $total_stored_remaining_usd = 0;
    $total_stored_remaining_iqd = 0;
    $total_calculated_remaining_usd = 0;
    $total_calculated_remaining_iqd = 0;
    $total_usd_difference = 0;
    $total_iqd_difference = 0;
    
    foreach ($items as $item) {
        $total_stored_remaining_usd += (float)$item['remaining_amount_usd'];
        $total_stored_remaining_iqd += (float)$item['remaining_amount_iqd'];
        $total_calculated_remaining_usd += (float)$item['calculated_remaining_usd'];
        $total_calculated_remaining_iqd += (float)$item['calculated_remaining_iqd'];
        $total_usd_difference += (float)$item['usd_difference'];
        $total_iqd_difference += (float)$item['iqd_difference'];
    }
    
    $check_data = [
        'receipt_number' => $receipt_number,
        'person_id' => $person_id,
        'items' => $items,
        'summary' => [
            'total_stored_remaining_usd' => $total_stored_remaining_usd,
            'total_stored_remaining_iqd' => $total_stored_remaining_iqd,
            'total_calculated_remaining_usd' => $total_calculated_remaining_usd,
            'total_calculated_remaining_iqd' => $total_calculated_remaining_iqd,
            'total_usd_difference' => $total_usd_difference,
            'total_iqd_difference' => $total_iqd_difference,
            'items_count' => count($items),
            'has_issues' => $total_usd_difference > 0.01 || $total_iqd_difference > 0.01
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $check_data
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
