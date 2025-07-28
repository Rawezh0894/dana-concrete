<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('update_employee.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for employee update');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('edit_employee')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to edit employee');
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
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $salary = trim($_POST['salary'] ?? '');

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', name='$name', mobile='$mobile', role='$role', salary='$salary'");

    // Validate required fields
    if ($id <= 0) {
        error_log('Invalid employee ID: ' . $id);
        echo json_encode(['success' => false, 'message' => 'ناسنامەی کارمەند پێویستە!']);
        exit;
    }

    if (empty($name)) {
        error_log('Employee name is empty');
        echo json_encode(['success' => false, 'message' => 'ناوی کارمەند پێویستە!']);
        exit;
    }

    if (empty($mobile)) {
        error_log('Employee mobile is empty');
        echo json_encode(['success' => false, 'message' => 'ژمارەی مۆبایلی کارمەند پێویستە!']);
        exit;
    }

    if (empty($role)) {
        error_log('Employee role is empty');
        echo json_encode(['success' => false, 'message' => 'پۆستی کارمەند پێویستە!']);
        exit;
    }

    if (empty($salary)) {
        error_log('Employee salary is empty');
        echo json_encode(['success' => false, 'message' => 'مووچەی کارمەند پێویستە!']);
        exit;
    }

    // Check if employee exists
    $checkStmt = $pdo->prepare('SELECT id, name FROM employees WHERE id = ?');
    $checkStmt->execute([$id]);
    $existingEmployee = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingEmployee) {
        error_log('Employee not found: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'کارمەند نەدۆزرایەوە!']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE employees SET name=?, mobile=?, role=?, salary=? WHERE id=?');
    if ($stmt->execute([$name, $mobile, $role, $salary, $id])) {
        error_log('Employee successfully updated: ID=' . $id . ', Name=' . $name);
        echo json_encode(['success' => true, 'message' => 'کارمەند نوێکرایەوە!']);
    } else {
        error_log('Failed to update employee: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in update_employee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in update_employee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
