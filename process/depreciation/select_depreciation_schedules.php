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

if (!hasPermission('view_depreciation')) {
    echo json_encode([]);
    exit;
}

try {
    $asset_id = isset($_GET['asset_id']) && $_GET['asset_id'] !== '' ? intval($_GET['asset_id']) : null;
    $is_posted = isset($_GET['is_posted']) && $_GET['is_posted'] !== '' ? intval($_GET['is_posted']) : null;
    
    $sql = "SELECT 
        ds.id,
        ds.asset_id,
        a.asset_code,
        a.name as asset_name,
        ds.period_start,
        ds.period_end,
        ds.depreciation_amount,
        ds.accumulated_depreciation,
        ds.book_value,
        ds.is_posted,
        ds.posted_at,
        ds.notes,
        u.username as posted_by_name
    FROM depreciation_schedules ds
    LEFT JOIN assets a ON ds.asset_id = a.id
    LEFT JOIN users u ON ds.posted_by = u.id
    WHERE 1=1";
    
    $params = [];
    
    if ($asset_id !== null) {
        $sql .= " AND ds.asset_id = ?";
        $params[] = $asset_id;
    }
    
    if ($is_posted !== null) {
        $sql .= " AND ds.is_posted = ?";
        $params[] = $is_posted;
    }
    
    $sql .= " ORDER BY ds.period_start DESC, ds.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format numbers
    foreach ($schedules as &$schedule) {
        $schedule['depreciation_amount'] = floatval($schedule['depreciation_amount']);
        $schedule['accumulated_depreciation'] = floatval($schedule['accumulated_depreciation']);
        $schedule['book_value'] = floatval($schedule['book_value']);
    }
    
    echo json_encode($schedules);
    
} catch (PDOException $e) {
    error_log('PDOException in depreciation/select_depreciation_schedules.php: ' . $e->getMessage());
    echo json_encode([]);
} catch (Exception $e) {
    error_log('Exception in depreciation/select_depreciation_schedules.php: ' . $e->getMessage());
    echo json_encode([]);
}
