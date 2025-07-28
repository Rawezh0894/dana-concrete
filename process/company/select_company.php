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
    error_log('User not logged in for companies retrieval');
    http_response_code(401);
    echo json_encode(['error' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('view_accounts')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to view companies');
    http_response_code(403);
    echo json_encode(['error' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $stmt = $pdo->query('SELECT id, name, opening_debt_usd, opening_debt_iqd, currency_type FROM company ORDER BY id ASC');
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Companies retrieved successfully: Count=' . count($companies));
    echo json_encode(['success' => true, 'data' => $companies]);
    
} catch (PDOException $e) {
    error_log('PDOException in company/select_company.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in company/select_company.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
?>
