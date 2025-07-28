<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('add_sale.php POST: ' . print_r($_POST, true));

if (!hasPermission('add_sale')) {
    error_log('Permission denied for user: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown'));
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json');

try {
    $customer_id = $_POST['customer_id'] ?? null;
    if ($customer_id === '' || $customer_id === null) {
        $customer_id = null;
    }
    $recipient = $_POST['recipient'] ?? null;
    $location = $_POST['location'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $price_per_unit = $_POST['price_per_unit'] ?? null;
    $total_price = $_POST['total_price'] ?? null;
    $payment_type = $_POST['payment_type'] ?? null;
    $amount_paid_usd = $_POST['amount_paid_usd'] ?? null;
    $amount_paid_iq = $_POST['amount_paid_iq'] ?? null;
    $dolar_rate = $_POST['dolar_rate'] ?? null;
    $remaining_amount = $_POST['remaining_amount'] ?? null;
    $invoice_number = $_POST['invoice_number'] ?? null;
    $order_date = $_POST['order_date'] ?? null;
    $notes = $_POST['notes'] ?? null;
    $formula_id = $_POST['formula_id'] ?? null;
    $discount = $_POST['discount'] ?? 0;

    // Log parsed variables for debugging
    error_log("Parsed vars: customer_id='$customer_id', recipient='$recipient', location='$location', quantity='$quantity', price_per_unit='$price_per_unit', total_price='$total_price', payment_type='$payment_type', amount_paid_usd='$amount_paid_usd', amount_paid_iq='$amount_paid_iq', dolar_rate='$dolar_rate', remaining_amount='$remaining_amount', invoice_number='$invoice_number', order_date='$order_date', notes='$notes', formula_id='$formula_id', discount='$discount'");

    if (!$location || !$quantity || !$price_per_unit || !$total_price || !$payment_type || !$invoice_number || !$order_date || !$formula_id) {
        echo json_encode(['success' => false, 'message' => 'هەموو خانە پڕ بکە']);
        exit;
    }

    // Check for duplicate invoice_number
    $check = $pdo->prepare('SELECT COUNT(*) FROM sales WHERE invoice_number = ?');
    $check->execute([$invoice_number]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی پسوڵە پێشتر تۆمارکراوە!']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO sales (customer_id, recipient, location, quantity, price_per_unit, total_price, payment_type, amount_paid_usd, amount_paid_iq, dolar_rate, remaining_amount, invoice_number, order_date, notes, formula_id, discount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
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
        $discount
    ]);
    $sale_id = $pdo->lastInsertId();

    // Update customer debt
    if ($payment_type === 'قەرز') {
        // No need to update customer debt_usd/debt_iqd anymore
        // The remaining amount is tracked in the sales table itself
        // This is handled by the remaining_amount field in the sales table
    }

    require_once __DIR__ . '/../../includes/notify.php';
    notify('insert', 'sales', $sale_id, 'فرۆشتنێکی نوێ زیادکرا (invoice: ' . $invoice_number . ')');
    echo json_encode(['success' => true, 'message' => 'فرۆشتن بەسەرکەوتوویی زیادکرا!']);
} catch (PDOException $e) {
    error_log('PDOException: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
