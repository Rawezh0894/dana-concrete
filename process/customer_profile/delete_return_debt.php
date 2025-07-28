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

    // وەرگرتنی زانیاری قەرزەکە
    $stmt = $pdo->prepare('SELECT customer_id, paid_usd, paid_iqd, discount, dolar_rate, from_opening_debt_usd, from_sales_usd FROM customer_debt_payments WHERE id = ?');
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

    // هەژمارکردنی بڕی IQD بۆ USD
    $paid_iqd_usd = $dolar_rate > 0 ? $paid_iqd / ($dolar_rate / 100) : 0;
    $total_usd = $paid_usd + $paid_iqd_usd + $discount;

    // زیادکردنی بۆ opening_debt_usd تەنها بۆ بڕی from_opening_debt_usd
    if ($from_opening_debt_usd > 0) {
        $upd = $pdo->prepare("UPDATE customers SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?");
        $upd->execute([$from_opening_debt_usd, $customer_id]);
    }
    
    // زیادکردنی بۆ sales.remaining_amount تەنها بۆ بڕی from_sales_usd
    if ($from_sales_usd > 0) {
        // زیادکردنی بۆ sales.remaining_amount بە FIFO
        $usd_left = $from_sales_usd;
        $stmt = $pdo->prepare("SELECT id, remaining_amount, total_price FROM sales WHERE customer_id = ? ORDER BY order_date ASC, id ASC");
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
            }
        }
    }

    // سڕینەوەی قەرزەکە
    $del = $pdo->prepare('DELETE FROM customer_debt_payments WHERE id = ?');
    $ok = $del->execute([$id]);

    if ($ok) {
        require_once __DIR__ . '/../../includes/notify.php';
        notify('delete', 'customer_debt_payments', $id, 'پارەدانی قەرزی کڕیار سڕایەوە (کڕیار: ' . $customer_id . ')');
        error_log('Return debt successfully deleted: ID=' . $id . ', Customer=' . $customer_id);
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
