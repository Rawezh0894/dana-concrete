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
error_log('update_sale.php POST: ' . print_r($_POST, true));

if (!hasPermission('update_sale')) {
    error_log('Permission denied for user: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown'));
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = $_POST['edit_sale_id'] ?? null;
    $customer_id = $_POST['edit_customer_id'] ?? null;
    if ($customer_id === '' || $customer_id === '0' || $customer_id === null) {
        $customer_id = null;
    }
    $recipient_id = isset($_POST['edit_recipient_id']) && $_POST['edit_recipient_id'] !== '' ? intval($_POST['edit_recipient_id']) : null;
    $recipient = null;
    $location = $_POST['edit_location'] ?? null;
    $quantity = $_POST['edit_quantity'] ?? null;
    $price_per_unit = $_POST['edit_price_per_unit'] ?? null;
    $total_price = $_POST['edit_total_price'] ?? null;
    $payment_type = $_POST['edit_payment_type'] ?? null;
    $amount_paid_usd = $_POST['edit_amount_paid_usd'] ?? null;
    $amount_paid_iq = $_POST['edit_amount_paid_iq'] ?? null;
    $dolar_rate = $_POST['edit_dolar_rate'] ?? null;
    $remaining_amount = $_POST['edit_remaining_amount'] ?? null;
    $invoice_number = $_POST['edit_invoice_number'] ?? null;
    $order_date = $_POST['edit_order_date'] ?? null;
    $notes = $_POST['edit_notes'] ?? null;
    $formula_id = $_POST['edit_formula_id'] ?? null;
    $discount = $_POST['edit_discount'] ?? 0;

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', customer_id='$customer_id', recipient='$recipient', location='$location', quantity='$quantity', price_per_unit='$price_per_unit', total_price='$total_price', payment_type='$payment_type', amount_paid_usd='$amount_paid_usd', amount_paid_iq='$amount_paid_iq', dolar_rate='$dolar_rate', remaining_amount='$remaining_amount', invoice_number='$invoice_number', order_date='$order_date', notes='$notes', formula_id='$formula_id', discount='$discount'");

    if (!$id || !$location || !$quantity || !$price_per_unit || !$total_price || !$payment_type || !$invoice_number || !$order_date || !$formula_id) {
        error_log('Missing required fields for sale update');
        echo json_encode(['success' => false, 'message' => 'هەموو خانە پڕ بکە']);
        exit;
    }

    // Check if sale exists
    $checkStmt = $pdo->prepare('SELECT id, customer_id, payment_type, remaining_amount, invoice_number FROM sales WHERE id = ?');
    $checkStmt->execute([$id]);
    $oldSale = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$oldSale) {
        error_log('Sale not found for update: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'فرۆشتن نەدۆزرایەوە!']);
        exit;
    }

    error_log('Found sale for update: ' . print_r($oldSale, true));

    // Check for duplicate invoice_number (excluding current record)
    $dupCheck = $pdo->prepare('SELECT COUNT(*) FROM sales WHERE invoice_number = ? AND id != ?');
    $dupCheck->execute([$invoice_number, $id]);
    if ($dupCheck->fetchColumn() > 0) {
        error_log('Duplicate invoice number: ' . $invoice_number);
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی پسوڵە پێشتر تۆمارکراوە!']);
        exit;
    }

    // Update customer debt logic (simplified)
    if ($oldSale['customer_id'] !== null && $oldSale['payment_type'] === 'قەرز') {
        // No need to update customer debt_usd/debt_iqd anymore
        // The remaining amount is tracked in the sales table itself
        // This is handled by the remaining_amount field in the sales table
    }

    // Get old values BEFORE updating
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
    $stmt->execute([$id]);
    $old_record = $stmt->fetch();

    // Get old customer and formula information
    $old_customer_name = 'Unknown';
    $old_formula_name = 'Unknown';

    if ($old_record['customer_id']) {
        $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
        $stmt->execute([$old_record['customer_id']]);
        $old_customer = $stmt->fetch();
        $old_customer_name = $old_customer['name'] ?? 'Unknown';
    }

    if ($old_record['formula_id']) {
        $stmt = $pdo->prepare("SELECT name FROM concrete_formulas WHERE id = ?");
        $stmt->execute([$old_record['formula_id']]);
        $old_formula = $stmt->fetch();
        $old_formula_name = $old_formula['name'] ?? 'Unknown';
    }

    $old_values = [
        'customer_id' => $old_record['customer_id'],
        'customer_name' => $old_customer_name,
        'recipient' => $old_record['recipient'],
        'location' => $old_record['location'],
        'quantity' => $old_record['quantity'],
        'price_per_unit' => $old_record['price_per_unit'],
        'total_price' => $old_record['total_price'],
        'payment_type' => $old_record['payment_type'],
        'amount_paid_usd' => $old_record['amount_paid_usd'],
        'amount_paid_iq' => $old_record['amount_paid_iq'],
        'dolar_rate' => $old_record['dolar_rate'],
        'remaining_amount' => $old_record['remaining_amount'],
        'invoice_number' => $old_record['invoice_number'],
        'order_date' => $old_record['order_date'],
        'notes' => $old_record['notes'],
        'formula_id' => $old_record['formula_id'],
        'formula_name' => $old_formula_name,
        'discount' => $old_record['discount']
    ];

    if ($recipient_id) {
        $recipientStmt = $pdo->prepare("SELECT name FROM recipients WHERE id = ?");
        $recipientStmt->execute([$recipient_id]);
        $recipientRow = $recipientStmt->fetch(PDO::FETCH_ASSOC);
        if ($recipientRow) {
            $recipient = $recipientRow['name'];
        }
    }
    if ($recipient === null || $recipient === '') {
        $recipient = $_POST['edit_recipient'] ?? $old_record['recipient'];
    }

    // Now perform the update
    $stmt = $pdo->prepare("UPDATE sales SET customer_id=?, recipient=?, location=?, quantity=?, price_per_unit=?, total_price=?, payment_type=?, amount_paid_usd=?, amount_paid_iq=?, dolar_rate=?, remaining_amount=?, invoice_number=?, order_date=?, notes=?, formula_id=?, discount=? WHERE id=?");
    $result = $stmt->execute([
        $customer_id,
        $recipient,
        $location,
        $quantity,
        $price_per_unit,
        $total_price,
        $payment_type,
        $amount_paid_usd,
        $amount_paid_iq,
        $dolar_rate,
        $remaining_amount,
        $invoice_number,
        $order_date,
        $notes,
        $formula_id,
        $discount,
        $id
    ]);

    if ($result && $stmt->rowCount() > 0) {
        // Get customer and formula information for notification
        $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch();
        $customer_name = $customer['name'] ?? 'Unknown';

        $stmt = $pdo->prepare("SELECT name FROM concrete_formulas WHERE id = ?");
        $stmt->execute([$formula_id]);
        $formula = $stmt->fetch();
        $formula_name = $formula['name'] ?? 'Unknown';

        $new_values = [
            'customer_id' => $customer_id,
            'customer_name' => $customer_name,
            'recipient' => $recipient,
            'location' => $location,
            'quantity' => $quantity,
            'price_per_unit' => $price_per_unit,
            'total_price' => $total_price,
            'payment_type' => $payment_type,
            'amount_paid_usd' => $amount_paid_usd,
            'amount_paid_iq' => $amount_paid_iq,
            'dolar_rate' => $dolar_rate,
            'remaining_amount' => $remaining_amount,
            'invoice_number' => $invoice_number,
            'order_date' => $order_date,
            'notes' => $notes,
            'formula_id' => $formula_id,
            'formula_name' => $formula_name,
            'discount' => $discount
        ];

        $additional_info = [
            'action_type' => 'sale_update',
            'payment_status' => $payment_type === 'نەقد' ? 'paid' : 'credit',
            'currency_used' => $amount_paid_usd > 0 ? 'USD' : ($amount_paid_iq > 0 ? 'IQD' : 'none'),
            'total_paid' => $amount_paid_usd + $amount_paid_iq,
            'remaining_debt' => $remaining_amount
        ];

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'update',
            'sales',
            $id,
            "فرۆشتنەکە نوێکرایەوە (invoice: $invoice_number, کڕیار: $customer_name, فۆرمۆلا: $formula_name)",
            $old_values,
            $new_values,
            $additional_info,
            getUserIP()
        );

        error_log('Sale successfully updated: ID=' . $id . ', Invoice=' . $invoice_number . ', Customer=' . $customer_name);
        echo json_encode(['success' => true, 'message' => 'فرۆشتن نوێکرایەوە!']);
    } else {
        error_log('No rows affected when updating sale: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'هیچ گۆڕانکارییەک نەکرا!']);
    }
} catch (PDOException $e) {
    error_log('PDOException in update_sale.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in update_sale.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
