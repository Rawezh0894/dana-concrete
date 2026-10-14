<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!hasPermission('view_customer')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
        exit;
    }
    
    $sql = "SELECT id, name, mobile1, mobile2, opening_debt_usd, opening_debt_iqd, is_recipient, is_company FROM customers WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        // Convert numeric fields to proper types (TINYINT may arrive as bool/string)
        $customer['opening_debt_usd'] = floatval($customer['opening_debt_usd'] ?? 0);
        $customer['opening_debt_iqd'] = floatval($customer['opening_debt_iqd'] ?? 0);
        $customer['is_recipient'] = ((int)($customer['is_recipient'] ?? 0) === 1) ? 1 : 0;
        $customer['is_company'] = ((int)($customer['is_company'] ?? 0) === 1) ? 1 : 0;
        $customer['id'] = intval($customer['id']);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $customer], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'error' => 'Customer not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?> 