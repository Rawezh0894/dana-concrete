<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('add_material/delete.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for material deletion');
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('delete_material')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to delete material');
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    
    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id'");

    if ($id <= 0) {
        error_log('Invalid material ID: ' . $id);
        echo json_encode(['success' => false, 'message' => 'ناسنامەی ماددە پێویستە!']);
        exit;
    }

    // Check if material exists
    $checkStmt = $pdo->prepare('SELECT id, name FROM inventory_materials WHERE id = ?');
    $checkStmt->execute([$id]);
    $existingMaterial = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingMaterial) {
        error_log('Material not found for deletion: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'ماددە نەدۆزرایەوە!']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM inventory_materials WHERE id=?");
    if ($stmt->execute([$id])) {
        error_log('Material successfully deleted: ID=' . $id . ', Name=' . $existingMaterial['name']);
        echo json_encode(['success' => true, 'message' => 'ماددە بەسەرکەوتوویی سڕایەوە!']);
    } else {
        error_log('Failed to delete material: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in add_material/delete.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in add_material/delete.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
