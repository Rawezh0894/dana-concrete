<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

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
    $material_type = $_POST['material_type'] ?? null;
    $recipient_id = isset($_POST['recipient_id']) && $_POST['recipient_id'] !== '' ? intval($_POST['recipient_id']) : null;
    $recipient = '';
    $location = $_POST['location'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $unit = $_POST['unit'] ?? 'کیلۆگرام';
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
    if (!$customer_id || !$material_type || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'هەموو خانە پێویستەکان پڕبکەرەوە']);
        exit;
    }

    // Check for duplicate invoice number
    if (!empty($invoice_number)) {
        $dup_stmt = $pdo->prepare("SELECT COUNT(*) FROM raw_material_sales WHERE invoice_number = ?");
        $dup_stmt->execute([$invoice_number]);
        if ($dup_stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی پسوڵە پێشتر تۆمارکراوە!']);
            exit;
        }
    }

    if ($recipient_id) {
        // First try to get from recipients table
        $recipientStmt = $pdo->prepare("SELECT name FROM recipients WHERE id = ?");
        $recipientStmt->execute([$recipient_id]);
        $recipientRow = $recipientStmt->fetch(PDO::FETCH_ASSOC);
        
        // If not found in recipients table, try customers table (is_recipient = 1)
        if (!$recipientRow) {
            $recipientStmt = $pdo->prepare("SELECT name FROM customers WHERE id = ? AND is_recipient = 1");
            $recipientStmt->execute([$recipient_id]);
            $recipientRow = $recipientStmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($recipientRow) {
            $recipient = $recipientRow['name'];
        }
    } else {
        $recipient = trim($_POST['recipient'] ?? '');
    }

    // Get customer name for notification
    $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();

    // Material type names
    $material_type_names = [
        'black_sand' => 'لمی ڕەش',
        'brown_sand' => 'لمی کەسارە',
        'gravel' => 'چەو',
        'cement' => 'چیمەنتۆ',
        'medicine' => 'دەرمان',
        'gas' => 'گاز'
    ];
    $material_name = $material_type_names[$material_type] ?? $material_type;

    // Insert raw material sale
    $sql = "INSERT INTO raw_material_sales (customer_id, recipient, location, material_type, quantity, unit, price_per_unit, total_price, payment_type, amount_paid_usd, amount_paid_iq, remaining_amount, dolar_rate, discount, order_date, invoice_number, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $customer_id, $recipient, $location, $material_type, $quantity, $unit, $price_per_unit, $total_price,
        $payment_type, $amount_paid_usd, $amount_paid_iq, $remaining_amount, $dolar_rate, $discount,
        $order_date, $invoice_number, $notes
    ]);

    $sale_id = $pdo->lastInsertId();

    // Create detailed notification
    $new_values = [
        'customer_id' => $customer_id,
        'customer_name' => $customer['name'] ?? 'Unknown',
        'material_type' => $material_type,
        'material_name' => $material_name,
        'recipient' => $recipient,
        'location' => $location,
        'quantity' => $quantity,
        'unit' => $unit,
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
        'action_type' => 'raw_material_sale_creation',
        'payment_status' => $payment_type === 'نەقد' ? 'paid' : 'credit',
        'currency_used' => $amount_paid_usd > 0 ? 'USD' : ($amount_paid_iq > 0 ? 'IQD' : 'none'),
        'total_paid' => $amount_paid_usd + $amount_paid_iq
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'insert',
        'raw_material_sales',
        $sale_id,
        "فرۆشتی ماددە خامەکان زیادکرا (invoice: $invoice_number, کڕیار: {$customer['name']}, ماددە: $material_name, بڕ: $quantity $unit)",
        null,
        $new_values,
        $additional_info,
        getUserIP()
    );

    echo json_encode([
        'success' => true,
        'message' => 'فرۆشتی ماددە خامەکان بە سەرکەوتوویی زیادکرا',
        'sale_id' => $sale_id
    ]);

} catch (Exception $e) {
    error_log("Error in add_raw_material_sale.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()
    ]);
}
?>
