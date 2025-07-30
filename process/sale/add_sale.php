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
    $quantity = $_POST['quantity'] ?? 0;
    $price_per_cubic = $_POST['price_per_cubic'] ?? 0;
    $total_amount = $_POST['total_amount'] ?? 0;
    $payment_type = $_POST['payment_type'] ?? 'قەرز';
    $amount_paid_usd = $_POST['amount_paid_usd'] ?? 0;
    $amount_paid_iq = $_POST['amount_paid_iq'] ?? 0;
    $order_date = $_POST['order_date'] ?? date('Y-m-d');
    $invoice_number = $_POST['invoice_number'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Validate required fields
    if (!$customer_id || !$formula_id || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'هەموو خانە پێویستەکان پڕبکەرەوە']);
        exit;
    }

    // Get customer name for notification
    $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();

    // Get formula name for notification
    $stmt = $pdo->prepare("SELECT name FROM concrete_formulas WHERE id = ?");
    $stmt->execute([$formula_id]);
    $formula = $stmt->fetch();

    // Insert sale
    $sql = "INSERT INTO sales (customer_id, formula_id, quantity, price_per_cubic, total_amount, payment_type, amount_paid_usd, amount_paid_iq, order_date, invoice_number, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $customer_id, $formula_id, $quantity, $price_per_cubic, $total_amount,
        $payment_type, $amount_paid_usd, $amount_paid_iq, $order_date, $invoice_number, $notes
    ]);

    $sale_id = $pdo->lastInsertId();

    // Create detailed notification with old and new values
    $new_values = [
        'customer_id' => $customer_id,
        'customer_name' => $customer['name'] ?? 'Unknown',
        'formula_id' => $formula_id,
        'formula_name' => $formula['name'] ?? 'Unknown',
        'quantity' => $quantity,
        'price_per_cubic' => $price_per_cubic,
        'total_amount' => $total_amount,
        'payment_type' => $payment_type,
        'amount_paid_usd' => $amount_paid_usd,
        'amount_paid_iq' => $amount_paid_iq,
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
