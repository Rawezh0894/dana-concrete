<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../../config/db_conected.php';
    require_once '../../config/permissions.php';
    header('Content-Type: application/json');

    if (!hasPermission('view_other_expenses')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
        exit;
    }

// Get the average price from BINS_SILOS table
$sql = "SELECT AVERAGE_PRICE FROM BINS_SILOS WHERE AVERAGE_PRICE IS NOT NULL AND AVERAGE_PRICE > 0 ORDER BY id DESC LIMIT 1";
$stmt = $pdo->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result && $result['AVERAGE_PRICE']) {
    echo json_encode([
        'success' => true, 
        'average_price' => floatval($result['AVERAGE_PRICE']),
        'msg' => 'نرخی گاز بەردەستە'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'msg' => 'نرخی گاز لە سیستەمەکەدا نییە'
    ]);
}
} catch (Exception $e) {
    error_log('Error in get_gas_average_price.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'msg' => 'هەڵەی سیستەم: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} 