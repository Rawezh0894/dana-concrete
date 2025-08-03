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
    // Get all persons/suppliers
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM other_expense_persons 
        ORDER BY name ASC
    ");
    $stmt->execute();
    $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $persons
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_persons.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 