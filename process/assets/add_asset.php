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

if (!hasPermission('add_assets')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
    $asset_code = trim($_POST['asset_code'] ?? '');
    $asset_name = trim($_POST['asset_name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $serial_number = trim($_POST['serial_number'] ?? '');
    $purchase_date = $_POST['purchase_date'] ?? '';
    $purchase_cost = floatval($_POST['purchase_cost'] ?? 0);
    $salvage_value = floatval($_POST['salvage_value'] ?? 0);
    $useful_life_years = intval($_POST['useful_life_years'] ?? 5);
    $useful_life_units = !empty($_POST['useful_life_units']) ? floatval($_POST['useful_life_units']) : null;
    $depreciation_method = $_POST['depreciation_method'] ?? 'straight_line';
    $depreciation_rate = !empty($_POST['depreciation_rate']) ? floatval($_POST['depreciation_rate']) : null;
    $location = trim($_POST['location'] ?? '');
    $supplier = trim($_POST['supplier'] ?? '');
    $warranty_expiry = !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null;
    $status = $_POST['status'] ?? 'active';
    $notes = trim($_POST['notes'] ?? '');
    $created_by = $_SESSION['user_id'];

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

    if ($useful_life_years <= 0) {
        echo json_encode(['success' => false, 'message' => 'ماوەی بەکارهێنان پێویستە!']);
        exit;
    }

    // Check for duplicate asset code
    $check = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE asset_code = ?");
    $check->execute([$asset_code]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ئەم کۆدی ئامێر پێشتر تۆمارکراوە!']);
        exit;
    }

    // Calculate initial values
    $current_value = $purchase_cost;
    $accumulated_depreciation = 0.00;

    // Insert asset
    $stmt = $pdo->prepare("INSERT INTO assets (
        asset_code, name, category_id, serial_number, purchase_date, purchase_cost,
        salvage_value, useful_life_years, useful_life_units, depreciation_method,
        depreciation_rate, current_value, accumulated_depreciation, status,
        location, supplier, warranty_expiry, notes, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $ok = $stmt->execute([
        $asset_code, $asset_name, $category_id, $serial_number ?: null, $purchase_date,
        $purchase_cost, $salvage_value, $useful_life_years, $useful_life_units,
        $depreciation_method, $depreciation_rate, $current_value, $accumulated_depreciation,
        $status, $location ?: null, $supplier ?: null, $warranty_expiry, $notes ?: null, $created_by
    ]);
    
    if ($ok) {
        $asset_id = $pdo->lastInsertId();
        
        // Create notification
        createDetailedNotification(
            $pdo,
            $created_by,
            'create',
            'assets',
            $asset_id,
            "ئامێرێکی نوێ زیادکرا: $asset_name ($asset_code)",
            null,
            ['asset_code' => $asset_code, 'name' => $asset_name, 'purchase_cost' => $purchase_cost],
            null,
            getUserIP()
        );
        
        echo json_encode(['success' => true, 'message' => 'ئامێر بەسەرکەوتوویی زیادکرا!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in assets/add_asset.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی ئامێر!']);
} catch (Exception $e) {
    error_log('Exception in assets/add_asset.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی ئامێر!']);
}
