<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');
error_log('SESSION: ' . print_r($_SESSION, true));

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('add_sale')) {
    error_log('Permission denied for user: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown') . ' role: ' . (isset($_SESSION['role']) ? $_SESSION['role'] : 'unknown'));
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

    // Log all input variables for debugging
    error_log('add_sale.php POST: ' . print_r($_POST, true));
    error_log('Parsed vars: customer_id=' . var_export($customer_id, true) . ', recipient=' . var_export($recipient, true) . ', location=' . var_export($location, true) . ', quantity=' . var_export($quantity, true) . ', price_per_unit=' . var_export($price_per_unit, true) . ', total_price=' . var_export($total_price, true) . ', payment_type=' . var_export($payment_type, true) . ', amount_paid_usd=' . var_export($amount_paid_usd, true) . ', amount_paid_iq=' . var_export($amount_paid_iq, true) . ', dolar_rate=' . var_export($dolar_rate, true) . ', remaining_amount=' . var_export($remaining_amount, true) . ', invoice_number=' . var_export($invoice_number, true) . ', order_date=' . var_export($order_date, true) . ', notes=' . var_export($notes, true) . ', formula_id=' . var_export($formula_id, true) . ', discount=' . var_export($discount, true));

    if (!$location || !$quantity || !$price_per_unit || !$total_price || !$payment_type || !$invoice_number || !$order_date || !$formula_id) {
        echo json_encode(['success' => false, 'message' => 'هەموو خانە پڕ بکە']);
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

    // Update customer debt if needed
    if ($customer_id !== null && $payment_type === 'قەرز') {
        $stmt2 = $pdo->prepare("UPDATE customers SET debt_usd = IFNULL(debt_usd,0) + ? WHERE id = ?");
        $stmt2->execute([$remaining_amount, $customer_id]);
    }

    echo json_encode(['success' => true, 'message' => 'فرۆشتن بەسەرکەوتوویی زیادکرا!']);
} catch (PDOException $e) {
    error_log('PDOException: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
