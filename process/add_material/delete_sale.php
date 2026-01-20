<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

try {
    $sale_id = $_POST['id'];
    
    // Begin transaction
    $pdo->beginTransaction();

    // 1. Get Sale Details & Material Info
    $stmt = $pdo->prepare("
        SELECT ms.*, lm.unit_type as base_unit,
               lm.pieces_per_carton, lm.buckets_per_barrel, lm.liters_per_bucket, lm.liters_per_barrel
        FROM material_sales ms
        JOIN list_materials lm ON ms.material_id = lm.id
        WHERE ms.id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception("Sale not found");
    }

    // 2. Calculate Quantity to Restore (Base Unit)
    $quantity_to_restore = $sale['quantity'];
    
    // logic same as sell.php but reverse not needed, just restore what was deducted
    // Wait, sell.php calculates what to deduct based on inputs.
    // The DB stores the 'sold quantity' and 'sold unit'.
    // We need to convert THAT sold quantity back to base unit.
    
    if ($sale['unit'] != $sale['base_unit']) {
        if ($sale['base_unit'] == 'کارتۆن' && $sale['unit'] == 'دانە') {
            $quantity_to_restore = $sale['quantity'] / $sale['pieces_per_carton'];
        } elseif ($sale['base_unit'] == 'بەرمیل') {
            if ($sale['unit'] == 'دەبە') {
                $quantity_to_restore = $sale['quantity'] / $sale['buckets_per_barrel'];
            } elseif ($sale['unit'] == 'لیتر') {
                $quantity_to_restore = $sale['quantity'] / $sale['liters_per_barrel'];
            }
        } elseif ($sale['base_unit'] == 'دەبە' && $sale['unit'] == 'لیتر') {
            $quantity_to_restore = $sale['quantity'] / $sale['liters_per_bucket'];
        }
    }

    // 3. Restore Stock
    $updateStmt = $pdo->prepare("UPDATE list_materials SET quantity = quantity + ? WHERE id = ?");
    $updateStmt->execute([$quantity_to_restore, $sale['material_id']]);

    // 4. Delete Sale Record
    $deleteStmt = $pdo->prepare("DELETE FROM material_sales WHERE id = ?");
    $deleteStmt->execute([$sale_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
