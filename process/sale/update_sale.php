<?php
header('Content-Type: application/json');
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!hasPermission('update_sale')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $customer_id = $_POST['customer_id'] ?? null;
    if ($customer_id === '' || $customer_id === null) {
        $customer_id = null;
    }
    $id = $_POST['edit_sale_id'] ?? null;
    $customer_id = $_POST['edit_customer_id'] ?? null;
    if ($customer_id === '' || $customer_id === '0') {
        $customer_id = null;
    }
    $recipient = $_POST['edit_recipient'] ?? null;
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

    if (!$id || !$location || !$quantity || !$price_per_unit || !$total_price || !$payment_type || !$invoice_number || !$order_date || !$formula_id) {
        echo json_encode(['success' => false, 'message' => 'هەموو خانە پڕ بکە']);
        exit;
    }

    // Get old sale info before update
    $stmt = $pdo->prepare('SELECT customer_id, payment_type, remaining_amount FROM sales WHERE id = ?');
    $stmt->execute([$id]);
    $oldSale = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($oldSale && $oldSale['customer_id'] !== null && $oldSale['payment_type'] === 'قەرز') {
        $stmt2 = $pdo->prepare("UPDATE customers SET debt_usd = IFNULL(debt_usd,0) - ? WHERE id = ?");
        $stmt2->execute([$oldSale['remaining_amount'], $oldSale['customer_id']]);
    }

    $stmt = $pdo->prepare("UPDATE sales SET customer_id=?, recipient=?, location=?, quantity=?, price_per_unit=?, total_price=?, payment_type=?, amount_paid_usd=?, amount_paid_iq=?, dolar_rate=?, remaining_amount=?, invoice_number=?, order_date=?, notes=?, formula_id=?, discount=? WHERE id=?");
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
        $discount,
        $id
    ]);

    // Update new customer debt if needed
    if ($customer_id !== null && $payment_type === 'قەرز') {
        $stmt3 = $pdo->prepare("UPDATE customers SET debt_usd = IFNULL(debt_usd,0) + ? WHERE id = ?");
        $stmt3->execute([$remaining_amount, $customer_id]);
    }

    echo json_encode(['success' => true, 'message' => 'فرۆشتن نوێکرایەوە!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
