<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('add_return_debt.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for return debt addition');
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('add_debt')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to add debt');
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $customer_id = $_POST['customer_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $dolar_rate = floatval($_POST['dolar_rate'] ?? 0);
    $paid_usd = floatval($_POST['paid_usd'] ?? 0);
    $paid_iqd = floatval($_POST['paid_iqd'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $note = $_POST['note'] ?? '';

    // Log parsed variables for debugging
    error_log("Parsed vars: customer_id='$customer_id', date='$date', dolar_rate='$dolar_rate', paid_usd='$paid_usd', paid_iqd='$paid_iqd', discount='$discount', note='$note'");

    if (!$customer_id || !$date || ($paid_usd <= 0 && $paid_iqd <= 0 && $discount <= 0)) {
        error_log('Missing required fields for return debt addition');
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان پڕ بکە!']);
        exit;
    }

    // 1. هەژمارکردنی بڕی پارەی داوە بە دۆلار
    $paid_iqd_usd = $dolar_rate > 0 ? $paid_iqd / ($dolar_rate / 100) : 0;
    $total_paid_usd = $paid_usd + $paid_iqd_usd + $discount;

    // 2. چێککردنی قەرز
    $stmt = $pdo->prepare('SELECT opening_debt_usd FROM customers WHERE id = ?');
    $stmt->execute([$customer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        error_log('Customer not found: ID=' . $customer_id);
        echo json_encode(['success' => false, 'msg' => 'کڕیار نەدۆزرایەوە!']);
        exit;
    }
    
    $opening_debt_usd = floatval($row['opening_debt_usd'] ?? 0);
    
    // Get total remaining amount from sales
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(remaining_amount), 0) as total_remaining FROM sales WHERE customer_id = ? AND payment_type = 'قەرز'");
    $stmt->execute([$customer_id]);
    $sales_remaining = floatval($stmt->fetchColumn() ?? 0);
    
    $total_debt_usd = $opening_debt_usd + $sales_remaining;

    // Allow small tolerance for floating-point precision (1 cent)
    $tolerance = 0.01;
    if ($total_paid_usd > ($total_debt_usd + $tolerance)) {
        error_log('Payment amount exceeds total debt: payment=' . $total_paid_usd . ', debt=' . $total_debt_usd);
        echo json_encode(['success' => false, 'msg' => 'بڕی پارەی داوە نابێت زیاتر بێت لە قەرز!']);
        exit;
    }

    // 3. زیادکردنی قەرزە گەڕاوەکە
    // بۆیە پێویستە بزانین چەند لە opening_debt_usd و چەند لە sales کەم دەکەین
    $usd_left = $total_paid_usd;
    $from_sales_usd = 0;
    $from_opening_debt_usd = 0;

    // پێچەوانە: سەرەتا opening_debt_usd کەم بکە
    $paid_from_opening = 0;
    $paid_from_sales = 0;
    if ($opening_debt_usd > 0) {
        $to_deduct = min($opening_debt_usd, $usd_left);
        $upd = $pdo->prepare("UPDATE customers SET opening_debt_usd = GREATEST(opening_debt_usd - ?, 0) WHERE id = ?");
        $upd->execute([$to_deduct, $customer_id]);
        $paid_from_opening = $to_deduct;
        $usd_left -= $to_deduct;
    }
    // پاشان لە sales کەم بکە بە FIFO
    if ($usd_left > 0) {
        $stmt = $pdo->prepare("SELECT id, remaining_amount FROM sales WHERE customer_id = ? AND remaining_amount > 0 ORDER BY order_date ASC, id ASC");
        $stmt->execute([$customer_id]);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sales as $sale) {
            if ($usd_left <= 0) break;
            $to_deduct = min($sale['remaining_amount'], $usd_left);
            $upd = $pdo->prepare("UPDATE sales SET remaining_amount = remaining_amount - ? WHERE id = ?");
            $upd->execute([$to_deduct, $sale['id']]);
            $usd_left -= $to_deduct;
            $paid_from_sales += $to_deduct;
        }
    }

    // Get customer information for notification
    $stmt = $pdo->prepare("SELECT name, mobile1 FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
    $customer_name = $customer['name'] ?? 'Unknown';
    $customer_phone = $customer['mobile1'] ?? 'هیچ ژمارەیەک نییە';

    // زیادکردنی قەرزە گەڕاوەکە لە customer_debt_payments
    $stmt = $pdo->prepare('INSERT INTO customer_debt_payments (customer_id, date, dolar_rate, paid_usd, paid_iqd, discount, note, from_opening_debt_usd, from_sales_usd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $ok = $stmt->execute([$customer_id, $date, $dolar_rate, $paid_usd, $paid_iqd, $discount, $note, $paid_from_opening, $paid_from_sales]);
    
    if (!$ok) {
        error_log('Failed to insert debt payment record');
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە تۆمارکردن']);
        exit;
    }
    
    $debt_payment_id = $pdo->lastInsertId();

    // Create detailed notification with customer information
    $new_values = [
        'customer_id' => $customer_id,
        'customer_name' => $customer_name,
        'customer_phone' => $customer_phone,
        'date' => $date,
        'dolar_rate' => $dolar_rate,
        'paid_usd' => $paid_usd,
        'paid_iqd' => $paid_iqd,
        'discount' => $discount,
        'note' => $note,
        'from_opening_debt_usd' => $paid_from_opening,
        'from_sales_usd' => $paid_from_sales
    ];

    $additional_info = [
        'action_type' => 'customer_debt_payment',
        'payment_method' => $paid_usd > 0 ? 'USD' : ($paid_iqd > 0 ? 'IQD' : 'none'),
        'total_paid_usd_equivalent' => $total_paid_usd,
        'debt_reduction_type' => $paid_from_opening > 0 ? 'opening_debt' : 'sales_debt'
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'insert',
        'customer_debt_payments',
        $debt_payment_id,
        "پارەدان بۆ قەرزی کڕیار زیادکرا (کڕیار: $customer_name, تەلەفۆن: $customer_phone)",
        null, // No old values for insert
        $new_values,
        $additional_info,
        getUserIP()
    );
    
    error_log('Return debt successfully added: Customer=' . $customer_name . ' (ID: ' . $customer_id . '), Amount=' . $total_paid_usd);
    echo json_encode(['success' => true, 'msg' => 'دانەوەی قەرز بەسەرکەوتوویی تۆمارکرا!']);
    
} catch (PDOException $e) {
    error_log('PDOException in add_return_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in add_return_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
