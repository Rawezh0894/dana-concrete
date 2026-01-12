<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('calculate_depreciation')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
    $schedule_id = intval($_POST['schedule_id'] ?? 0);
    
    if ($schedule_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی کاتی داخوران پێویستە!']);
        exit;
    }
    
    // Get schedule
    $scheduleStmt = $pdo->prepare("SELECT * FROM depreciation_schedules WHERE id = ?");
    $scheduleStmt->execute([$schedule_id]);
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode(['success' => false, 'message' => 'کاتی داخوران نەدۆزرایەوە!']);
        exit;
    }
    
    if ($schedule['is_posted'] == 1) {
        echo json_encode(['success' => false, 'message' => 'ناتوانیت داخورانی پۆستکراو بسڕیتەوە!']);
        exit;
    }
    
    // Delete schedule
    $deleteStmt = $pdo->prepare("DELETE FROM depreciation_schedules WHERE id = ?");
    $deleteStmt->execute([$schedule_id]);
    
    // Recalculate asset values based on remaining schedules
    $remainingStmt = $pdo->prepare("SELECT 
        MAX(accumulated_depreciation) as max_accumulated,
        MIN(book_value) as min_book_value
    FROM depreciation_schedules 
    WHERE asset_id = ? AND is_posted = 1");
    $remainingStmt->execute([$schedule['asset_id']]);
    $remaining = $remainingStmt->fetch(PDO::FETCH_ASSOC);
    
    $asset = $pdo->prepare("SELECT purchase_cost FROM assets WHERE id = ?");
    $asset->execute([$schedule['asset_id']]);
    $assetData = $asset->fetch(PDO::FETCH_ASSOC);
    
    $new_accumulated = $remaining['max_accumulated'] ? floatval($remaining['max_accumulated']) : 0.00;
    $new_book_value = $remaining['min_book_value'] ? floatval($remaining['min_book_value']) : floatval($assetData['purchase_cost']);
    
    // Update asset
    $updateStmt = $pdo->prepare("UPDATE assets SET 
        current_value = ?,
        accumulated_depreciation = ?
    WHERE id = ?");
    $updateStmt->execute([$new_book_value, $new_accumulated, $schedule['asset_id']]);
    
    echo json_encode(['success' => true, 'message' => 'کاتی داخوران بەسەرکەوتوویی سڕایەوە!']);
    
} catch (PDOException $e) {
    error_log('PDOException in depreciation/delete_depreciation_schedule.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی کاتی داخوران!']);
} catch (Exception $e) {
    error_log('Exception in depreciation/delete_depreciation_schedule.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی کاتی داخوران!']);
}
