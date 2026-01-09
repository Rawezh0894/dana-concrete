<?php
/**
 * Raw Material Sales - Delete (Soft Delete)
 * Following ERP standards (Odoo, SAP, Oracle)
 * 
 * Features:
 * - Soft delete with audit trail
 * - Automatic inventory restoration to bins_silos
 * - Full transaction support
 */
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once 'get_average_price.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('delete_raw_material_sales')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە بۆ سڕینەوە!']);
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

    // Get sale data
    $saleStmt = $pdo->prepare("SELECT * FROM raw_material_sales WHERE id = ? AND is_deleted = 0");
    $saleStmt->execute([$sale_id]);
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        echo json_encode(['success' => false, 'message' => 'فرۆشتنەکە نەدۆزرایەوە یان پێشتر سڕاوەتەوە']);
        exit;
    }

    // Get bin data
    $binStmt = $pdo->prepare("SELECT * FROM bins_silos WHERE id = ?");
    $binStmt->execute([$sale['bin_id']]);
    $bin = $binStmt->fetch(PDO::FETCH_ASSOC);

    if (!$bin) {
        echo json_encode(['success' => false, 'message' => 'بین/سایلۆ نەدۆزرایەوە']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // Soft delete the sale
    $deleteSql = "
        UPDATE raw_material_sales SET 
            is_deleted = 1, 
            deleted_at = NOW(), 
            deleted_by = ? 
        WHERE id = ?
    ";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$_SESSION['user_id'], $sale_id]);

    // Restore inventory to bin
    $quantity_to_restore = floatval($sale['quantity_kg']);
    $cost_price = floatval($sale['cost_price']);
    $value_to_restore = $quantity_to_restore * $cost_price;
    
    $new_bin_amount = floatval($bin['amount']) + $quantity_to_restore;
    $new_bin_value = floatval($bin['total_value']) + $value_to_restore;

    $restoreBinSql = "UPDATE bins_silos SET amount = ?, total_value = ? WHERE id = ?";
    $restoreBinStmt = $pdo->prepare($restoreBinSql);
    $restoreBinStmt->execute([$new_bin_amount, $new_bin_value, $sale['bin_id']]);

    // Log inventory restoration
    $logSql = "
        INSERT INTO raw_material_inventory_log (
            bin_id, sale_id, movement_type, quantity_change,
            quantity_before, quantity_after, reference_doc, notes, created_by
        ) VALUES (?, ?, 'return', ?, ?, ?, ?, ?, ?)
    ";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        $sale['bin_id'],
        $sale_id,
        $quantity_to_restore, // Positive for return
        $bin['amount'],
        $new_bin_amount,
        $sale['invoice_number'],
        'سڕینەوەی فرۆشتن - گەڕاندنەوەی بڕ',
        $_SESSION['user_id']
    ]);

    // Get buyer name for notification
    $buyer_name = $sale['external_buyer_name'];
    if ($sale['buyer_type'] === 'کڕیار' && $sale['customer_id']) {
        $custStmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
        $custStmt->execute([$sale['customer_id']]);
        $buyer_name = $custStmt->fetchColumn() ?: 'Unknown';
    } elseif ($sale['buyer_type'] === 'کۆمپانیا' && $sale['company_id']) {
        $compStmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
        $compStmt->execute([$sale['company_id']]);
        $buyer_name = $compStmt->fetchColumn() ?: 'Unknown';
    }

    // Create notification
    $old_values = [
        'invoice_number' => $sale['invoice_number'],
        'sale_date' => $sale['sale_date'],
        'buyer_name' => $buyer_name,
        'material_type' => $sale['material_type'],
        'quantity_kg' => $sale['quantity_kg'],
        'total_price' => $sale['total_price'],
        'currency_type' => $sale['currency_type']
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'delete',
        'raw_material_sales',
        $sale_id,
        "سڕینەوەی فرۆشتنی مەوادی خام: {$sale['quantity_kg']} کگم {$sale['material_type']} (پسووڵە: {$sale['invoice_number']})",
        $old_values,
        null,
        [
            'action_type' => 'raw_material_sale_deletion',
            'restored_quantity' => $quantity_to_restore,
            'bin_name' => $bin['name']
        ],
        getUserIP()
    );

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'فرۆشتنەکە بە سەرکەوتووی سڕایەوە و بڕی مەواد گەڕێندرایەوە بۆ کۆگا',
        'data' => [
            'sale_id' => $sale_id,
            'restored_quantity' => $quantity_to_restore,
            'new_bin_quantity' => $new_bin_amount
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Raw Material Sales Delete Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Raw Material Sales Delete Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
}
