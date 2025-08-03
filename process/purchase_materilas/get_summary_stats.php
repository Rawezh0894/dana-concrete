<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check permission
if (!hasPermission('view_materials')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get total number of purchases (unique receipt numbers)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT receipt_number) as total_purchases
        FROM purchase_materials
    ");
    $stmt->execute();
    $totalPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total_purchases'];

    // Get total purchase value in USD
    $stmt = $pdo->prepare("
        SELECT SUM(total_price_usd) as total_purchase_value_usd
        FROM purchase_materials
    ");
    $stmt->execute();
    $totalPurchaseValue = $stmt->fetch(PDO::FETCH_ASSOC)['total_purchase_value_usd'] ?? 0;

    // Get total number of unique suppliers
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT person_id) as total_suppliers
        FROM purchase_materials
    ");
    $stmt->execute();
    $totalSuppliers = $stmt->fetch(PDO::FETCH_ASSOC)['total_suppliers'];

    // Prepare summary data
    $summary = [
        'total_purchases' => (int)$totalPurchases,
        'total_purchase_value_usd' => (float)$totalPurchaseValue,
        'total_suppliers' => (int)$totalSuppliers
    ];

    echo json_encode([
        'success' => true,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    error_log("Error in get_summary_stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 