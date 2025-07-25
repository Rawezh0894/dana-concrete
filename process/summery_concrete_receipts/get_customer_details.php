<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!hasPermission('view_concrete_receipts')) {
    echo json_encode(['success' => false, 'message' => 'No permission']);
    exit;
}

try {
    $customer_id = $_GET['customer_id'] ?? '';

    if (empty($customer_id)) {
        echo json_encode(['success' => false, 'message' => 'Customer ID is required']);
        exit;
    }

    // Get customer details
    $query = "
        SELECT 
            cr.id,
            cr.receipt_number,
            cr.location,
            cr.receiver_name,
            cr.meter_amount,
            cr.created_at,
            cf.name as formula_name
        FROM concrete_receipts cr
        LEFT JOIN concrete_formulas cf ON cr.formulas_id = cf.id
        WHERE cr.customer_id = ?
        ORDER BY cr.created_at DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$customer_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'details' => $details
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 