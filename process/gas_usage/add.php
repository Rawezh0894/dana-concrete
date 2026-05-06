<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!hasPermission('add_other_expenses')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێنەدراوە']);
    exit;
}

try {
    $car_id = $_POST['car_id'] ?? null;
    $gas_liters = $_POST['gas_liters'] ?? 0;
    $gas_price = $_POST['gas_price'] ?? 0;
    $gas_total_cost = $_POST['gas_total_cost'] ?? 0;
    $date = $_POST['date'] ?? date('Y-m-d');

    if (!$car_id || !$gas_liters || !$gas_total_cost) {
        echo json_encode(['success' => false, 'msg' => 'تکایە هەموو خانەکان پڕ بکەرەوە']);
        exit;
    }

    $pdo->beginTransaction();

    // 1. Check if gas is available in bins_silos
    $stmt = $pdo->prepare("SELECT id, amount FROM bins_silos WHERE material_type = 'گاز' LIMIT 1");
    $stmt->execute();
    $gas_tank = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gas_tank || $gas_tank['amount'] < $gas_liters) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'بڕی گازی پێویست لە کۆگا بەردەست نییە. بەردەست: ' . ($gas_tank['amount'] ?? 0) . ' لیتر']);
        exit;
    }

    // 2. Insert into other_expenses
    $sql = "INSERT INTO other_expenses (
                car_id, gas_liters, gas_purchase_price_input, gas_total_cost, 
                expense_type, currency_type, payment_type, date, purpose
            ) VALUES (?, ?, ?, ?, 'بەکارهێنانی گاز', 'دینار', 'نەقد', ?, ?)";
    
    $purpose = "بەکارهێنانی گاز بۆ سەیارە";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$car_id, $gas_liters, $gas_price, $gas_total_cost, $date, $purpose]);

    // 3. Deduct from bins_silos
    $updateSql = "UPDATE bins_silos 
                  SET amount = amount - ?, 
                      total_value = total_value - (? * average_price)
                  WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$gas_liters, $gas_liters, $gas_tank['id']]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'msg' => 'هەڵە لە تۆمارکردن: ' . $e->getMessage()
    ]);
}
