<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!hasPermission('view_customer')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $sql = 'SELECT id, name, mobile1, mobile2, opening_debt_usd, opening_debt_iqd, is_recipient, is_company
            FROM customers ORDER BY id DESC';
    $stmt = $pdo->query($sql);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($customers as &$customer) {
        $customer['id'] = (int)$customer['id'];
        $customer['opening_debt_usd'] = (float)($customer['opening_debt_usd'] ?? 0);
        $customer['opening_debt_iqd'] = (float)($customer['opening_debt_iqd'] ?? 0);
        $customer['is_recipient'] = ((int)($customer['is_recipient'] ?? 0) === 1) ? 1 : 0;
        $customer['is_company'] = ((int)($customer['is_company'] ?? 0) === 1) ? 1 : 0;
    }
    unset($customer);

    echo json_encode(['success' => true, 'data' => $customers], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Exception in select_customer.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
