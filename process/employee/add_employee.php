<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once '../../config/employee_ledger_schema.php';

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
    ensureEmployeeLedgerSchema($pdo);
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $opening_balance = (float)($_POST['opening_balance'] ?? 0);

    // Log parsed variables for debugging
    error_log("Parsed vars: name='$name', mobile='$mobile', role='$role', salary='$salary'");

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

    // Check for duplicate mobile number
    $stmt = $pdo->prepare('SELECT id FROM employees WHERE mobile = ?');
    $stmt->execute([$mobile]);
    if ($stmt->fetch()) {
        error_log('Duplicate mobile number found: ' . $mobile);
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی مۆبایل پێشتر تۆمارکراوە!']);
        exit;
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO employees (name, mobile, role, salary) VALUES (?, ?, ?, ?)');
    if ($stmt->execute([$name, $mobile, $role, $salary])) {
        $employee_id = (int)$pdo->lastInsertId();

        // Opening balance => store as ledger transaction so ERP balance works from day 1
        if ($opening_balance != 0.0) {
            $operation = $opening_balance > 0 ? 'credit' : 'debit';
            $amount = abs($opening_balance);
            $desc = 'باڵانسی سەرەتا';
            $txStmt = $pdo->prepare("
                INSERT INTO employee_transactions (employee_id, type, amount, operation, transaction_date, description)
                VALUES (?, 'opening_balance', ?, ?, NOW(), ?)
            ");
            $txStmt->execute([$employee_id, $amount, $operation, $desc]);
        }

        $pdo->commit();

        error_log('Employee successfully added: ID=' . $employee_id . ', Name=' . $name . ', Mobile=' . $mobile . ', Role=' . $role);
        echo json_encode(['success' => true, 'message' => 'کارمەند بەسەرکەوتوویی زیادکرا!']);
    } else {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Failed to add employee: Name=' . $name);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن!']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('PDOException in add_employee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی کارمەند!']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Exception in add_employee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی کارمەند!']);
}
