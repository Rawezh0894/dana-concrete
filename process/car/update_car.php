<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('car/update_car.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for car update');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('edit_car')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to edit car');
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'msg' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
    $car_id = intval($_POST['car_id'] ?? 0);
    $car_name = trim($_POST['car_name'] ?? '');

    // Log parsed variables for debugging
    error_log("Parsed vars: car_id='$car_id', car_name='$car_name'");

    // Validate required fields
    if ($car_id <= 0) {
        error_log('Invalid car ID: ' . $car_id);
        echo json_encode(['success' => false, 'msg' => 'ناسنامەی ئۆتۆمبێل پێویستە!']);
        exit;
    }

    if (empty($car_name)) {
        error_log('Car name is empty');
        echo json_encode(['success' => false, 'msg' => 'ناوی ئۆتۆمبێل پێویستە!']);
        exit;
    }

    // Check if car exists
    $checkStmt = $pdo->prepare('SELECT id, name FROM cars WHERE id = ?');
    $checkStmt->execute([$car_id]);
    $existingCar = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingCar) {
        error_log('Car not found: ID=' . $car_id);
        echo json_encode(['success' => false, 'msg' => 'ئۆتۆمبێل نەدۆزرایەوە!']);
        exit;
    }

    // Check for duplicate car name (excluding current car)
    $stmt = $pdo->prepare('SELECT id FROM cars WHERE name = ? AND id != ?');
    $stmt->execute([$car_name, $car_id]);
    if ($stmt->fetch()) {
        error_log('Duplicate car name found: ' . $car_name . ' (excluding car ID: ' . $car_id . ')');
        echo json_encode(['success' => false, 'msg' => 'ئەم ناوی ئۆتۆمبێل پێشتر تۆمارکراوە!']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE cars SET name = ? WHERE id = ?");
    $ok = $stmt->execute([$car_name, $car_id]);
    
    if ($ok) {
        error_log('Car successfully updated: ID=' . $car_id . ', Name=' . $car_name);
        echo json_encode(['success' => true, 'msg' => 'ئۆتۆمبێل بەسەرکەوتوویی نوێکرایەوە!']);
    } else {
        error_log('Failed to update car: ID=' . $car_id);
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in car/update_car.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوەی ئۆتۆمبێل!']);
} catch (Exception $e) {
    error_log('Exception in car/update_car.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوەی ئۆتۆمبێل!']);
}
