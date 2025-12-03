<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('delete_employee.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for employee deletion');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('delete_employee')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to delete employee');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    
    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id'");

    if ($id <= 0) {
        error_log('Invalid employee ID: ' . $id);
        echo json_encode(['success' => false, 'message' => 'ناسنامەی کارمەند پێویستە!']);
        exit;
    }

    // Check if employee exists
    $checkStmt = $pdo->prepare('SELECT id, name FROM employees WHERE id = ?');
    $checkStmt->execute([$id]);
    $existingEmployee = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingEmployee) {
        error_log('Employee not found for deletion: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'کارمەند نەدۆزرایەوە!']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM employees WHERE id=?');
    if ($stmt->execute([$id])) {
        error_log('Employee successfully deleted: ID=' . $id . ', Name=' . $existingEmployee['name']);
        echo json_encode(['success' => true, 'message' => 'کارمەند بەسەرکەوتوویی سڕایەوە!']);
    } else {
        error_log('Failed to delete employee: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in delete_employee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی کارمەند!']);
} catch (Exception $e) {
    error_log('Exception in delete_employee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی کارمەند!']);
}
