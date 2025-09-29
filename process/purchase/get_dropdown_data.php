<?php
// Simple dropdown data loader
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

// Database connection
$host = 'localhost';
$db   = 'dana_concrete_db';
$user = 'dana_user';
$pass = 'Rawezh.Jaza@0894';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
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
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => 'Error: ' . $e->getMessage()]);
}
?>