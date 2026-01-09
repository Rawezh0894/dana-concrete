<?php
/**
 * Raw Material Sales - Update Sale
 * Following ERP standards (Odoo, SAP, Oracle)
 * 
 * Features:
 * - Inventory adjustment when quantity changes
 * - Profit recalculation
 * - Full audit trail
 */
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('update_raw_material_sales')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە بۆ نوێکردنەوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $sale_id = intval($_POST['id'] ?? 0);
    
    if ($sale_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی فرۆشتن نادروستە']);
        exit;
    }

    // Get existing sale data
    $existingStmt = $pdo->prepare("SELECT * FROM raw_material_sales WHERE id = ? AND is_deleted = 0");
    $existingStmt->execute([$sale_id]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'فرۆشتنەکە نەدۆزرایەوە']);
        exit;
    }

    // Get and validate input data
    $invoice_number = trim($_POST['invoice_number'] ?? $existing['invoice_number']);
    $sale_date = $_POST['sale_date'] ?? $existing['sale_date'];
    $buyer_type = $_POST['buyer_type'] ?? $existing['buyer_type'];
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
    $company_id = !empty($_POST['company_id']) ? intval($_POST['company_id']) : null;
    $external_buyer_name = trim($_POST['external_buyer_name'] ?? $existing['external_buyer_name']);
    $external_buyer_phone = trim($_POST['external_buyer_phone'] ?? $existing['external_buyer_phone']);
    $bin_id = intval($_POST['bin_id'] ?? $existing['bin_id']);
    $quantity_kg = floatval($_POST['quantity_kg'] ?? $existing['quantity_kg']);
    $unit_price = floatval($_POST['unit_price'] ?? $existing['unit_price']);
    $payment_type = $_POST['payment_type'] ?? $existing['payment_type'];
    $paid_amount = floatval($_POST['paid_amount'] ?? $existing['paid_amount']);
    $exchange_rate = floatval($_POST['exchange_rate'] ?? $existing['exchange_rate']);
    $notes = trim($_POST['notes'] ?? $existing['notes']);

    // Validate required fields
    if (empty($invoice_number)) {
        echo json_encode(['success' => false, 'message' => 'ژمارەی پسووڵە پێویستە']);
        exit;
    }
    
    if ($quantity_kg <= 0) {
        echo json_encode(['success' => false, 'message' => 'بڕی کیلۆگرام پێویستە و دەبێت لە سفر زیاتر بێت']);
        exit;
    }
    
    if ($unit_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'نرخی یەکە پێویستە و دەبێت لە سفر زیاتر بێت']);
        exit;
    }

    // Check for duplicate invoice number (excluding current record)
    $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM raw_material_sales WHERE invoice_number = ? AND id != ?");
    $dupStmt->execute([$invoice_number, $sale_id]);
    if ($dupStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی پسووڵە پێشتر تۆمارکراوە']);
        exit;
    }

    // Get current bin information
    $binStmt = $pdo->prepare("SELECT * FROM bins_silos WHERE id = ?");
    $binStmt->execute([$bin_id]);
    $bin = $binStmt->fetch(PDO::FETCH_ASSOC);

    if (!$bin) {
        echo json_encode(['success' => false, 'message' => 'بین/سایلۆ نەدۆزرایەوە']);
        exit;
    }

    // Calculate quantity difference for inventory adjustment
    $old_quantity = floatval($existing['quantity_kg']);
    $old_bin_id = intval($existing['bin_id']);
    $quantity_diff = $quantity_kg - $old_quantity;

    // Check if bin changed
    $bin_changed = ($bin_id !== $old_bin_id);
    
    if ($bin_changed) {
        // If bin changed, need to restore old bin and deduct from new bin
        // Get old bin info
        $oldBinStmt = $pdo->prepare("SELECT * FROM bins_silos WHERE id = ?");
        $oldBinStmt->execute([$old_bin_id]);
        $old_bin = $oldBinStmt->fetch(PDO::FETCH_ASSOC);
        
        // Check new bin has enough quantity
        if (floatval($bin['amount']) < $quantity_kg) {
            echo json_encode([
                'success' => false, 
                'message' => 'بڕی داواکراو لە بڕی بەردەست زیاترە. بڕی بەردەست: ' . number_format($bin['amount'], 2) . ' کگم'
            ]);
            exit;
        }
    } else {
        // Same bin - check if new quantity is available
        $available = floatval($bin['amount']) + $old_quantity; // Add back old quantity
        if ($available < $quantity_kg) {
            echo json_encode([
                'success' => false, 
                'message' => 'بڕی داواکراو لە بڕی بەردەست زیاترە. بڕی بەردەست: ' . number_format($available, 2) . ' کگم'
            ]);
            exit;
        }
    }

    // Determine currency type based on material
    $material_type = $bin['material_type'];
    $usd_materials = ['چیمەنتۆ', 'دەرمان'];
    $currency_type = in_array($material_type, $usd_materials) ? 'دۆلار' : 'دینار';

    // Calculate total price
    $total_price = $quantity_kg * $unit_price;

    // Get average cost price
    $cost_price = floatval($bin['average_price'] ?? 0);
    $total_cost = $quantity_kg * $cost_price;
    $profit_amount = $total_price - $total_cost;

    // Calculate remaining amount
    $remaining_amount = 0;
    if ($payment_type === 'قەرز') {
        $remaining_amount = $total_price - $paid_amount;
        if ($remaining_amount < 0) $remaining_amount = 0;
    } else {
        $paid_amount = $total_price;
    }

    // Start transaction
    $pdo->beginTransaction();

    // Update the sale
    $updateSql = "
        UPDATE raw_material_sales SET
            invoice_number = ?, sale_date = ?, buyer_type = ?, customer_id = ?,
            company_id = ?, external_buyer_name = ?, external_buyer_phone = ?,
            bin_id = ?, material_type = ?, quantity_kg = ?, currency_type = ?,
            unit_price = ?, total_price = ?, cost_price = ?, profit_amount = ?,
            payment_type = ?, paid_amount = ?, remaining_amount = ?,
            exchange_rate = ?, notes = ?, updated_by = ?, updated_at = NOW()
        WHERE id = ?
    ";
    
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        $invoice_number,
        $sale_date,
        $buyer_type,
        $customer_id,
        $company_id,
        $external_buyer_name,
        $external_buyer_phone,
        $bin_id,
        $material_type,
        $quantity_kg,
        $currency_type,
        $unit_price,
        $total_price,
        $cost_price,
        $profit_amount,
        $payment_type,
        $paid_amount,
        $remaining_amount,
        $exchange_rate,
        $notes,
        $_SESSION['user_id'],
        $sale_id
    ]);

    // Handle inventory adjustments
    if ($bin_changed) {
        // Restore old bin quantity
        $oldBinStmt = $pdo->prepare("SELECT * FROM bins_silos WHERE id = ?");
        $oldBinStmt->execute([$old_bin_id]);
        $old_bin = $oldBinStmt->fetch(PDO::FETCH_ASSOC);
        
        $new_old_bin_amount = floatval($old_bin['amount']) + $old_quantity;
        $old_cost_price = floatval($existing['cost_price']);
        $value_restored = $old_quantity * $old_cost_price;
        $new_old_bin_value = floatval($old_bin['total_value']) + $value_restored;
        
        $restoreOldBinSql = "UPDATE bins_silos SET amount = ?, total_value = ? WHERE id = ?";
        $pdo->prepare($restoreOldBinSql)->execute([$new_old_bin_amount, $new_old_bin_value, $old_bin_id]);
        
        // Log restoration
        $logSql = "INSERT INTO raw_material_inventory_log (bin_id, sale_id, movement_type, quantity_change, quantity_before, quantity_after, reference_doc, notes, created_by) VALUES (?, ?, 'return', ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($logSql)->execute([
            $old_bin_id, $sale_id, $old_quantity, $old_bin['amount'], $new_old_bin_amount,
            $invoice_number, 'گۆڕینی بین - گەڕاندنەوە', $_SESSION['user_id']
        ]);
        
        // Deduct from new bin
        $new_bin_amount = floatval($bin['amount']) - $quantity_kg;
        $value_deducted = ($bin['amount'] > 0) ? (floatval($bin['total_value']) / $bin['amount']) * $quantity_kg : 0;
        $new_bin_value = floatval($bin['total_value']) - $value_deducted;
        
        $deductNewBinSql = "UPDATE bins_silos SET amount = ?, total_value = ? WHERE id = ?";
        $pdo->prepare($deductNewBinSql)->execute([$new_bin_amount, $new_bin_value, $bin_id]);
        
        // Log deduction
        $pdo->prepare($logSql)->execute([
            $bin_id, $sale_id, -$quantity_kg, $bin['amount'], $new_bin_amount,
            $invoice_number, 'گۆڕینی بین - کەمکردنەوە', $_SESSION['user_id']
        ]);
        
    } else if ($quantity_diff != 0) {
        // Same bin, quantity changed
        $current_bin_amount = floatval($bin['amount']);
        $new_bin_amount = $current_bin_amount - $quantity_diff;
        
        // Adjust total value
        $current_total_value = floatval($bin['total_value']);
        if ($quantity_diff > 0) {
            // More sold - deduct value
            $value_change = ($bin['amount'] > 0) ? ($current_total_value / ($current_bin_amount + $old_quantity)) * $quantity_diff : 0;
            $new_total_value = $current_total_value - $value_change;
        } else {
            // Less sold - restore value
            $value_change = abs($quantity_diff) * $cost_price;
            $new_total_value = $current_total_value + $value_change;
        }
        
        $updateBinSql = "UPDATE bins_silos SET amount = ?, total_value = ? WHERE id = ?";
        $pdo->prepare($updateBinSql)->execute([$new_bin_amount, $new_total_value, $bin_id]);
        
        // Log adjustment
        $logSql = "INSERT INTO raw_material_inventory_log (bin_id, sale_id, movement_type, quantity_change, quantity_before, quantity_after, reference_doc, notes, created_by) VALUES (?, ?, 'adjustment', ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($logSql)->execute([
            $bin_id, $sale_id, -$quantity_diff, $current_bin_amount, $new_bin_amount,
            $invoice_number, 'نوێکردنەوەی فرۆشتن', $_SESSION['user_id']
        ]);
    }

    // Get buyer name for notification
    $buyer_name = $external_buyer_name;
    if ($buyer_type === 'کڕیار' && $customer_id) {
        $custStmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
        $custStmt->execute([$customer_id]);
        $buyer_name = $custStmt->fetchColumn() ?: 'Unknown';
    } elseif ($buyer_type === 'کۆمپانیا' && $company_id) {
        $compStmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
        $compStmt->execute([$company_id]);
        $buyer_name = $compStmt->fetchColumn() ?: 'Unknown';
    }

    // Create notification
    $old_values = [
        'invoice_number' => $existing['invoice_number'],
        'quantity_kg' => $existing['quantity_kg'],
        'unit_price' => $existing['unit_price'],
        'total_price' => $existing['total_price'],
        'payment_type' => $existing['payment_type'],
        'paid_amount' => $existing['paid_amount'],
        'remaining_amount' => $existing['remaining_amount']
    ];

    $new_values = [
        'invoice_number' => $invoice_number,
        'quantity_kg' => $quantity_kg,
        'unit_price' => $unit_price,
        'total_price' => $total_price,
        'payment_type' => $payment_type,
        'paid_amount' => $paid_amount,
        'remaining_amount' => $remaining_amount
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'update',
        'raw_material_sales',
        $sale_id,
        "نوێکردنەوەی فرۆشتنی مەوادی خام: پسووڵە {$invoice_number}",
        $old_values,
        $new_values,
        ['buyer_name' => $buyer_name],
        getUserIP()
    );

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'فرۆشتنەکە بە سەرکەوتووی نوێکرایەوە',
        'data' => [
            'sale_id' => $sale_id,
            'total_price' => $total_price,
            'profit_amount' => $profit_amount,
            'remaining_amount' => $remaining_amount
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Raw Material Sales Update Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Raw Material Sales Update Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
}
