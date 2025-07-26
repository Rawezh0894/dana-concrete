<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check permission
if (!hasPermission('delete_material')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'Purchase ID is required']);
    exit;
}

try {
    $purchase_id = $_POST['id'];
    
    // Get the receipt number for this purchase
    $stmt = $pdo->prepare("SELECT receipt_number FROM purchase_materials WHERE id = ? LIMIT 1");
    $stmt->execute([$purchase_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        throw new Exception('Purchase not found');
    }
    
    $receipt_number = $result['receipt_number'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete all materials for this receipt number
    $stmt = $pdo->prepare("DELETE FROM purchase_materials WHERE receipt_number = ?");
    $stmt->execute([$receipt_number]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'کڕینەکە بە سەرکەوتووی سڕایەوە'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in delete_purchase.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
