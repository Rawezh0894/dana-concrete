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

if (!hasPermission('post_depreciation')) {
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
        echo json_encode(['success' => false, 'message' => 'ئەم داخورانە پێشتر پۆست کراوە!']);
        exit;
    }
    
    // Update schedule to posted
    $updateStmt = $pdo->prepare("UPDATE depreciation_schedules SET 
        is_posted = 1,
        posted_at = NOW(),
        posted_by = ?
    WHERE id = ?");
    $updateStmt->execute([$_SESSION['user_id'], $schedule_id]);
    
    // Update asset with final values
    $assetUpdateStmt = $pdo->prepare("UPDATE assets SET 
        current_value = ?,
        accumulated_depreciation = ?
    WHERE id = ?");
    $assetUpdateStmt->execute([
        $schedule['book_value'],
        $schedule['accumulated_depreciation'],
        $schedule['asset_id']
    ]);
    
    // Create notification
    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'update',
        'depreciation_schedules',
        $schedule_id,
        "داخوران پۆست کرا بۆ ئامێر ID: {$schedule['asset_id']}",
        null,
        ['is_posted' => 1, 'posted_at' => date('Y-m-d H:i:s')],
        null,
        getUserIP()
    );
    
    echo json_encode(['success' => true, 'message' => 'داخوران بەسەرکەوتوویی پۆست کرا!']);
    
} catch (PDOException $e) {
    error_log('PDOException in depreciation/post_depreciation.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە پۆستکردنی داخوران!']);
} catch (Exception $e) {
    error_log('Exception in depreciation/post_depreciation.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە پۆستکردنی داخوران!']);
}
