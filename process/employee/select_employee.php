<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for employees retrieval');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('view_employee')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to view employees');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $stmt = $pdo->query('SELECT id, name, mobile, role, salary FROM employees ORDER BY id DESC');
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Employees retrieved successfully: Count=' . count($employees));
    echo json_encode($employees);
    
} catch (PDOException $e) {
    error_log('PDOException in select_employee.php: ' . $e->getMessage());
    echo json_encode([]);
} catch (Exception $e) {
    error_log('Exception in select_employee.php: ' . $e->getMessage());
    echo json_encode([]);
}
