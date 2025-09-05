<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasPermission('delete_purchase')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە بۆ سڕینەوەی تۆمارەکان!']);
    exit;
}

try {
    $record_id = $_POST['record_id'] ?? null;
    
    if (!$record_id) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی تۆمارەکە نەدراوە']);
        exit;
    }
    
    // Validate record ID
    if (!is_numeric($record_id)) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی تۆمارەکە نادروستە']);
        exit;
    }
    
    // Check if record exists
    $check_stmt = $pdo->prepare("SELECT id, bin_name, material_type, month_year FROM monthly_material_stock WHERE id = ?");
    $check_stmt->execute([$record_id]);
    $record = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        echo json_encode(['success' => false, 'message' => 'تۆمارەکە نەدۆزرایەوە']);
        exit;
    }
    
    // Delete the record
    $delete_stmt = $pdo->prepare("DELETE FROM monthly_material_stock WHERE id = ?");
    $result = $delete_stmt->execute([$record_id]);
    
    if ($result) {
        // Log the deletion
        error_log("Monthly stock record deleted - ID: $record_id, Bin: {$record['bin_name']}, Material: {$record['material_type']}, Month: {$record['month_year']}, User: {$_SESSION['user_id']}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'تۆمارەکە بە سەرکەوتوویی سڕایەوە',
            'deleted_record' => $record
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی تۆمارەکە']);
    }
    
} catch (Exception $e) {
    error_log("Error deleting monthly stock record: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()]);
}
?>
