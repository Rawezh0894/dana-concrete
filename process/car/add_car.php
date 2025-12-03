<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('car/add_car.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for car addition');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('add_car')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to add car');
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'msg' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
    $car_name = trim($_POST['car_name'] ?? '');

    // Log parsed variables for debugging
    error_log("Parsed vars: car_name='$car_name'");

    // Validate required fields
    if (empty($car_name)) {
        error_log('Car name is empty');
        echo json_encode(['success' => false, 'msg' => 'ناوی ئۆتۆمبێل پێویستە!']);
        exit;
    }

    // Prevent duplicate car names
    $check = $pdo->prepare("SELECT COUNT(*) FROM cars WHERE name = ?");
    $check->execute([$car_name]);
    if ($check->fetchColumn() > 0) {
        error_log('Duplicate car name found: ' . $car_name);
        echo json_encode(['success' => false, 'msg' => 'ئەم ناوی ئۆتۆمبێل پێشتر تۆمارکراوە!']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO cars (name) VALUES (?)");
    $ok = $stmt->execute([$car_name]);
    
    if ($ok) {
        error_log('Car successfully added: Name=' . $car_name);
        echo json_encode(['success' => true, 'msg' => 'ئۆتۆمبێل بەسەرکەوتوویی زیادکرا!']);
    } else {
        error_log('Failed to add car: Name=' . $car_name);
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردن!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in car/add_car.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردنی ئۆتۆمبێل!']);
} catch (Exception $e) {
    error_log('Exception in car/add_car.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردنی ئۆتۆمبێل!']);
}
