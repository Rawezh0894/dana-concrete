<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !hasPermission('view_customer')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

try {
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
    if (!$customer_id) {
        echo json_encode(['success' => false, 'data' => []]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT a.id, a.date, a.adjustment_type, a.amount_usd, a.reason, a.created_at, u.name AS created_by_name
        FROM customer_account_adjustments a
        LEFT JOIN users u ON a.created_by = u.id
        WHERE a.customer_id = ?
        ORDER BY a.date DESC, a.id DESC
    ");
    $stmt->execute([$customer_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    error_log('select_account_adjustments.php error: ' . $e->getMessage());
    $rawMsg = $e->getMessage();
    $msg = 'هەڵەیەک ڕوویدا';
    if (stripos($rawMsg, 'customer_account_adjustments') !== false && stripos($rawMsg, 'doesn\'t exist') !== false) {
        $msg = 'تابڵی customer_account_adjustments بوونی نییە. تکایە SQL ی پێویست جێبەجێ بکە.';
    }
    echo json_encode(['success' => false, 'data' => [], 'msg' => $msg]);
}

