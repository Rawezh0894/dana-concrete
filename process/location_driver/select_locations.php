<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'کاربر لاگین نەکراوە']);
    exit;
}

// Check if user has permission to view locations
if (!hasPermission('view_location')) {
    echo json_encode(['success' => false, 'message' => 'توانای دەست گەیشتنت نییە بۆ بینینی شوێنەکان']);
    exit;
}

try {
    // Get all locations
    $stmt = $pdo->query("SELECT id, name FROM locations ORDER BY name ASC");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $locations
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in select_locations.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە داتابەیس'
    ]);
}
?>
