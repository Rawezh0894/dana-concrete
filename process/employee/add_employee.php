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
        echo json_encode(['success' => false, 'message' => 'تکایە لانیکەم یەک ڕۆڵ هەڵبژێرە!']);
        exit;
    }

    // Salary and bonus are optional - already set to 0 if empty above

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

    // Check if bonus, status, and join_date columns exist
    $bonusExists = false;
    $statusExists = false;
    $joinDateExists = false;
    
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

    try {
        $checkColumns = $pdo->query("SHOW COLUMNS FROM employees LIKE 'join_date'");
        $joinDateExists = $checkColumns->rowCount() > 0;
        error_log("Join Date column exists: " . ($joinDateExists ? 'YES' : 'NO'));
    } catch (Exception $e) {
        error_log('Error checking join_date column: ' . $e->getMessage());
    }

    $resignationDateExists = false;
    try {
        $checkColumns = $pdo->query("SHOW COLUMNS FROM employees LIKE 'resignation_date'");
        $resignationDateExists = $checkColumns->rowCount() > 0;
        error_log("Resignation Date column exists: " . ($resignationDateExists ? 'YES' : 'NO'));
    } catch (Exception $e) {
        error_log('Error checking resignation_date column: ' . $e->getMessage());
    }
    
    $join_date = $_POST['join_date'] ?? date('Y-m-d');
    if (empty($join_date)) {
        $join_date = date('Y-m-d');
    }

    $resignation_date = $_POST['resignation_date'] ?? null;
    if ($resignation_date === '') {
        $resignation_date = null;
    }

    // Build INSERT query based on column existence
    $query = 'INSERT INTO employees (name, mobile, role, salary';
    $placeholders = 'VALUES (?, ?, ?, ?';
    $params = [$name, $mobile, $role, $salary];

    if ($bonusExists) {
        $query .= ', bonus';
        $placeholders .= ', ?';
        $params[] = $bonus;
    }
    if ($statusExists) {
        $query .= ', status';
        $placeholders .= ', ?';
        $params[] = $status;
    }
    if ($joinDateExists) {
        $query .= ', join_date';
        $placeholders .= ', ?';
        $params[] = $join_date;
    }
    if ($resignationDateExists) {
        $query .= ', resignation_date';
        $placeholders .= ', ?';
        $params[] = $resignation_date;
    }

    $query .= ') ' . $placeholders . ')';
    
    error_log("Query: $query");
    error_log("Params: " . print_r($params, true));
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute($params);
    
    if ($result) {
        $new_id = $pdo->lastInsertId();
        
        // Record initial salary in history table
        try {
            $historyStmt = $pdo->prepare('INSERT INTO employee_salary_history (employee_id, salary, bonus, effective_date) VALUES (?, ?, ?, ?)');
            $historyStmt->execute([$new_id, $salary, $bonus, $join_date]);
        } catch (Exception $e) {
            error_log('Error recording initial salary history: ' . $e->getMessage());
        }

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
