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

if (!hasPermission('update_assets')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
    $asset_id = intval($_POST['asset_id'] ?? 0);
    $asset_code = trim($_POST['asset_code'] ?? '');
    $asset_name = trim($_POST['asset_name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $serial_number = trim($_POST['serial_number'] ?? '');
    $purchase_date = $_POST['purchase_date'] ?? '';
    $purchase_cost = floatval($_POST['purchase_cost'] ?? 0);
    $salvage_value = floatval($_POST['salvage_value'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $supplier = trim($_POST['supplier'] ?? '');
    $warranty_expiry = !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null;
    $status = $_POST['status'] ?? 'active';
    $notes = trim($_POST['notes'] ?? '');

    if ($asset_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی ئامێر پێویستە!']);
        exit;
    }

    // Get old values for notification
    $oldStmt = $pdo->prepare("SELECT * FROM assets WHERE id = ?");
    $oldStmt->execute([$asset_id]);
    $oldValues = $oldStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$oldValues) {
        echo json_encode(['success' => false, 'message' => 'ئامێر نەدۆزرایەوە!']);
        exit;
    }

    // Validate required fields
    if (empty($asset_code)) {
        echo json_encode(['success' => false, 'message' => 'کۆدی ئامێر پێویستە!']);
        exit;
    }

    if (empty($asset_name)) {
        echo json_encode(['success' => false, 'message' => 'ناوی ئامێر پێویستە!']);
        exit;
    }

    if ($category_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'جۆری ئامێر پێویستە!']);
        exit;
    }

    if (empty($purchase_date)) {
        echo json_encode(['success' => false, 'message' => 'بەرواری کڕین پێویستە!']);
        exit;
    }

    if ($purchase_cost <= 0) {
        echo json_encode(['success' => false, 'message' => 'نرخی کڕین پێویستە!']);
        exit;
    }

    // Check for duplicate asset code (excluding current asset)
    $check = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE asset_code = ? AND id != ?");
    $check->execute([$asset_code, $asset_id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ئەم کۆدی ئامێر پێشتر تۆمارکراوە!']);
        exit;
    }

    // Update asset (don't update depreciation-related fields as they are calculated)
    $stmt = $pdo->prepare("UPDATE assets SET
        asset_code = ?,
        name = ?,
        category_id = ?,
        serial_number = ?,
        purchase_date = ?,
        purchase_cost = ?,
        salvage_value = ?,
        location = ?,
        supplier = ?,
        warranty_expiry = ?,
        status = ?,
        notes = ?
    WHERE id = ?");
    
    $ok = $stmt->execute([
        $asset_code, $asset_name, $category_id, $serial_number ?: null, $purchase_date,
        $purchase_cost, $salvage_value, $location ?: null, $supplier ?: null,
        $warranty_expiry, $status, $notes ?: null, $asset_id
    ]);
    
    if ($ok) {
        // Create notification
        $newValues = [
            'asset_code' => $asset_code,
            'name' => $asset_name,
            'purchase_cost' => $purchase_cost,
            'status' => $status
        ];
        
        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'update',
            'assets',
            $asset_id,
            "ئامێر نوێکرایەوە: $asset_name ($asset_code)",
            $oldValues,
            $newValues,
            null,
            getUserIP()
        );
        
        echo json_encode(['success' => true, 'message' => 'ئامێر بەسەرکەوتوویی نوێکرایەوە!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in assets/update_asset.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی ئامێر!']);
} catch (Exception $e) {
    error_log('Exception in assets/update_asset.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی ئامێر!']);
}
