<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('delete_return_debt.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for return debt deletion');
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە!']);
    exit;
}

if (!hasPermission('delete_debt')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to delete debt');
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        error_log('No debt ID provided for deletion');
        echo json_encode(['success' => false, 'msg' => 'ناسنامەی قەرز پێویستە!']);
        exit;
    }

    // وەرگرتنی زانیاری قەرزەکە لەگەڵ payment_type
    $stmt = $pdo->prepare('SELECT customer_id, paid_usd, paid_iqd, discount, dolar_rate, from_opening_debt_usd, from_sales_usd, date, note, payment_type FROM customer_debt_payments WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        error_log('Debt payment not found: ID=' . $id);
        echo json_encode(['success' => false, 'msg' => 'قەرز نەدۆزرایەوە!']);
        exit;
    }
    
    error_log('Found debt payment for deletion: ' . print_r($row, true));
    
    $customer_id = $row['customer_id'];
    $paid_usd = floatval($row['paid_usd']);
    $paid_iqd = floatval($row['paid_iqd']);
    $discount = floatval($row['discount']);
    $dolar_rate = floatval($row['dolar_rate']);
    $from_opening_debt_usd = floatval($row['from_opening_debt_usd']);
    $from_sales_usd = floatval($row['from_sales_usd']);
    $payment_type = $row['payment_type'];

    // هەژمارکردنی بڕی IQD بۆ USD
    $paid_iqd_usd = $dolar_rate > 0 ? $paid_iqd / ($dolar_rate / 100) : 0;
    $total_usd = $paid_usd + $paid_iqd_usd + $discount;

    // Handle deletion based on payment type
    switch ($payment_type) {
        case 'opening_debt_only':
            // تەنها قەرزی سەرەتایی - گەڕاندنەوەی بۆ opening_debt_usd
            if ($from_opening_debt_usd > 0) {
                $upd = $pdo->prepare("UPDATE customers SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?");
                $upd->execute([$from_opening_debt_usd, $customer_id]);
                error_log("Restored $from_opening_debt_usd USD to opening debt for customer $customer_id");
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
                error_log("Restored $allocated_amount USD to sale $sale_id for customer $customer_id");
            }
            break;
            
        case 'fifo':
        default:
            // FIFO - گەڕاندنەوەی بە LIFO (Last In, First Out)
            
            // یەکەم بۆ opening_debt_usd
            if ($from_opening_debt_usd > 0) {
                $upd = $pdo->prepare("UPDATE customers SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?");
                $upd->execute([$from_opening_debt_usd, $customer_id]);
                error_log("Restored $from_opening_debt_usd USD to opening debt for customer $customer_id");
            }
            
            // پاشان بۆ sales بە LIFO - بەکارهێنانی allocation records
            if ($from_sales_usd > 0) {
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
                        error_log("Restored $allocated_amount USD to sale $sale_id for customer $customer_id (FIFO allocation)");
                    }
                } else {
                    // Fallback: گەڕاندنەوەی بە LIFO ئەگەر allocation records نەبوون
                    $usd_left = $from_sales_usd;
                    
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
                            error_log("Restored $to_add USD to sale {$sale['id']} for customer $customer_id (LIFO fallback)");
                        }
                    }
                    
                    // ئەگەر هێشتا پارە ماوە، بگەڕێنەوە بۆ opening_debt_usd
                    if ($usd_left > 0) {
                        $upd = $pdo->prepare("UPDATE customers SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?");
                        $upd->execute([$usd_left, $customer_id]);
                        error_log("Restored remaining $usd_left USD to opening debt for customer $customer_id (LIFO overflow)");
                    }
                }
            }
            break;
    }

    // سڕینەوەی payment allocations یان ئەگەر هەیە
    $delAllocations = $pdo->prepare('DELETE FROM customer_payment_allocations WHERE debt_payment_id = ?');
    $delAllocations->execute([$id]);

    // سڕینەوەی قەرزەکە
    $del = $pdo->prepare('DELETE FROM customer_debt_payments WHERE id = ?');
    $ok = $del->execute([$id]);

    if ($ok) {
        // Get customer information for notification
        $stmt = $pdo->prepare("SELECT name, mobile1 FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch();
        $customer_name = $customer['name'] ?? 'Unknown';
        $customer_phone = $customer['mobile1'] ?? 'هیچ ژمارەیەک نییە';

        // Create old values for notification
        $old_values = [
            'customer_id' => $customer_id,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'date' => $row['date'],
            'dolar_rate' => $dolar_rate,
            'paid_usd' => $paid_usd,
            'paid_iqd' => $paid_iqd,
            'discount' => $discount,
            'note' => $row['note'],
            'from_opening_debt_usd' => $from_opening_debt_usd,
            'from_sales_usd' => $from_sales_usd
        ];

        $additional_info = [
            'action_type' => 'customer_debt_payment_deletion',
            'payment_method' => $paid_usd > 0 ? 'USD' : ($paid_iqd > 0 ? 'IQD' : 'none'),
            'total_paid_usd_equivalent' => $total_usd,
            'debt_reduction_type' => $from_opening_debt_usd > 0 ? 'opening_debt' : 'sales_debt'
        ];

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'delete',
            'customer_debt_payments',
            $id,
            "پارەدانی قەرزی کڕیار سڕایەوە (کڕیار: $customer_name, تەلەفۆن: $customer_phone)",
            $old_values,
            null, // No new values for delete
            $additional_info,
            getUserIP()
        );

        error_log('Return debt successfully deleted: ID=' . $id . ', Customer=' . $customer_name . ' (ID: ' . $customer_id . ')');
        echo json_encode(['success' => true, 'msg' => 'قەرز بەسەرکەوتوویی سڕایەوە!']);
    } else {
        error_log('Failed to delete debt payment: ID=' . $id);
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە!']);
    }
    
} catch (PDOException $e) {
    error_log('PDOException in delete_return_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in delete_return_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
