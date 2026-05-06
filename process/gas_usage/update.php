<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!hasPermission('edit_other_expenses')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێنەدراوە']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    $car_id = $_POST['car_id'] ?? null;
    $gas_liters = $_POST['gas_liters'] ?? 0;
    $gas_price = $_POST['gas_price'] ?? 0;
    $gas_total_cost = $_POST['gas_total_cost'] ?? 0;
    $date = $_POST['date'] ?? date('Y-m-d');

    if (!$id || !$car_id || !$gas_liters || !$gas_total_cost) {
        echo json_encode(['success' => false, 'msg' => 'تکایە هەموو خانەکان پڕ بکەرەوە']);
        exit;
    }

    $pdo->beginTransaction();

    // 1. Get old record details to adjust stock
    $stmt = $pdo->prepare("SELECT gas_liters FROM other_expenses WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $old_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$old_record) {
        throw new Exception("تۆمارەکە نەدۆزرایەوە");
    }

    $old_liters = $old_record['gas_liters'];
    $diff_liters = $gas_liters - $old_liters;

    // 2. Check if enough gas is available if we are increasing the amount
    $stmt = $pdo->prepare("SELECT id, amount FROM bins_silos WHERE material_type = 'گاز' LIMIT 1 FOR UPDATE");
    $stmt->execute();
    $gas_tank = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gas_tank || $gas_tank['amount'] < $diff_liters) {
        throw new Exception("بڕی گازی پێویست لە کۆگا بەردەست نییە. بەردەست: " . ($gas_tank['amount'] ?? 0) . " لیتر");
    }

    // 3. Update other_expenses
    $sql = "UPDATE other_expenses SET 
                car_id = ?, 
                gas_liters = ?, 
                gas_purchase_price_input = ?, 
                gas_total_cost = ?, 
                date = ?
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$car_id, $gas_liters, $gas_price, $gas_total_cost, $date, $id]);

    // 4. Update bins_silos
    $updateSql = "UPDATE bins_silos 
                  SET amount = amount - ?, 
                      total_value = total_value - (? * average_price)
                  WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$diff_liters, $diff_liters, $gas_tank['id']]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'msg' => 'هەڵە لە نوێکردنەوە: ' . $e->getMessage()
    ]);
}
