<?php
/**
 * Get bins/silos for raw material sales
 * Returns bin information with current quantity and average price
 */
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'سێشن نییە!']);
    exit;
}

try {
    // Get all bins with their information
    $sql = "
        SELECT 
            id,
            name,
            type,
            material_type,
            amount as available_quantity,
            total_value,
            average_price,
            CASE 
                WHEN material_type IN ('چیمەنتۆ', 'دەرمان') THEN 'دۆلار'
                ELSE 'دینار'
            END as currency_type
        FROM bins_silos
        WHERE amount > 0
        ORDER BY type, name
    ";
    
    $stmt = $pdo->query($sql);
    $bins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    foreach ($bins as &$bin) {
        $bin['available_quantity'] = floatval($bin['available_quantity']);
        $bin['total_value'] = floatval($bin['total_value']);
        $bin['average_price'] = floatval($bin['average_price']);
        $bin['display_name'] = $bin['name'] . ' (' . $bin['material_type'] . ') - ' . 
                               number_format($bin['available_quantity'], 2) . ' کگم';
    }

    echo json_encode([
        'success' => true,
        'data' => $bins
    ]);

} catch (PDOException $e) {
    error_log('Get Bins Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس']);
}
