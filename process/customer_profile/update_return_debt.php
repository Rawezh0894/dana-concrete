<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('update_return_debt.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for return debt update');
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('update_debt')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to update debt');
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    $customer_id = $_POST['customer_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $dolar_rate = floatval($_POST['dolar_rate'] ?? 0);
    $paid_usd = floatval($_POST['paid_usd'] ?? 0);
    $paid_iqd = floatval($_POST['paid_iqd'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $note = $_POST['note'] ?? '';

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', customer_id='$customer_id', date='$date', dolar_rate='$dolar_rate', paid_usd='$paid_usd', paid_iqd='$paid_iqd', discount='$discount', note='$note'");

    if (!$id || !$customer_id || !$date || ($paid_usd <= 0 && $paid_iqd <= 0 && $discount <= 0)) {
        error_log('Missing required fields for return debt update');
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان پڕ بکە!']);
        exit;
    }

    // Check if debt payment exists and get payment type
    $checkStmt = $pdo->prepare('SELECT id, from_opening_debt_usd, from_sales_usd, payment_type FROM customer_debt_payments WHERE id = ?');
    $checkStmt->execute([$id]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        error_log('Debt payment not found: ID=' . $id);
        echo json_encode(['success' => false, 'msg' => 'قەرز نەدۆزرایەوە!']);
        exit;
    }
    
    error_log('Found debt payment for update: ' . print_r($row, true));

    // وەرگرتنی بڕەکانی کۆن
    $old_from_opening = floatval($row['from_opening_debt_usd'] ?? 0);
    $old_from_sales = floatval($row['from_sales_usd'] ?? 0);
    $old_payment_type = $row['payment_type'];

    // بگەڕێنەوە بۆ شوێنەکانیان بەپێی جۆری پارەدان (یەکسان بە delete)
    switch ($old_payment_type) {
        case 'opening_debt_only':
            // تەنها قەرزی سەرەتایی - گەڕاندنەوەی بۆ opening_debt_usd
            if ($old_from_opening > 0) {
                $upd = $pdo->prepare("UPDATE customers SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?");
                $upd->execute([$old_from_opening, $customer_id]);
                error_log("Restored $old_from_opening USD to opening debt for customer $customer_id (update)");
            }
            break;
            
        case 'specific_sales':
            // فرۆشتنێکی دیاریکراو - گەڕاندنەوەی بۆ فرۆشتنە دیاریکراوەکان
            $stmt = $pdo->prepare("SELECT sale_id, allocated_amount FROM customer_payment_allocations WHERE debt_payment_id = ?");
            $stmt->execute([$id]);
            $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($allocations as $allocation) {
                $sale_id = $allocation['sale_id'];
                $allocated_amount = floatval($allocation['allocated_amount']);
                
                // گەڕاندنەوەی بڕەکە بۆ فرۆشتنەکە
                $upd = $pdo->prepare("UPDATE sales SET remaining_amount = remaining_amount + ? WHERE id = ? AND customer_id = ?");
                $upd->execute([$allocated_amount, $sale_id, $customer_id]);
                error_log("Restored $allocated_amount USD to sale $sale_id for customer $customer_id (update)");
            }
            break;
            
        case 'fifo':
        default:
            // FIFO - گەڕاندنەوەی بە LIFO (Last In, First Out)
            
            // یەکەم بۆ opening_debt_usd
            if ($old_from_opening > 0) {
                $upd = $pdo->prepare("UPDATE customers SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?");
                $upd->execute([$old_from_opening, $customer_id]);
                error_log("Restored $old_from_opening USD to opening debt for customer $customer_id (update)");
            }
            
            // پاشان بۆ sales بە LIFO - بەکارهێنانی allocation records
            if ($old_from_sales > 0) {
                // یەکەم هەوڵی بەکارهێنانی allocation records
                $stmt = $pdo->prepare("SELECT sale_id, allocated_amount FROM customer_payment_allocations WHERE debt_payment_id = ? ORDER BY id DESC");
                $stmt->execute([$id]);
                $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($allocations)) {
                    // بەکارهێنانی allocation records بۆ گەڕاندنەوەی تەواو
                    foreach ($allocations as $allocation) {
                        $sale_id = $allocation['sale_id'];
                        $allocated_amount = floatval($allocation['allocated_amount']);
                        
                        $upd = $pdo->prepare("UPDATE sales SET remaining_amount = remaining_amount + ? WHERE id = ? AND customer_id = ?");
                        $upd->execute([$allocated_amount, $sale_id, $customer_id]);
                        error_log("Restored $allocated_amount USD to sale $sale_id for customer $customer_id (FIFO allocation update)");
                    }
                } else {
                    // Fallback: گەڕاندنەوەی بە LIFO ئەگەر allocation records نەبوون
                    $usd_left = $old_from_sales;
                    
                    $stmt = $pdo->prepare("SELECT id, remaining_amount, total_price FROM sales WHERE customer_id = ? AND remaining_amount < total_price ORDER BY order_date DESC, id DESC");
                    $stmt->execute([$customer_id]);
                    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($sales as $sale) {
                        if ($usd_left <= 0) break;
                        $max_add = $sale['total_price'] - $sale['remaining_amount'];
                        $to_add = min($max_add, $usd_left);
                        if ($to_add > 0) {
                            $upd = $pdo->prepare("UPDATE sales SET remaining_amount = remaining_amount + ? WHERE id = ?");
                            $upd->execute([$to_add, $sale['id']]);
                            $usd_left -= $to_add;
                            error_log("Restored $to_add USD to sale {$sale['id']} for customer $customer_id (LIFO update fallback)");
                        }
                    }
                    
                    // ئەگەر هێشتا پارە ماوە، بگەڕێنەوە بۆ opening_debt_usd
                    if ($usd_left > 0) {
                        $upd = $pdo->prepare("UPDATE customers SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?");
                        $upd->execute([$usd_left, $customer_id]);
                        error_log("Restored remaining $usd_left USD to opening debt for customer $customer_id (LIFO update overflow)");
                    }
                }
            }
            break;
    }

    // سڕینەوەی payment allocations یان ئەگەر هەیە
    $delAllocations = $pdo->prepare('DELETE FROM customer_payment_allocations WHERE debt_payment_id = ?');
    $delAllocations->execute([$id]);

    // هەژمارکردنی بڕی نوێ
    $paid_iqd_usd = $dolar_rate > 0 ? $paid_iqd / ($dolar_rate / 100) : 0;
    $total_usd = $paid_usd + $paid_iqd_usd + $discount;

    // For update, we'll use FIFO by default (can be enhanced later to support different payment types in updates)
    $from_opening_debt_usd = 0;
    $from_sales_usd = 0;
    $payment_allocations = [];

    // یەکەم بۆ opening_debt_usd
    $stmt = $pdo->prepare("SELECT opening_debt_usd FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $opening_debt = floatval($stmt->fetchColumn() ?? 0);

    if ($opening_debt > 0) {
        $from_opening_debt_usd = min($opening_debt, $total_usd);
        $total_usd -= $from_opening_debt_usd;
    }

    // پاشان بۆ sales.remaining_amount بە FIFO
    if ($total_usd > 0) {
        $stmt = $pdo->prepare("SELECT id, remaining_amount FROM sales WHERE customer_id = ? AND remaining_amount > 0 ORDER BY order_date ASC, id ASC");
        $stmt->execute([$customer_id]);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sales as $sale) {
            if ($total_usd <= 0) break;
            $to_deduct = min($sale['remaining_amount'], $total_usd);
            if ($to_deduct > 0) {
                $upd = $pdo->prepare("UPDATE sales SET remaining_amount = remaining_amount - ? WHERE id = ?");
                $upd->execute([$to_deduct, $sale['id']]);
                $from_sales_usd += $to_deduct;
                $total_usd -= $to_deduct;
                $payment_allocations[] = ['sale_id' => $sale['id'], 'amount' => $to_deduct];
            }
        }
    }

    // نوێکردنەوەی قەرزەکە (بە FIFO بەکاردەهێنین بۆ نوێکردنەوە)
    $upd = $pdo->prepare('UPDATE customer_debt_payments SET date=?, dolar_rate=?, paid_usd=?, paid_iqd=?, discount=?, note=?, payment_type=?, from_opening_debt_usd=?, from_sales_usd=? WHERE id=?');
    $result = $upd->execute([$date, $dolar_rate, $paid_usd, $paid_iqd, $discount, $note, 'fifo', $from_opening_debt_usd, $from_sales_usd, $id]);

    if ($result) {
        // کەمکردنەوەی بڕەکانی نوێ
        if ($from_opening_debt_usd > 0) {
            $upd = $pdo->prepare("UPDATE customers SET opening_debt_usd = opening_debt_usd - ? WHERE id = ?");
            $upd->execute([$from_opening_debt_usd, $customer_id]);
        }

        // Create payment allocation records for the new allocations
        if (!empty($payment_allocations)) {
            $stmt = $pdo->prepare('INSERT INTO customer_payment_allocations (debt_payment_id, sale_id, allocated_amount) VALUES (?, ?, ?)');
            foreach ($payment_allocations as $allocation) {
                $stmt->execute([$id, $allocation['sale_id'], $allocation['amount']]);
            }
        }

        // Get customer information for notification
        $stmt = $pdo->prepare("SELECT name, mobile1 FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch();
        $customer_name = $customer['name'] ?? 'Unknown';
        $customer_phone = $customer['mobile1'] ?? 'هیچ ژمارەیەک نییە';

        // Get old values for notification
        $stmt = $pdo->prepare("SELECT * FROM customer_debt_payments WHERE id = ?");
        $stmt->execute([$id]);
        $old_record = $stmt->fetch();

        $old_values = [
            'customer_id' => $old_record['customer_id'],
            'date' => $old_record['date'],
            'dolar_rate' => $old_record['dolar_rate'],
            'paid_usd' => $old_record['paid_usd'],
            'paid_iqd' => $old_record['paid_iqd'],
            'discount' => $old_record['discount'],
            'note' => $old_record['note'],
            'from_opening_debt_usd' => $old_record['from_opening_debt_usd'],
            'from_sales_usd' => $old_record['from_sales_usd']
        ];

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
            'from_opening_debt_usd' => $from_opening_debt_usd,
            'from_sales_usd' => $from_sales_usd
        ];

        $additional_info = [
            'action_type' => 'customer_debt_payment_update',
            'payment_method' => $paid_usd > 0 ? 'USD' : ($paid_iqd > 0 ? 'IQD' : 'none'),
            'total_paid_usd_equivalent' => $paid_usd + ($paid_iqd / $dolar_rate),
            'debt_reduction_type' => $from_opening_debt_usd > 0 ? 'opening_debt' : 'sales_debt'
        ];

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'update',
            'customer_debt_payments',
            $id,
            "پارەدانی قەرزی کڕیار نوێکرایەوە (کڕیار: $customer_name, تەلەفۆن: $customer_phone)",
            $old_values,
            $new_values,
            $additional_info,
            getUserIP()
        );

        error_log('Return debt successfully updated: ID=' . $id . ', Customer=' . $customer_name . ' (ID: ' . $customer_id . ')');
        echo json_encode(['success' => true, 'msg' => 'قەرز بەسەرکەوتوویی نوێکرایەوە!']);
    } else {
        error_log('Failed to update return debt: ID=' . $id);
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە!']);
    }
} catch (PDOException $e) {
    error_log('PDOException in update_return_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in update_return_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
