<?php
/**
 * Raw Material Sales - Add New Sale
 * Following ERP standards (Odoo, SAP, Oracle)
 * 
 * Features:
 * - Multi-party support (customer, company, external)
 * - Multi-currency support (IQD, USD)
 * - Automatic inventory deduction from bins_silos
 * - Inventory movement logging
 * - Average cost price from purchases
 * - Profit calculation
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

if (!hasPermission('add_raw_material_sales')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە بۆ زیادکردن!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get and validate input data
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $sale_date = $_POST['sale_date'] ?? date('Y-m-d');
    $buyer_type = $_POST['buyer_type'] ?? 'دەرەوە';
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
    $company_id = !empty($_POST['company_id']) ? intval($_POST['company_id']) : null;
    $external_buyer_name = trim($_POST['external_buyer_name'] ?? '');
    $external_buyer_phone = trim($_POST['external_buyer_phone'] ?? '');
    $bin_id = intval($_POST['bin_id'] ?? 0);
    $quantity_kg = floatval($_POST['quantity_kg'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $payment_type = $_POST['payment_type'] ?? 'نەقد';
    $paid_amount = floatval($_POST['paid_amount'] ?? 0);
    $exchange_rate = floatval($_POST['exchange_rate'] ?? 150000);
    $notes = trim($_POST['notes'] ?? '');

    // Validate required fields
    if (empty($invoice_number)) {
        echo json_encode(['success' => false, 'message' => 'ژمارەی پسووڵە پێویستە']);
        exit;
    }
    
    if ($bin_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'تکایە بین/سایلۆ هەڵبژێرە']);
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

    // Validate buyer based on type
    if ($buyer_type === 'کڕیار' && empty($customer_id)) {
        echo json_encode(['success' => false, 'message' => 'تکایە کڕیار هەڵبژێرە']);
        exit;
    }
    if ($buyer_type === 'کۆمپانیا' && empty($company_id)) {
        echo json_encode(['success' => false, 'message' => 'تکایە کۆمپانیا هەڵبژێرە']);
        exit;
    }
    if ($buyer_type === 'دەرەوە' && empty($external_buyer_name)) {
        echo json_encode(['success' => false, 'message' => 'ناوی کڕیاری دەرەوە پێویستە']);
        exit;
    }

    // Check for duplicate invoice number
    $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM raw_material_sales WHERE invoice_number = ?");
    $dupStmt->execute([$invoice_number]);
    if ($dupStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی پسووڵە پێشتر تۆمارکراوە']);
        exit;
    }

    // Get bin information and validate available quantity
    $binStmt = $pdo->prepare("SELECT * FROM bins_silos WHERE id = ?");
    $binStmt->execute([$bin_id]);
    $bin = $binStmt->fetch(PDO::FETCH_ASSOC);

    if (!$bin) {
        echo json_encode(['success' => false, 'message' => 'بین/سایلۆ نەدۆزرایەوە']);
        exit;
    }

    if (floatval($bin['amount']) < $quantity_kg) {
        echo json_encode([
            'success' => false, 
            'message' => 'بڕی داواکراو لە بڕی بەردەست زیاترە. بڕی بەردەست: ' . number_format($bin['amount'], 2) . ' کگم'
        ]);
        exit;
    }

    // Determine currency type based on material
    // چەو، لم، گاز = IQD | چیمەنتۆ، دەرمان = USD
    $material_type = $bin['material_type'];
    $usd_materials = ['چیمەنتۆ', 'دەرمان'];
    $currency_type = in_array($material_type, $usd_materials) ? 'دۆلار' : 'دینار';

    // Calculate total price
    $total_price = $quantity_kg * $unit_price;

    // Get average cost price from PURCHASES table (not bins_silos)
    // This follows the same logic as the reports page
    $avgPriceData = getAveragePriceFromPurchases($pdo, $material_type);
    $cost_price = floatval($avgPriceData['price_per_kg'] ?? 0);
    
    // Calculate profit - ensure same currency for comparison
    // If material is USD type, convert cost to same basis
    $total_cost = $quantity_kg * $cost_price;
    $profit_amount = $total_price - $total_cost;

    // Calculate remaining amount based on payment type
    $remaining_amount = 0;
    if ($payment_type === 'قەرز') {
        $remaining_amount = $total_price - $paid_amount;
        if ($remaining_amount < 0) $remaining_amount = 0;
    } else {
        // Cash payment - paid amount should equal total
        $paid_amount = $total_price;
    }

    // Start transaction
    $pdo->beginTransaction();

    // Insert the sale
    $insertSql = "
        INSERT INTO raw_material_sales (
            invoice_number, sale_date, buyer_type, customer_id, company_id,
            external_buyer_name, external_buyer_phone, bin_id, material_type,
            quantity_kg, currency_type, unit_price, total_price, cost_price,
            profit_amount, payment_type, paid_amount, remaining_amount,
            exchange_rate, notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
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
        $_SESSION['user_id']
    ]);

    $sale_id = $pdo->lastInsertId();

    // Update bin quantity (decrease)
    $new_bin_amount = floatval($bin['amount']) - $quantity_kg;
    
    // Update total_value proportionally
    $old_total_value = floatval($bin['total_value']);
    $value_deducted = ($bin['amount'] > 0) ? ($old_total_value / $bin['amount']) * $quantity_kg : 0;
    $new_total_value = $old_total_value - $value_deducted;
    
    $updateBinSql = "UPDATE bins_silos SET amount = ?, total_value = ? WHERE id = ?";
    $updateBinStmt = $pdo->prepare($updateBinSql);
    $updateBinStmt->execute([$new_bin_amount, $new_total_value, $bin_id]);

    // Log inventory movement
    $logSql = "
        INSERT INTO raw_material_inventory_log (
            bin_id, sale_id, movement_type, quantity_change,
            quantity_before, quantity_after, reference_doc, notes, created_by
        ) VALUES (?, ?, 'sale', ?, ?, ?, ?, ?, ?)
    ";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        $bin_id,
        $sale_id,
        -$quantity_kg, // Negative for sale
        $bin['amount'],
        $new_bin_amount,
        $invoice_number,
        'فرۆشتنی مەوادی خام',
        $_SESSION['user_id']
    ]);

    // Update customer/company debt if credit sale
    if ($payment_type === 'قەرز' && $remaining_amount > 0) {
        if ($buyer_type === 'کڕیار' && $customer_id) {
            // Customers don't have direct debt column for raw materials
            // Debt is tracked in raw_material_sales.remaining_amount
        } elseif ($buyer_type === 'کۆمپانیا' && $company_id) {
            // Companies don't have direct debt column for raw materials
            // Debt is tracked in raw_material_sales.remaining_amount
        }
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
    $new_values = [
        'invoice_number' => $invoice_number,
        'sale_date' => $sale_date,
        'buyer_type' => $buyer_type,
        'buyer_name' => $buyer_name,
        'material_type' => $material_type,
        'quantity_kg' => $quantity_kg,
        'unit_price' => $unit_price,
        'total_price' => $total_price,
        'currency_type' => $currency_type,
        'payment_type' => $payment_type,
        'paid_amount' => $paid_amount,
        'remaining_amount' => $remaining_amount,
        'profit_amount' => $profit_amount
    ];

    $additional_info = [
        'action_type' => 'raw_material_sale_creation',
        'bin_name' => $bin['name'],
        'old_bin_quantity' => $bin['amount'],
        'new_bin_quantity' => $new_bin_amount
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'insert',
        'raw_material_sales',
        $sale_id,
        "فرۆشتنی مەوادی خام: {$quantity_kg} کگم {$material_type} بۆ {$buyer_name} (پسووڵە: {$invoice_number})",
        null,
        $new_values,
        $additional_info,
        getUserIP()
    );

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'فرۆشتنەکە بە سەرکەوتووی تۆمارکرا',
        'data' => [
            'sale_id' => $sale_id,
            'invoice_number' => $invoice_number,
            'total_price' => $total_price,
            'profit_amount' => $profit_amount,
            'remaining_amount' => $remaining_amount,
            'new_bin_quantity' => $new_bin_amount
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Raw Material Sales Add Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Raw Material Sales Add Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
}
