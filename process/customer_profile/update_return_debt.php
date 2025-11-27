<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('update_debt')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $date = $_POST['date'] ?? null;
    $dolar_rate = floatval($_POST['dolar_rate'] ?? 0);
    $paid_usd = floatval($_POST['paid_usd'] ?? 0);
    $paid_iqd = floatval($_POST['paid_iqd'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $note = $_POST['note'] ?? '';
    $payment_type = $_POST['payment_type'] ?? 'fifo';
    $specific_sales_raw = $_POST['specific_sales'] ?? '{}';
    $specific_sales = json_decode($specific_sales_raw, true);

    if (!is_array($specific_sales)) {
        $specific_sales = [];
    }

    $allowed_payment_types = ['fifo', 'opening_debt_only', 'specific_sales'];

    if (
        !$id ||
        !$customer_id ||
        !$date ||
        ($paid_usd <= 0 && $paid_iqd <= 0 && $discount <= 0) ||
        !in_array($payment_type, $allowed_payment_types, true)
    ) {
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان بە دروستی پڕبکە!']);
        exit;
    }

    if ($payment_type === 'specific_sales' && empty($specific_sales)) {
        echo json_encode(['success' => false, 'msg' => 'تکایە لانیکەم یەک فرۆشتن هەڵبژێرە!']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM customer_debt_payments WHERE id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $payment_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment_record) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'قەرز نەدۆزرایەوە!']);
        exit;
    }

    $db_customer_id = intval($payment_record['customer_id']);
    if ($db_customer_id !== $customer_id) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'زانیاری کڕیار نادروستە!']);
        exit;
    }

    $old_from_opening = floatval($payment_record['from_opening_debt_usd'] ?? 0);
    $old_from_sales = floatval($payment_record['from_sales_usd'] ?? 0);
    $old_payment_type = $payment_record['payment_type'];

    $old_values = [
        'customer_id' => $payment_record['customer_id'],
        'date' => $payment_record['date'],
        'dolar_rate' => $payment_record['dolar_rate'],
        'paid_usd' => $payment_record['paid_usd'],
        'paid_iqd' => $payment_record['paid_iqd'],
        'discount' => $payment_record['discount'],
        'note' => $payment_record['note'],
        'payment_type' => $payment_record['payment_type'],
        'from_opening_debt_usd' => $payment_record['from_opening_debt_usd'],
        'from_sales_usd' => $payment_record['from_sales_usd']
    ];

    switch ($old_payment_type) {
        case 'opening_debt_only':
            if ($old_from_opening > 0) {
                $pdo->prepare('UPDATE customers SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')
                    ->execute([$old_from_opening, $customer_id]);
            }
            break;
        case 'specific_sales':
            $stmt = $pdo->prepare('SELECT sale_id, allocated_amount FROM customer_payment_allocations WHERE debt_payment_id = ?');
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $allocation) {
                $pdo->prepare('UPDATE sales SET remaining_amount = remaining_amount + ? WHERE id = ? AND customer_id = ?')
                    ->execute([floatval($allocation['allocated_amount']), $allocation['sale_id'], $customer_id]);
            }
            break;
        case 'fifo':
        default:
            if ($old_from_opening > 0) {
                $pdo->prepare('UPDATE customers SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')
                    ->execute([$old_from_opening, $customer_id]);
            }

            if ($old_from_sales > 0) {
                $stmt = $pdo->prepare('SELECT sale_id, allocated_amount FROM customer_payment_allocations WHERE debt_payment_id = ? ORDER BY id DESC');
                $stmt->execute([$id]);
                $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($allocations)) {
                    foreach ($allocations as $allocation) {
                        $pdo->prepare('UPDATE sales SET remaining_amount = remaining_amount + ? WHERE id = ? AND customer_id = ?')
                            ->execute([floatval($allocation['allocated_amount']), $allocation['sale_id'], $customer_id]);
                    }
                } else {
                    $usd_left = $old_from_sales;
                    $stmt = $pdo->prepare('SELECT id, remaining_amount, total_price FROM sales WHERE customer_id = ? AND remaining_amount < total_price ORDER BY order_date DESC, id DESC');
                    $stmt->execute([$customer_id]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $sale) {
                        if ($usd_left <= 0) {
                            break;
                        }
                        $max_add = $sale['total_price'] - $sale['remaining_amount'];
                        $to_add = min($max_add, $usd_left);
                        if ($to_add > 0) {
                            $pdo->prepare('UPDATE sales SET remaining_amount = remaining_amount + ? WHERE id = ?')
                                ->execute([$to_add, $sale['id']]);
                            $usd_left -= $to_add;
                        }
                    }

                    if ($usd_left > 0) {
                        $pdo->prepare('UPDATE customers SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')
                            ->execute([$usd_left, $customer_id]);
                    }
                }
            }
            break;
    }

    $pdo->prepare('DELETE FROM customer_payment_allocations WHERE debt_payment_id = ?')->execute([$id]);

    $paid_iqd_usd = $dolar_rate > 0 ? $paid_iqd / ($dolar_rate / 100) : 0;
    $total_paid_usd = $paid_usd + $paid_iqd_usd + $discount;

    $stmt = $pdo->prepare('SELECT opening_debt_usd FROM customers WHERE id = ? FOR UPDATE');
    $stmt->execute([$customer_id]);
    $opening_debt_usd = floatval($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(remaining_amount), 0) FROM sales WHERE customer_id = ? AND payment_type = 'قەرز'");
    $stmt->execute([$customer_id]);
    $sales_remaining = floatval($stmt->fetchColumn() ?? 0);

    $total_debt_usd = $opening_debt_usd + $sales_remaining;
    $tolerance = 0.01;

    if ($payment_type === 'opening_debt_only') {
        if ($total_paid_usd > ($opening_debt_usd + $tolerance)) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'msg' => 'بڕی پارە زیاترە لە قەرزی سەرەتایی!']);
            exit;
        }
    } elseif ($payment_type === 'specific_sales') {
        $total_selected_remaining = 0;
        $total_requested = 0;

        foreach ($specific_sales as $sale_id => $amount) {
            $amount = floatval($amount);
            if ($amount <= 0) {
                continue;
            }
            $stmt = $pdo->prepare('SELECT remaining_amount FROM sales WHERE id = ? AND customer_id = ?');
            $stmt->execute([$sale_id, $customer_id]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sale) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'msg' => 'فرۆشتنێکی دیاریکراو نەدۆزرایەوە!']);
                exit;
            }
            $total_selected_remaining += floatval($sale['remaining_amount']);
            $total_requested += $amount;
        }

        if ($total_paid_usd > ($total_selected_remaining + $tolerance)) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'msg' => 'پارە زیاترە لە ماوەی فاکتەرەکان!']);
            exit;
        }

        if (abs($total_requested - $total_paid_usd) > $tolerance) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'msg' => 'کۆی پارەی دابەشکراو دەبێت یەکسان بێت بە بڕی پارەدان!']);
            exit;
        }
    } else {
        if ($total_paid_usd > ($total_debt_usd + $tolerance)) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'msg' => 'پارە زیاترە لە کۆی قەرز!']);
            exit;
        }
    }

    $usd_left = $total_paid_usd;
    $paid_from_opening = 0;
    $paid_from_sales = 0;
    $payment_allocations = [];

    if ($payment_type === 'opening_debt_only' || $payment_type === 'fifo') {
        if ($opening_debt_usd > 0 && $usd_left > 0) {
            $to_deduct = min($opening_debt_usd, $usd_left);
            $pdo->prepare('UPDATE customers SET opening_debt_usd = GREATEST(opening_debt_usd - ?, 0) WHERE id = ?')
                ->execute([$to_deduct, $customer_id]);
            $paid_from_opening = $to_deduct;
            $usd_left -= $to_deduct;
        }
    }

    if ($payment_type === 'specific_sales') {
        foreach ($specific_sales as $sale_id => $amount) {
            $amount = floatval($amount);
            if ($amount <= 0 || $usd_left <= 0) {
                continue;
            }
            $stmt = $pdo->prepare('SELECT remaining_amount FROM sales WHERE id = ? AND customer_id = ?');
            $stmt->execute([$sale_id, $customer_id]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sale) {
                continue;
            }
            $to_deduct = min(floatval($sale['remaining_amount']), $amount, $usd_left);
            if ($to_deduct > 0) {
                $pdo->prepare('UPDATE sales SET remaining_amount = remaining_amount - ? WHERE id = ?')
                    ->execute([$to_deduct, $sale_id]);
                $usd_left -= $to_deduct;
                $paid_from_sales += $to_deduct;
                $payment_allocations[] = ['sale_id' => $sale_id, 'amount' => $to_deduct];
            }
        }
    } else if ($payment_type === 'fifo' && $usd_left > 0) {
        $stmt = $pdo->prepare('SELECT id, remaining_amount FROM sales WHERE customer_id = ? AND remaining_amount > 0 ORDER BY order_date ASC, id ASC');
        $stmt->execute([$customer_id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $sale) {
            if ($usd_left <= 0) {
                break;
            }
            $to_deduct = min(floatval($sale['remaining_amount']), $usd_left);
            if ($to_deduct > 0) {
                $pdo->prepare('UPDATE sales SET remaining_amount = remaining_amount - ? WHERE id = ?')
                    ->execute([$to_deduct, $sale['id']]);
                $usd_left -= $to_deduct;
                $paid_from_sales += $to_deduct;
                $payment_allocations[] = ['sale_id' => $sale['id'], 'amount' => $to_deduct];
            }
        }
    }

    $updateStmt = $pdo->prepare('UPDATE customer_debt_payments SET date=?, dolar_rate=?, paid_usd=?, paid_iqd=?, discount=?, note=?, payment_type=?, from_opening_debt_usd=?, from_sales_usd=? WHERE id=?');
    $result = $updateStmt->execute([$date, $dolar_rate, $paid_usd, $paid_iqd, $discount, $note, $payment_type, $paid_from_opening, $paid_from_sales, $id]);

    if (!$result) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە!']);
        exit;
    }

    if (!empty($payment_allocations)) {
        $allocStmt = $pdo->prepare('INSERT INTO customer_payment_allocations (debt_payment_id, sale_id, allocated_amount) VALUES (?, ?, ?)');
        foreach ($payment_allocations as $allocation) {
            $allocStmt->execute([$id, $allocation['sale_id'], $allocation['amount']]);
        }
    }

    $stmt = $pdo->prepare('SELECT name, mobile1 FROM customers WHERE id = ?');
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
    $customer_name = $customer['name'] ?? 'Unknown';
    $customer_phone = $customer['mobile1'] ?? 'هیچ ژمارەیەک نییە';

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
        'payment_type' => $payment_type,
        'from_opening_debt_usd' => $paid_from_opening,
        'from_sales_usd' => $paid_from_sales
    ];

    $iqd_equivalent = $dolar_rate > 0 ? ($paid_iqd / ($dolar_rate / 100)) : 0;
    $additional_info = [
        'action_type' => 'customer_debt_payment_update',
        'payment_method' => $paid_usd > 0 ? 'USD' : ($paid_iqd > 0 ? 'IQD' : 'none'),
        'total_paid_usd_equivalent' => $paid_usd + $iqd_equivalent + $discount,
        'debt_reduction_type' => $paid_from_opening > 0 ? 'opening_debt' : 'sales_debt'
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

    $pdo->commit();

    echo json_encode(['success' => true, 'msg' => 'قەرز بەسەرکەوتوویی نوێکرایەوە!']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException in update_return_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Exception in update_return_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}

