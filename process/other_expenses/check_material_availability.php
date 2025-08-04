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
    $quantity = $_GET['quantity'] ?? null;
    $usage_unit_type = $_GET['usage_unit_type'] ?? null;

    if (!$material_id || !$quantity || !$usage_unit_type) {
        echo json_encode(['success' => false, 'msg' => 'ناسنامەی کاڵا، بڕ و یەکەی بەکارهێنان پێویستە']);
        exit;
    }

    // Get material details including unit type and conversion factors
    $sql = "SELECT quantity, name, unit_type, pieces_per_carton, buckets_per_barrel, liters_per_bucket, liters_per_barrel FROM list_materials WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$material_id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$material) {
        echo json_encode(['success' => false, 'msg' => 'کاڵا نەدۆزرایەوە']);
        exit;
    }

    $available_quantity = floatval($material['quantity']);
    $required_quantity = floatval($quantity);
    $material_unit_type = $material['unit_type'];
    $pieces_per_carton = floatval($material['pieces_per_carton'] ?? 0);
    $buckets_per_barrel = floatval($material['buckets_per_barrel'] ?? 0);
    $liters_per_bucket = floatval($material['liters_per_bucket'] ?? 0);
    $liters_per_barrel = floatval($material['liters_per_barrel'] ?? 0);

    // Calculate base quantity based on usage_unit_type
    $base_required_quantity = $required_quantity;
    
    // Convert usage unit to base unit (دانە/لیتر)
    if ($usage_unit_type === 'کارتۆن' && $material_unit_type === 'کارتۆن' && $pieces_per_carton > 0) {
        $base_required_quantity = $required_quantity * $pieces_per_carton;
    } elseif ($usage_unit_type === 'دانە' && $material_unit_type === 'کارتۆن') {
        $base_required_quantity = $required_quantity;
    } elseif ($usage_unit_type === 'بەرمیل' && $material_unit_type === 'بەرمیل' && $liters_per_barrel > 0) {
        $base_required_quantity = $required_quantity * $liters_per_barrel;
    } elseif ($usage_unit_type === 'لیتر' && $material_unit_type === 'بەرمیل') {
        $base_required_quantity = $required_quantity;
    } elseif ($usage_unit_type === 'دەبە' && $material_unit_type === 'بەرمیل' && $liters_per_bucket > 0) {
        $base_required_quantity = $required_quantity * $liters_per_bucket;
    } elseif ($usage_unit_type === 'دەبە' && $material_unit_type === 'دەبە' && $liters_per_bucket > 0) {
        $base_required_quantity = $required_quantity * $liters_per_bucket;
    } elseif ($usage_unit_type === 'لیتر' && $material_unit_type === 'دەبە') {
        $base_required_quantity = $required_quantity;
    } elseif ($usage_unit_type === 'لیتر' && $material_unit_type === 'لیتر') {
        $base_required_quantity = $required_quantity;
    } elseif ($usage_unit_type === 'دانە' && $material_unit_type === 'دانە') {
        $base_required_quantity = $required_quantity;
    } else {
        // Fallback: use the usage quantity as base quantity
        $base_required_quantity = $required_quantity;
    }

    $is_available = $available_quantity >= $base_required_quantity;

    echo json_encode([
        'success' => true,
        'available' => $is_available,
        'available_quantity' => $available_quantity,
        'required_quantity' => $required_quantity,
        'base_required_quantity' => $base_required_quantity,
        'usage_unit_type' => $usage_unit_type,
        'material_unit_type' => $material_unit_type,
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
?> 