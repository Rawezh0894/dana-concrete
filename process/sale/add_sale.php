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

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasPermission('add_sale')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

header('Content-Type: application/json');

try {
    // Get form data
    $customer_id = $_POST['customer_id'] ?? null;
    $formula_id = $_POST['formula_id'] ?? null;
    $recipient = $_POST['recipient'] ?? '';
    $location = $_POST['location'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $price_per_unit = $_POST['price_per_unit'] ?? 0;
    $total_price = $_POST['total_price'] ?? 0;
    $payment_type = $_POST['payment_type'] ?? 'قەرز';
    $amount_paid_usd = $_POST['amount_paid_usd'] ?? 0;
    $amount_paid_iq = $_POST['amount_paid_iq'] ?? 0;
    $remaining_amount = $_POST['remaining_amount'] ?? 0;
    $dolar_rate = $_POST['dolar_rate'] ?? 150000;
    $discount = $_POST['discount'] ?? 0;
    $order_date = $_POST['order_date'] ?? date('Y-m-d');
    $invoice_number = $_POST['invoice_number'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Validate required fields
    if (!$customer_id || !$formula_id || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'هەموو خانە پێویستەکان پڕبکەرەوە']);
        exit;
    }

    // Check for duplicate invoice number
    if (!empty($invoice_number)) {
        $dup_stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE invoice_number = ?");
        $dup_stmt->execute([$invoice_number]);
        if ($dup_stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی پسوڵە پێشتر تۆمارکراوە!']);
            exit;
        }
    }

    // Get customer name for notification
    $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();

    // Get formula name for notification
    $stmt = $pdo->prepare("SELECT name FROM concrete_formulas WHERE id = ?");
    $stmt->execute([$formula_id]);
    $formula = $stmt->fetch();

    // Insert sale with all fields
    $sql = "INSERT INTO sales (customer_id, formula_id, recipient, location, quantity, price_per_unit, total_price, payment_type, amount_paid_usd, amount_paid_iq, remaining_amount, dolar_rate, discount, order_date, invoice_number, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $customer_id, $formula_id, $recipient, $location, $quantity, $price_per_unit, $total_price,
        $payment_type, $amount_paid_usd, $amount_paid_iq, $remaining_amount, $dolar_rate, $discount,
        $order_date, $invoice_number, $notes
    ]);

    $sale_id = $pdo->lastInsertId();

    // Create detailed notification with old and new values
    $new_values = [
        'customer_id' => $customer_id,
        'customer_name' => $customer['name'] ?? 'Unknown',
        'formula_id' => $formula_id,
        'formula_name' => $formula['name'] ?? 'Unknown',
        'recipient' => $recipient,
        'location' => $location,
        'quantity' => $quantity,
        'price_per_unit' => $price_per_unit,
        'total_price' => $total_price,
        'payment_type' => $payment_type,
        'amount_paid_usd' => $amount_paid_usd,
        'amount_paid_iq' => $amount_paid_iq,
        'remaining_amount' => $remaining_amount,
        'dolar_rate' => $dolar_rate,
        'discount' => $discount,
        'order_date' => $order_date,
        'invoice_number' => $invoice_number,
        'notes' => $notes
    ];

    $additional_info = [
        'action_type' => 'sale_creation',
        'payment_status' => $payment_type === 'نەقد' ? 'paid' : 'credit',
        'currency_used' => $amount_paid_usd > 0 ? 'USD' : ($amount_paid_iq > 0 ? 'IQD' : 'none'),
        'total_paid' => $amount_paid_usd + $amount_paid_iq
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'insert',
        'sales',
        $sale_id,
        "فرۆشتنێکی نوێ زیادکرا (invoice: $invoice_number, کڕیار: {$customer['name']}, فۆرمۆلا: {$formula['name']}, بڕ: $quantity م³)",
        null, // No old values for insert
        $new_values,
        $additional_info,
        getUserIP()
    );

    echo json_encode([
        'success' => true,
        'message' => 'فرۆشتنەکە بە سەرکەوتوویی زیادکرا',
        'sale_id' => $sale_id
    ]);

} catch (Exception $e) {
    error_log("Error in add_sale.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()
    ]);
}
?>
