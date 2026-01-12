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

if (!hasPermission('delete_assets')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
    $asset_id = intval($_POST['asset_id'] ?? 0);

    if ($asset_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی ئامێر پێویستە!']);
        exit;
    }

    // Get asset info for notification
    $stmt = $pdo->prepare("SELECT asset_code, name FROM assets WHERE id = ?");
    $stmt->execute([$asset_id]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$asset) {
        echo json_encode(['success' => false, 'message' => 'ئامێر نەدۆزرایەوە!']);
        exit;
    }

    // Check if asset has depreciation schedules
    $checkDep = $pdo->prepare("SELECT COUNT(*) FROM depreciation_schedules WHERE asset_id = ? AND is_posted = 1");
    $checkDep->execute([$asset_id]);
    $hasPostedDep = $checkDep->fetchColumn() > 0;

    if ($hasPostedDep) {
        echo json_encode(['success' => false, 'message' => 'ناتوانیت ئەم ئامێرە بسڕیتەوە چونکە داخورانی پۆستکراو هەیە!']);
        exit;
    }

    // Delete asset (cascade will handle related records)
    $stmt = $pdo->prepare("DELETE FROM assets WHERE id = ?");
    $ok = $stmt->execute([$asset_id]);
    
    if ($ok) {
        // Create notification
        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'delete',
            'assets',
            $asset_id,
            "ئامێر سڕایەوە: {$asset['name']} ({$asset['asset_code']})",
            $asset,
            null,
            null,
            getUserIP()
        );
        
        echo json_encode(['success' => true, 'message' => 'ئامێر بەسەرکەوتوویی سڕایەوە!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in assets/delete_asset.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی ئامێر!']);
} catch (Exception $e) {
    error_log('Exception in assets/delete_asset.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی ئامێر!']);
}
