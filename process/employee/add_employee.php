<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('add_employee.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for employee addition');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('add_employee')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to add employee');
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
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $role = trim($_POST['role'] ?? '');
    // Mapping basic_salary from form to 'salary' column for backward compatibility or use basic_salary
    // The user's prompt implies full HR. I will save to both if needed, but the table now has 'basic_salary'.
    // Let's assume 'salary' column holds the main monthly salary.
    $salary = floatval($_POST['salary'] ?? 0); 
    
    // New Fields
    $job_title = trim($_POST['job_title'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $join_date = !empty($_POST['join_date']) ? $_POST['join_date'] : null;
    $basic_salary = floatval($_POST['basic_salary'] ?? 0);
    $daily_rate = floatval($_POST['daily_rate'] ?? 0);
    $overtime_rate = floatval($_POST['overtime_rate'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');

    // If basic salary is provided but salary (legacy) is 0, use basic.
    if ($salary == 0 && $basic_salary > 0) {
        $salary = $basic_salary;
    }

    // Image Upload Handling
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/employees/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExt, $allowed)) {
            $fileName = uniqid('emp_') . '.' . $fileExt;
            $destPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                $image_path = 'uploads/employees/' . $fileName;
            }
        }
    }

    // Check for duplicate mobile number
    $stmt = $pdo->prepare('SELECT id FROM employees WHERE mobile = ?');
    $stmt->execute([$mobile]);
    if ($stmt->fetch()) {
        error_log('Duplicate mobile number found: ' . $mobile);
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی مۆبایل پێشتر تۆمارکراوە!']);
        exit;
    }

    $sql = "INSERT INTO employees (name, mobile, role, salary, job_title, department, join_date, basic_salary, daily_rate, overtime_rate, status, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$name, $mobile, $role, $salary, $job_title, $department, $join_date, $basic_salary, $daily_rate, $overtime_rate, $status, $image_path])) {
        error_log('Employee successfully added: Name=' . $name);
        echo json_encode(['success' => true, 'message' => 'کارمەند بەسەرکەوتوویی زیادکرا!']);
    } else {
        error_log('Failed to add employee: Name=' . $name);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in add_employee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی کارمەند!']);
} catch (Exception $e) {
    error_log('Exception in add_employee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی کارمەند!']);
}
