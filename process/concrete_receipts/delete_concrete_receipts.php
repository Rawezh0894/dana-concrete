<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('delete_concrete_receipts.php POST: ' . print_r($_POST, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for concrete receipt deletion');
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

// Check if user has permission to delete concrete receipts
if (!hasPermission('delete_concrete_receipts')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to delete concrete receipts');
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$id = $_POST['id'] ?? null;
if (!$id) {
    error_log('No concrete receipt ID provided for deletion');
    echo json_encode(['success' => false, 'message' => 'ID پێویستە']);
    exit;
}

try {
    // First check if the receipt exists
    $checkStmt = $pdo->prepare('SELECT * FROM concrete_receipts WHERE id = ?');
    $checkStmt->execute([$id]);
    $receipt = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receipt) {
        error_log('Concrete receipt not found for ID: ' . $id);
        echo json_encode(['success' => false, 'message' => 'پسوڵە نەدۆزرایەوە!']);
        exit;
    }
    
    error_log('Found concrete receipt for deletion: ' . print_r($receipt, true));
    
    $stmt = $pdo->prepare('DELETE FROM concrete_receipts WHERE id = ?');
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        require_once __DIR__ . '/../../includes/notify.php';
        notify('delete', 'concrete_receipts', $id, 'پسوڵەی کۆنکرێت سڕایەوە (شماره: ' . $receipt['receipt_number'] . ')');
        error_log('Concrete receipt successfully deleted: ID=' . $id . ', Receipt Number=' . $receipt['receipt_number']);
        echo json_encode(['success' => true, 'message' => 'پسوڵە سڕایەوە']);
    } else {
        error_log('No rows affected when deleting concrete receipt: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'پسوڵە نەدۆزرایەوە!']);
    }
} catch (PDOException $e) {
    error_log('PDOException in delete_concrete_receipts.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in delete_concrete_receipts.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
