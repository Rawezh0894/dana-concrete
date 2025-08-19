<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

header('Content-Type: application/json');
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('delete_sale.php POST: ' . print_r($_POST, true));

if (!hasPermission('delete_sale')) {
    error_log('Permission denied for user: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown'));
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

$id = $_POST['id'] ?? null;
if (!$id) {
    error_log('No sale ID provided for deletion');
    echo json_encode(['success' => false, 'message' => 'ناسنامەی فرۆشتن نادیارە']);
    exit;
}

try {
    // Get full sale info before delete
    $stmt = $pdo->prepare('SELECT * FROM sales WHERE id = ?');
    $stmt->execute([$id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        error_log('Sale not found for ID: ' . $id);
        echo json_encode(['success' => false, 'message' => 'فرۆشتن نەدۆزرایەوە!']);
        exit;
    }

    error_log('Found sale for deletion: ' . print_r($sale, true));

    // Validate invoice_number length before copying to recycle bin
    if (strlen($sale['invoice_number']) > 100) {
        error_log('Invoice number too long for recycle bin: ' . $sale['invoice_number'] . ' (length: ' . strlen($sale['invoice_number']) . ')');
        // Try to truncate the invoice number for recycle bin
        $truncatedInvoiceNumber = substr($sale['invoice_number'], 0, 100);
        error_log('Truncated invoice number for recycle bin: ' . $truncatedInvoiceNumber);
    } else {
        $truncatedInvoiceNumber = $sale['invoice_number'];
    }

    // Copy to recycle_bin_sales
    $copyStmt = $pdo->prepare('INSERT INTO recycle_bin_sales (
        original_id, customer_id, recipient, location, quantity, price_per_unit, total_price, payment_type, amount_paid_usd, amount_paid_iq, dolar_rate, remaining_amount, invoice_number, order_date, notes, formula_id, discount
    ) VALUES (
        :original_id, :customer_id, :recipient, :location, :quantity, :price_per_unit, :total_price, :payment_type, :amount_paid_usd, :amount_paid_iq, :dolar_rate, :remaining_amount, :invoice_number, :order_date, :notes, :formula_id, :discount
    )');
    $copyOk = $copyStmt->execute([
        ':original_id' => $sale['id'],
        ':customer_id' => $sale['customer_id'],
        ':recipient' => $sale['recipient'],
        ':location' => $sale['location'],
        ':quantity' => $sale['quantity'],
        ':price_per_unit' => $sale['price_per_unit'],
        ':total_price' => $sale['total_price'],
        ':payment_type' => $sale['payment_type'],
        ':amount_paid_usd' => $sale['amount_paid_usd'],
        ':amount_paid_iq' => $sale['amount_paid_iq'],
        ':dolar_rate' => $sale['dolar_rate'],
        ':remaining_amount' => $sale['remaining_amount'],
        ':invoice_number' => $truncatedInvoiceNumber,
        ':order_date' => $sale['order_date'],
        ':notes' => $sale['notes'],
        ':formula_id' => $sale['formula_id'],
        ':discount' => $sale['discount'],
    ]);
    if (!$copyOk) {
        error_log('Failed to copy sale to recycle bin: ' . print_r($copyStmt->errorInfo(), true));
        echo json_encode(['success' => false, 'message' => 'هەڵە لە گواستنەوە بۆ ڕیسایکڵ بین!']);
        exit;
    }

    // Update customer debt
    if ($sale['payment_type'] === 'قەرز') {
        // No need to update customer debt_usd/debt_iqd anymore
        // The remaining amount is tracked in the sales table itself
        // This is handled by the remaining_amount field in the sales table
    }

    // Get customer and formula information for notification
    $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
    $stmt->execute([$sale['customer_id']]);
    $customer = $stmt->fetch();
    $customer_name = $customer['name'] ?? 'Unknown';

    $stmt = $pdo->prepare("SELECT name FROM concrete_formulas WHERE id = ?");
    $stmt->execute([$sale['formula_id']]);
    $formula = $stmt->fetch();
    $formula_name = $formula['name'] ?? 'Unknown';

    // Create old values for notification
    $old_values = [
        'customer_id' => $sale['customer_id'],
        'customer_name' => $customer_name,
        'recipient' => $sale['recipient'],
        'location' => $sale['location'],
        'quantity' => $sale['quantity'],
        'price_per_unit' => $sale['price_per_unit'],
        'total_price' => $sale['total_price'],
        'payment_type' => $sale['payment_type'],
        'amount_paid_usd' => $sale['amount_paid_usd'],
        'amount_paid_iq' => $sale['amount_paid_iq'],
        'dolar_rate' => $sale['dolar_rate'],
        'remaining_amount' => $sale['remaining_amount'],
        'invoice_number' => $sale['invoice_number'],
        'order_date' => $sale['order_date'],
        'notes' => $sale['notes'],
        'formula_id' => $sale['formula_id'],
        'formula_name' => $formula_name,
        'discount' => $sale['discount']
    ];

    $additional_info = [
        'action_type' => 'sale_deletion',
        'payment_status' => $sale['payment_type'] === 'نەقد' ? 'paid' : 'credit',
        'currency_used' => $sale['amount_paid_usd'] > 0 ? 'USD' : ($sale['amount_paid_iq'] > 0 ? 'IQD' : 'none'),
        'total_paid' => $sale['amount_paid_usd'] + $sale['amount_paid_iq'],
        'remaining_debt' => $sale['remaining_amount']
    ];

    // Add note about invoice number truncation if it occurred
    if (strlen($sale['invoice_number']) > 100) {
        $additional_info['invoice_truncated'] = true;
        $additional_info['original_invoice_length'] = strlen($sale['invoice_number']);
        $additional_info['truncated_invoice'] = $truncatedInvoiceNumber;
    }

    $stmt = $pdo->prepare('DELETE FROM sales WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount()) {
        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'delete',
            'sales',
            $id,
            "فرۆشتنەکە سڕایەوە (invoice: {$sale['invoice_number']}, کڕیار: $customer_name, فۆرمۆلا: $formula_name)",
            $old_values,
            null, // No new values for delete
            $additional_info,
            getUserIP()
        );

        error_log('Sale successfully deleted: ID=' . $id . ', Invoice=' . $sale['invoice_number'] . ', Customer=' . $customer_name);
        echo json_encode(['success' => true, 'message' => 'فرۆشتن بەسەرکەوتوویی سڕایەوە!']);
    } else {
        error_log('No rows affected when deleting sale: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'فرۆشتن نەدۆزرایەوە!']);
    }
} catch (PDOException $e) {
    error_log('PDOException in delete_sale.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in delete_sale.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
