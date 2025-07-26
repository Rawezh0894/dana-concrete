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

// Check if user has permission to edit concrete receipts
if (!hasPermission('edit_concrete_receipts')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get POST data
$receipt_ids = $_POST['receipt_ids'] ?? [];
$price_per_meter = $_POST['price_per_meter'] ?? null;

// Validate input
if (empty($receipt_ids) || !is_array($receipt_ids)) {
    echo json_encode(['success' => false, 'error' => 'No receipt IDs provided']);
    exit;
}

if (!is_numeric($price_per_meter) || $price_per_meter <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid price']);
    exit;
}

try {
    // Prepare the update statement
    $stmt = $pdo->prepare("
        UPDATE concrete_receipts 
        SET price_per_meter = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    
    $success_count = 0;
    $error_count = 0;
    
    // Update each receipt
    foreach ($receipt_ids as $receipt_id) {
        if (is_numeric($receipt_id)) {
            $result = $stmt->execute([$price_per_meter, $receipt_id]);
            if ($result) {
                $success_count++;
            } else {
                $error_count++;
            }
        } else {
            $error_count++;
        }
    }
    
    if ($success_count > 0) {
        echo json_encode([
            'success' => true,
            'message' => "Updated $success_count receipt(s) successfully",
            'updated_count' => $success_count,
            'error_count' => $error_count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No receipts were updated'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error updating concrete receipt prices: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?> 