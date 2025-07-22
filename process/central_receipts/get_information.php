<?php
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Receipt ID is required']);
    exit;
}

$receipt_id = $_GET['id'];

try {
    require_once '../../config/db_conected.php';
    
   $receiptQuery = "SELECT cr.*, 
               cr.receiver_name, 
               c.name AS customer_name, 
               c.mobile1 AS customer_phone, 
               c.mobile2 AS customer_phone2,
               f.name AS formula_name, 
               f.type AS formula_type, 
               f.strength_type, 
               f.strength_kg, 
               f.strength_mpa,
               pump_car.name AS pump_car_name,
               pump_driver.name AS pump_driver_name, 
               pump_driver.mobile AS pump_driver_mobile,
               mixer_car.name AS mixer_car_name,
               mixer_driver.name AS mixer_driver_name, 
               mixer_driver.mobile AS mixer_driver_mobile
        FROM concrete_receipts cr
        LEFT JOIN customers c ON cr.customer_id = c.id
        LEFT JOIN concrete_formulas f ON cr.formulas_id = f.id
        LEFT JOIN cars pump_car ON cr.pump_car_id = pump_car.id
        LEFT JOIN employees pump_driver ON cr.pump_driver_id = pump_driver.id
        LEFT JOIN cars mixer_car ON cr.mixer_car_id = mixer_car.id
        LEFT JOIN employees mixer_driver ON cr.mixer_driver_id = mixer_driver.id
        WHERE cr.id = ?";

    
    $stmt = $pdo->prepare($receiptQuery);
    $stmt->execute([$receipt_id]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receipt) {
        http_response_code(404);
        echo json_encode(['error' => 'Receipt not found']);
        exit;
    }
    
    // Format the meter amount
    $formatted_quantity = '-';
    if (isset($receipt['meter_amount'])) {
        $formatted_quantity = rtrim(rtrim(number_format($receipt['meter_amount'], 2, '.', ','), '0'), '.');
    }
    
    // Format the date
    $formatted_date = date('d-m-Y', strtotime($receipt['created_at']));
    
    // Prepare strength information
    $strength_info = '-';
    if (!empty($receipt['strength_kg'])) {
        $strength_info = $receipt['strength_kg'] . ' Kg';
    } elseif (!empty($receipt['strength_mpa'])) {
        $strength_info = $receipt['strength_mpa'] . ' MPa';
    }
    
    // Return the formatted data
    echo json_encode([
        'success' => true,
        'receipt' => $receipt,
        'receiver_name' => $receipt['receiver_name'], // زیادکردنی وەرگر
        'formatted_quantity' => $formatted_quantity,
        'formatted_date' => $formatted_date,
        'strength_info' => $strength_info
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
