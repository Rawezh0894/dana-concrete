<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_purchase')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ بینینی کڕینەکان']);
    exit;
}

header('Content-Type: application/json');

try {
    $data = [];
    
    // Get companies
    $stmt = $pdo->query("SELECT id, name FROM company ORDER BY name ASC");
    $data['companies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get drivers
    $stmt = $pdo->query("SELECT id, name FROM drivers ORDER BY name ASC");
    $data['drivers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get locations
    $stmt = $pdo->query("SELECT id, name FROM locations ORDER BY name ASC");
    $data['locations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get materials
    $stmt = $pdo->query("SELECT id, name FROM materials ORDER BY name ASC");
    $data['materials'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get bins
    $stmt = $pdo->query("SELECT id, name FROM bins_silos ORDER BY name ASC");
    $data['bins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => 'هەڵە: ' . $e->getMessage()]);
}
?>
