<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['total_assets' => 0, 'total_value' => 0, 'total_depreciation' => 0, 'total_book_value' => 0]);
    exit;
}

if (!hasPermission('view_assets')) {
    echo json_encode(['total_assets' => 0, 'total_value' => 0, 'total_depreciation' => 0, 'total_book_value' => 0]);
    exit;
}

try {
    $category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? intval($_GET['category_id']) : null;
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
    
    $sql = "SELECT 
        COUNT(*) as total_assets,
        SUM(purchase_cost) as total_value,
        SUM(accumulated_depreciation) as total_depreciation,
        SUM(purchase_cost - accumulated_depreciation) as total_book_value
    FROM assets
    WHERE 1=1";
    
    $params = [];
    
    if ($category_id !== null) {
        $sql .= " AND category_id = ?";
        $params[] = $category_id;
    }
    
    if ($status !== null) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'total_assets' => intval($stats['total_assets'] ?? 0),
        'total_value' => floatval($stats['total_value'] ?? 0),
        'total_depreciation' => floatval($stats['total_depreciation'] ?? 0),
        'total_book_value' => floatval($stats['total_book_value'] ?? 0)
    ]);
    
} catch (PDOException $e) {
    error_log('PDOException in assets/get_summary_stats.php: ' . $e->getMessage());
    echo json_encode(['total_assets' => 0, 'total_value' => 0, 'total_depreciation' => 0, 'total_book_value' => 0]);
} catch (Exception $e) {
    error_log('Exception in assets/get_summary_stats.php: ' . $e->getMessage());
    echo json_encode(['total_assets' => 0, 'total_value' => 0, 'total_depreciation' => 0, 'total_book_value' => 0]);
}
