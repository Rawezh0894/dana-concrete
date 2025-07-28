<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('car/delete_car.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for car deletion');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('delete_car')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to delete car');
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'msg' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
    $car_id = intval($_POST['car_id'] ?? 0);
    
    // Log parsed variables for debugging
    error_log("Parsed vars: car_id='$car_id'");

    if ($car_id <= 0) {
        error_log('Invalid car ID: ' . $car_id);
        echo json_encode(['success' => false, 'msg' => 'ناسنامەی ئۆتۆمبێل پێویستە!']);
        exit;
    }

    // Check if car exists
    $checkStmt = $pdo->prepare('SELECT id, name FROM cars WHERE id = ?');
    $checkStmt->execute([$car_id]);
    $existingCar = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingCar) {
        error_log('Car not found for deletion: ID=' . $car_id);
        echo json_encode(['success' => false, 'msg' => 'ئۆتۆمبێل نەدۆزرایەوە!']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM cars WHERE id = ?');
    $ok = $stmt->execute([$car_id]);
    
    if ($ok) {
        error_log('Car successfully deleted: ID=' . $car_id . ', Name=' . $existingCar['name']);
        echo json_encode(['success' => true, 'msg' => 'ئۆتۆمبێل بەسەرکەوتوویی سڕایەوە!']);
    } else {
        error_log('Failed to delete car: ID=' . $car_id);
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in car/delete_car.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in car/delete_car.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
