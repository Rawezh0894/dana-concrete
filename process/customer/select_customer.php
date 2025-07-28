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
    $sql = "SELECT id, name, mobile1, mobile2, opening_debt_usd, opening_debt_iqd FROM customers ORDER BY id DESC";
    $stmt = $pdo->query($sql);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $customers]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
