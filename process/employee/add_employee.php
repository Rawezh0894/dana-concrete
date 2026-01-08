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
    $salary = trim($_POST['salary'] ?? '');
    $bonus = floatval($_POST['bonus'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');

    // Log parsed variables for debugging
    error_log("Parsed vars: name='$name', mobile='$mobile', role='$role', salary='$salary', bonus='$bonus', status='$status'");
    error_log("POST data: " . print_r($_POST, true));

    // Validate required fields
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

    // Validate status
    $valid_statuses = ['active', 'inactive', 'on_leave', 'resigned'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'active'; // Default to active if invalid
    }

    // Check for duplicate mobile number
    $stmt = $pdo->prepare('SELECT id FROM employees WHERE mobile = ?');
    $stmt->execute([$mobile]);
    if ($stmt->fetch()) {
        error_log('Duplicate mobile number found: ' . $mobile);
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی مۆبایل پێشتر تۆمارکراوە!']);
        exit;
    }

    // Check if bonus and status columns exist
    $bonusExists = false;
    $statusExists = false;
    
    try {
        $checkColumns = $pdo->query("SHOW COLUMNS FROM employees LIKE 'bonus'");
        $bonusExists = $checkColumns->rowCount() > 0;
        error_log("Bonus column exists: " . ($bonusExists ? 'YES' : 'NO'));
    } catch (Exception $e) {
        error_log('Error checking bonus column: ' . $e->getMessage());
    }
    
    try {
        $checkColumns = $pdo->query("SHOW COLUMNS FROM employees LIKE 'status'");
        $statusExists = $checkColumns->rowCount() > 0;
        error_log("Status column exists: " . ($statusExists ? 'YES' : 'NO'));
    } catch (Exception $e) {
        error_log('Error checking status column: ' . $e->getMessage());
    }
    
    // Build INSERT query based on column existence
    $query = '';
    $params = [];
    
    if ($bonusExists && $statusExists) {
        $query = 'INSERT INTO employees (name, mobile, role, salary, bonus, status) VALUES (?, ?, ?, ?, ?, ?)';
        $params = [$name, $mobile, $role, $salary, $bonus, $status];
        error_log("Using query with bonus and status");
    } elseif ($bonusExists) {
        $query = 'INSERT INTO employees (name, mobile, role, salary, bonus) VALUES (?, ?, ?, ?, ?)';
        $params = [$name, $mobile, $role, $salary, $bonus];
        error_log("Using query with bonus only");
    } elseif ($statusExists) {
        $query = 'INSERT INTO employees (name, mobile, role, salary, status) VALUES (?, ?, ?, ?, ?)';
        $params = [$name, $mobile, $role, $salary, $status];
        error_log("Using query with status only");
    } else {
        $query = 'INSERT INTO employees (name, mobile, role, salary) VALUES (?, ?, ?, ?)';
        $params = [$name, $mobile, $role, $salary];
        error_log("Using query without bonus and status");
    }
    
    error_log("Query: $query");
    error_log("Params: " . print_r($params, true));
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute($params);
    
    if ($result) {
        error_log('Employee successfully added: Name=' . $name . ', Mobile=' . $mobile . ', Role=' . $role . ', Bonus=' . $bonus);
        echo json_encode(['success' => true, 'message' => 'کارمەند بەسەرکەوتوویی زیادکرا!']);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log('Failed to add employee: Name=' . $name . ', Error: ' . print_r($errorInfo, true));
        echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن: ' . ($errorInfo[2] ?? 'Unknown error')]);
    }

} catch (PDOException $e) {
    error_log('PDOException in add_employee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی کارمەند!']);
} catch (Exception $e) {
    error_log('Exception in add_employee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی کارمەند!']);
}
