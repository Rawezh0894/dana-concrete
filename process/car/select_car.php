<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for cars retrieval');
    echo json_encode([]);
    exit;
}

if (!hasPermission('view_accounts')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to view cars');
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, name FROM cars ORDER BY id DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Cars retrieved successfully: Count=' . count($data));
    echo json_encode($data);
    
} catch (PDOException $e) {
    error_log('PDOException in car/select_car.php: ' . $e->getMessage());
    echo json_encode([]);
} catch (Exception $e) {
    error_log('Exception in car/select_car.php: ' . $e->getMessage());
    echo json_encode([]);
}
