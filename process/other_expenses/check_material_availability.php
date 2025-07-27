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
$required_quantity = $_GET['required_quantity'] ?? null;

if (!$material_id) {
    echo json_encode(['success' => false, 'msg' => 'ناسنامەی کاڵا پێویستە']);
    exit;
}

if (!$required_quantity || $required_quantity <= 0) {
    echo json_encode(['success' => false, 'msg' => 'بڕی پێویست پێویستە']);
    exit;
}

// Get current stock quantity for the material
$sql = "SELECT quantity, name FROM list_materials WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$material_id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    echo json_encode(['success' => false, 'msg' => 'کاڵا نەدۆزرایەوە']);
    exit;
}

$available_quantity = floatval($material['quantity']);
$required_quantity = floatval($required_quantity);

if ($available_quantity < $required_quantity) {
    echo json_encode([
        'success' => false, 
        'msg' => "بڕی پێویست لە کۆگا نەماوە. بڕی بەردەست: {$available_quantity}، بڕی پێویست: {$required_quantity}",
        'available_quantity' => $available_quantity,
        'required_quantity' => $required_quantity,
        'material_name' => $material['name']
    ]);
    exit;
}

echo json_encode([
    'success' => true, 
    'msg' => 'بڕی پێویست لە کۆگا هەیە',
    'available_quantity' => $available_quantity,
    'required_quantity' => $required_quantity,
    'material_name' => $material['name']
]);
} catch (Exception $e) {
    error_log('Error in check_material_availability.php: ' . $e->getMessage());
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