<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in (optional for persons data)
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'error' => 'Not authenticated']);
//     exit;
// }

// Check permission (optional for persons data)
// if (!hasPermission('view_materials')) {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'error' => 'Permission denied']);
//     exit;
// }

try {
    // Get persons from other_expense_persons table
    $stmt = $pdo->prepare("
        SELECT id, name, phone, address 
        FROM other_expense_persons 
        ORDER BY name ASC
    ");
    $stmt->execute();
    $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $persons
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_persons.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?> 