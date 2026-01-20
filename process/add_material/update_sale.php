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
    $new_quantity = floatval($_POST['quantity']);
    $new_unit = $_POST['unit'];
    $new_price = floatval($_POST['price']);
    $new_total_price = floatval($_POST['total_price']);
    $new_date = $_POST['date'];
    $new_note = $_POST['note'];
    $new_currency = $_POST['currency'];

    if ($new_quantity <= 0) {
        throw new Exception("بڕ نابێت سفر یان کەمتر بێت");
    }

    $pdo->beginTransaction();

    // 1. Get Old Sale & Material Info
    $stmt = $pdo->prepare("
        SELECT ms.*, lm.unit_type as base_unit, lm.quantity as current_stock,
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

    // A. Revert Old Quantity
    $restore_qty = $sale['quantity'];
    if ($sale['unit'] != $sale['base_unit']) {
        if ($sale['base_unit'] == 'کارتۆن' && $sale['unit'] == 'دانە') {
            $restore_qty = $sale['quantity'] / $sale['pieces_per_carton'];
        } elseif ($sale['base_unit'] == 'بەرمیل') {
             if ($sale['unit'] == 'دەبە') {
                 $restore_qty = $sale['quantity'] / $sale['buckets_per_barrel'];
             } elseif ($sale['unit'] == 'لیتر') {
                 $restore_qty = $sale['quantity'] / $sale['liters_per_barrel'];
             }
        } elseif ($sale['base_unit'] == 'دەبە' && $sale['unit'] == 'لیتر') {
            $restore_qty = $sale['quantity'] / $sale['liters_per_bucket'];
        }
    }

    // B. Calculate New Deduction
    $deduct_qty = $new_quantity;
    if ($new_unit != $sale['base_unit']) {
         if ($sale['base_unit'] == 'کارتۆن' && $new_unit == 'دانە') {
            $deduct_qty = $new_quantity / $sale['pieces_per_carton'];
        } elseif ($sale['base_unit'] == 'بەرمیل') {
             if ($new_unit == 'دەبە') {
                 $deduct_qty = $new_quantity / $sale['buckets_per_barrel'];
             } elseif ($new_unit == 'لیتر') {
                 $deduct_qty = $new_quantity / $sale['liters_per_barrel'];
             }
        } elseif ($sale['base_unit'] == 'دەبە' && $new_unit == 'لیتر') {
            $deduct_qty = $new_quantity / $sale['liters_per_bucket'];
        }
    }
    
    // Net Change: +Restore - Deduct
    // Stock = Stock + Restore - Deduct
    
    // Check feasibility
    // Available after restore = current_stock + restore_qty
    // Need deduct_qty from that
    
    $potential_stock = $sale['current_stock'] + $restore_qty;
    
    if ($deduct_qty > $potential_stock) {
        throw new Exception("بڕی پێویست بەردەست نییە (دوای گەڕاندنەوە).");
    }

    // 2. Update Stock
    $net_change = $restore_qty - $deduct_qty; // positive means we add back to stock, negative means we take more
    // update list_materials set quantity = quantity + (net_change)
    
    $updateStock = $pdo->prepare("UPDATE list_materials SET quantity = quantity + ? WHERE id = ?");
    $updateStock->execute([$net_change, $sale['material_id']]);

    // 3. Update Sale Record
    $updateSale = $pdo->prepare("
        UPDATE material_sales SET 
            quantity = ?, unit = ?, price = ?, total_price = ?, currency = ?, date = ?, note = ?
        WHERE id = ?
    ");
    $updateSale->execute([
        $new_quantity, $new_unit, $new_price, $new_total_price, $new_currency, $new_date, $new_note, $sale_id
    ]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
