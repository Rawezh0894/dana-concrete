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

$material_id = $_GET['material_id'] ?? null;

if (!$material_id) {
    echo json_encode(['success' => false, 'msg' => 'ناسنامەی کاڵا پێویستە']);
    exit;
}

$sql = "SELECT id, name, currency_type, purchase_price_usd, purchase_price_iqd FROM list_materials WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$material_id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    echo json_encode(['success' => false, 'msg' => 'کاڵا نەدۆزرایەوە']);
    exit;
}

echo json_encode(['success' => true, 'data' => $material]);
} catch (Exception $e) {
    error_log('Error in get_material_details.php: ' . $e->getMessage());
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