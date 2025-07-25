<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'نەتەوەت لۆگین بکەیت']);
    exit;
}

// Check if user has permission
if (!hasPermission('view_concrete_receipts')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'توانای دەست گەیشتنت نییە']);
    exit;
}

// Check if customer_id is provided
if (!isset($_POST['customer_id']) || empty($_POST['customer_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ناسنامەی کڕیار پێویستە']);
    exit;
}

try {
    $customer_id = $_POST['customer_id'];
    
    // Get customer information
    $customer_query = "
        SELECT 
            c.id,
            c.name as customer_name,
            c.mobile1,
            c.mobile2
        FROM customers c
        WHERE c.id = ?
    ";
    
    $customer_stmt = $pdo->prepare($customer_query);
    $customer_stmt->execute([$customer_id]);
    $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'کڕیار نەدۆزرایەوە']);
        exit;
    }
    
    // Get customer's concrete receipts
    $receipts_query = "
        SELECT 
            cr.id,
            cr.receipt_number,
            cr.date,
            cr.location,
            cr.receiver_name,
            cr.meter_amount,
            cf.name as formula_name,
            car_mixer.name as mixer_car_name,
            car_pump.name as pump_car_name,
            emp_mixer.name as mixer_driver_name,
            emp_pump.name as pump_driver_name
        FROM concrete_receipts cr
        LEFT JOIN concrete_formulas cf ON cr.formulas_id = cf.id
        LEFT JOIN cars car_mixer ON cr.mixer_car_id = car_mixer.id
        LEFT JOIN cars car_pump ON cr.pump_car_id = car_pump.id
        LEFT JOIN employees emp_mixer ON cr.mixer_driver_id = emp_mixer.id
        LEFT JOIN employees emp_pump ON cr.pump_driver_id = emp_pump.id
        WHERE cr.customer_id = ?
        ORDER BY cr.date DESC, cr.id DESC
    ";
    
    $receipts_stmt = $pdo->prepare($receipts_query);
    $receipts_stmt->execute([$customer_id]);
    $receipts = $receipts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_receipts = count($receipts);
    $total_meter_cubic = array_sum(array_column($receipts, 'meter_amount'));
    
    // Format dates
    foreach ($receipts as &$receipt) {
        $receipt['date'] = date('Y-m-d', strtotime($receipt['date']));
        $receipt['meter_amount'] = floatval($receipt['meter_amount']);
    }
    
    // Prepare response data
    $data = [
        'customer_name' => $customer['customer_name'],
        'mobile1' => $customer['mobile1'],
        'mobile2' => $customer['mobile2'],
        'total_receipts' => $total_receipts,
        'total_meter_cubic' => $total_meter_cubic,
        'receipts' => $receipts
    ];
    
    // Return response
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'هەڵە لە وەرگرتنی وردەکاری: ' . $e->getMessage()
    ]);
}
?> 