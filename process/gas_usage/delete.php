<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!hasPermission('delete_other_expenses')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێنەدراوە']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'msg' => 'ID دیاری نەکراوە']);
        exit;
    }

    $pdo->beginTransaction();

    // 1. Get record details to restore stock
    $stmt = $pdo->prepare("SELECT gas_liters, expense_type FROM other_expenses WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'تۆمارەکە نەدۆزرایەوە']);
        exit;
    }

    // 2. Restore stock in bins_silos if it was a gas usage
    if ($record['expense_type'] === 'بەکارهێنانی گاز' && $record['gas_liters'] > 0) {
        $updateSql = "UPDATE bins_silos 
                      SET amount = amount + ?, 
                          total_value = total_value + (? * average_price)
                      WHERE material_type = 'گاز' LIMIT 1";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$record['gas_liters'], $record['gas_liters']]);
    }

    // 3. Delete from other_expenses
    $deleteStmt = $pdo->prepare("DELETE FROM other_expenses WHERE id = ?");
    $deleteStmt->execute([$id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'msg' => 'هەڵە لە سڕینەوە: ' . $e->getMessage()
    ]);
}
