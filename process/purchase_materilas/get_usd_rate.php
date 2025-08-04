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
    // Get the current USD to IQD rate from the database
    // You might need to adjust this query based on where you store the exchange rate
    $stmt = $pdo->prepare("
        SELECT value as rate 
        FROM settings 
        WHERE setting_key = 'usd_to_iqd_rate' 
        LIMIT 1
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $rate = $result ? floatval($result['rate']) : 139250.00; // Default rate if not found
    
    echo json_encode([
        'success' => true,
        'rate' => $rate
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_usd_rate.php: " . $e->getMessage());
    // Return default rate on error
    echo json_encode([
        'success' => true,
        'rate' => 139250.00
    ]);
}
?> 