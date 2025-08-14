<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check if user has permission to view concrete receipts
if (!hasPermission('view_concrete_receipts')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Receipt ID is required']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Get the single receipt with all necessary data
    $stmt = $pdo->prepare("
        SELECT 
            cr.id,
            cr.receipt_number,
            cr.customer_id,
            cr.location,
            cr.receiver_name,
            cr.meter_amount,
            cr.formulas_id,
            cr.pump_car_id,
            cr.pump_driver_id,
            cr.mixer_car_id,
            cr.mixer_driver_id,
            cr.created_at,
            c.name as customer_name,
            cf.name as formula_name,
            pc.name as pump_car_name,
            pd.name as pump_driver_name,
            mc.name as mixer_car_name,
            md.name as mixer_driver_name
        FROM concrete_receipts cr
        LEFT JOIN customers c ON cr.customer_id = c.id
        LEFT JOIN concrete_formulas cf ON cr.formulas_id = cf.id
        LEFT JOIN cars pc ON cr.pump_car_id = pc.id
        LEFT JOIN employees pd ON cr.pump_driver_id = pd.id
        LEFT JOIN cars mc ON cr.mixer_car_id = mc.id
        LEFT JOIN employees md ON cr.mixer_driver_id = md.id
        WHERE cr.id = ?
    ");
    
    $stmt->execute([$id]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receipt) {
        echo json_encode(['success' => false, 'message' => 'Receipt not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $receipt
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
