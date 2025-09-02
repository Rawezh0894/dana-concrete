<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_purchase')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $bin_id = $_GET['bin_id'] ?? null;
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;
    
    // Build parameters array
    $params = [];
    $param_types = '';
    
    if ($bin_id) {
        $params[] = $bin_id;
        $param_types .= 'i';
    } else {
        $params[] = null;
        $param_types .= 's';
    }
    
    if ($start_date) {
        $params[] = $start_date;
        $param_types .= 's';
    } else {
        $params[] = null;
        $param_types .= 's';
    }
    
    if ($end_date) {
        $params[] = $end_date;
        $param_types .= 's';
    } else {
        $params[] = null;
        $param_types .= 's';
    }
    
    // Call stored procedure
    $stmt = $pdo->prepare("CALL GetMaterialStockHistory(?, ?, ?)");
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Close cursor to avoid "pending result sets" error
    $stmt->closeCursor();
    
    // Get current stock for comparison
    $current_stock_sql = "SELECT id, name, material_type, amount, total_value, average_price FROM bins_silos";
    if ($bin_id) {
        $current_stock_sql .= " WHERE id = ?";
        $current_stmt = $pdo->prepare($current_stock_sql);
        $current_stmt->execute([$bin_id]);
    } else {
        $current_stmt = $pdo->prepare($current_stock_sql);
        $current_stmt->execute();
    }
    $current_stock = $current_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'current_stock' => $current_stock
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()
    ]);
}
?>
