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
    
    // Handle multiple roles - convert array to comma-separated string
    $roles = $_POST['role'] ?? [];
    if (is_array($roles)) {
        $roles = array_filter(array_map('trim', $roles)); // Remove empty values
        $role = implode(',', $roles);
    } else {
        $role = trim($roles ?? '');
    }
    
    // Salary and bonus are optional - default to 0 if empty
    $salary = trim($_POST['salary'] ?? '0');
    if ($salary === '' || $salary === null) {
        $salary = '0';
    }
    $bonus = floatval($_POST['bonus'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', name='$name', mobile='$mobile', role='$role', salary='$salary', bonus='$bonus', status='$status'");
    error_log("POST data: " . print_r($_POST, true));

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
        echo json_encode(['success' => false, 'message' => 'تکایە لانیکەم یەک ڕۆڵ هەڵبژێرە!']);
        exit;
    }

    // Salary and bonus are optional - default to 0 if empty
    if (empty($salary) || $salary === '') {
        $salary = 0;
    }

    // Validate status
    $valid_statuses = ['active', 'inactive', 'on_leave', 'resigned'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'active'; // Default to active if invalid
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
    
    // Build UPDATE query based on column existence
    $query = '';
    $params = [];
    
    if ($bonusExists && $statusExists) {
        $query = 'UPDATE employees SET name=?, mobile=?, role=?, salary=?, bonus=?, status=? WHERE id=?';
        $params = [$name, $mobile, $role, $salary, $bonus, $status, $id];
        error_log("Using query with bonus and status");
    } elseif ($bonusExists) {
        $query = 'UPDATE employees SET name=?, mobile=?, role=?, salary=?, bonus=? WHERE id=?';
        $params = [$name, $mobile, $role, $salary, $bonus, $id];
        error_log("Using query with bonus only");
    } elseif ($statusExists) {
        $query = 'UPDATE employees SET name=?, mobile=?, role=?, salary=?, status=? WHERE id=?';
        $params = [$name, $mobile, $role, $salary, $status, $id];
        error_log("Using query with status only");
    } else {
        $query = 'UPDATE employees SET name=?, mobile=?, role=?, salary=? WHERE id=?';
        $params = [$name, $mobile, $role, $salary, $id];
        error_log("Using query without bonus and status");
    }
    
    error_log("Query: $query");
    error_log("Params: " . print_r($params, true));
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute($params);
    
    if ($result) {
        error_log('Employee successfully updated: ID=' . $id . ', Name=' . $name . ', Bonus=' . $bonus);
        echo json_encode(['success' => true, 'message' => 'کارمەند نوێکرایەوە!']);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log('Failed to update employee: ID=' . $id . ', Error: ' . print_r($errorInfo, true));
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە: ' . ($errorInfo[2] ?? 'Unknown error')]);
    }

} catch (PDOException $e) {
    error_log('PDOException in update_employee.php: ' . $e->getMessage());
    error_log('PDOException trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی کارمەند: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in update_employee.php: ' . $e->getMessage());
    error_log('Exception trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی کارمەند: ' . $e->getMessage()]);
}
