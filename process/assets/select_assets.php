<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

if (!hasPermission('view_assets')) {
    echo json_encode([]);
    exit;
}

try {
    $category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? intval($_GET['category_id']) : null;
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
    
    $sql = "SELECT 
        a.id,
        a.asset_code,
        a.name,
        a.category_id,
        ac.name as category_name,
        a.serial_number,
        a.purchase_date,
        a.purchase_cost,
        a.salvage_value,
        a.useful_life_years,
        a.useful_life_units,
        a.depreciation_method,
        a.depreciation_rate,
        a.current_value,
        a.accumulated_depreciation,
        a.status,
        a.location,
        a.supplier,
        a.warranty_expiry,
        a.notes,
        (a.purchase_cost - a.accumulated_depreciation) as book_value,
        CASE 
            WHEN a.depreciation_method = 'straight_line' THEN 'ساڵانە بە یەکسان'
            WHEN a.depreciation_method = 'declining_balance' THEN 'کەمبوونەوەی بیلانس'
            WHEN a.depreciation_method = 'units_of_production' THEN 'بەپێی بەرهەم'
            ELSE a.depreciation_method
        END as depreciation_method_name,
        CASE 
            WHEN a.status = 'active' THEN 'چالاک'
            WHEN a.status = 'inactive' THEN 'ناچالاک'
            WHEN a.status = 'disposed' THEN 'فڕێدراو'
            WHEN a.status = 'under_maintenance' THEN 'لە چاککردندا'
            ELSE a.status
        END as status_name
    FROM assets a
    LEFT JOIN asset_categories ac ON a.category_id = ac.id
    WHERE 1=1";
    
    $params = [];
    
    if ($category_id !== null) {
        $sql .= " AND a.category_id = ?";
        $params[] = $category_id;
    }
    
    if ($status !== null) {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format numbers
    foreach ($assets as &$asset) {
        $asset['purchase_cost'] = floatval($asset['purchase_cost']);
        $asset['salvage_value'] = floatval($asset['salvage_value']);
        $asset['current_value'] = floatval($asset['current_value']);
        $asset['accumulated_depreciation'] = floatval($asset['accumulated_depreciation']);
        $asset['book_value'] = floatval($asset['book_value']);
        $asset['depreciation_rate'] = $asset['depreciation_rate'] ? floatval($asset['depreciation_rate']) : null;
        $asset['useful_life_units'] = $asset['useful_life_units'] ? floatval($asset['useful_life_units']) : null;
    }
    
    echo json_encode($assets);
    
} catch (PDOException $e) {
    error_log('PDOException in assets/select_assets.php: ' . $e->getMessage());
    echo json_encode([]);
} catch (Exception $e) {
    error_log('Exception in assets/select_assets.php: ' . $e->getMessage());
    echo json_encode([]);
}
