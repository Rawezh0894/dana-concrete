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
if (!hasPermission('view_materials')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get the highest receipt number from the database
    $stmt = $pdo->prepare("
        SELECT receipt_number 
        FROM purchase_materials 
        WHERE receipt_number LIKE 'KR-%' 
        ORDER BY CAST(SUBSTRING(receipt_number, 4) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Extract the number part and increment it
        $current_number = intval(substr($result['receipt_number'], 3));
        $next_number = $current_number + 1;
    } else {
        // If no receipt exists, start from 1
        $next_number = 1;
    }
    
    // Format the next receipt number
    $next_receipt_number = 'KR-' . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    
    // Double-check that this receipt number doesn't already exist
    $stmt = $pdo->prepare("SELECT id FROM purchase_materials WHERE receipt_number = ? LIMIT 1");
    $stmt->execute([$next_receipt_number]);
    if ($stmt->fetch()) {
        // If it exists, find the next available number
        $stmt = $pdo->prepare("
            SELECT receipt_number 
            FROM purchase_materials 
            WHERE receipt_number LIKE 'KR-%' 
            ORDER BY CAST(SUBSTRING(receipt_number, 4) AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $current_number = intval(substr($result['receipt_number'], 3));
            $next_number = $current_number + 1;
        } else {
            $next_number = 1;
        }
        
        $next_receipt_number = 'KR-' . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }
    
    echo json_encode([
        'success' => true,
        'receipt_number' => $next_receipt_number
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_next_receipt_number.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 