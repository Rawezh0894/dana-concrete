<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}
if (!hasPermission('update_debt')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

$id = $_POST['id'] ?? null;
$customer_id = $_POST['customer_id'] ?? null;
$date = $_POST['date'] ?? null;
$dolar_rate = floatval($_POST['dolar_rate'] ?? 0);
$paid_usd = floatval($_POST['paid_usd'] ?? 0);
$paid_iqd = floatval($_POST['paid_iqd'] ?? 0);
$discount = floatval($_POST['discount'] ?? 0);
$note = $_POST['note'] ?? '';

if (!$id || !$customer_id || !$date || ($paid_usd <= 0 && $paid_iqd <= 0 && $discount <= 0)) {
    echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان پڕ بکە!']);
    exit;
}

// وەرگرتنی بڕەکانی کۆن
$stmt = $pdo->prepare('SELECT from_opening_debt_usd, from_sales_usd FROM customer_debt_payments WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$old_from_opening = floatval($row['from_opening_debt_usd'] ?? 0);
$old_from_sales = floatval($row['from_sales_usd'] ?? 0);

// بگەڕێنەوە بۆ شوێنەکانیان (یەکسان بە delete)
if ($old_from_opening > 0) {
    $upd = $pdo->prepare("UPDATE customers SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?");
    $upd->execute([$old_from_opening, $customer_id]);
}
if ($old_from_sales > 0) {
    $upd = $pdo->prepare('UPDATE customers SET debt_usd = debt_usd + ? WHERE id = ?');
    $upd->execute([$old_from_sales, $customer_id]);
    $usd_left = $old_from_sales;
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

// هەژمارکردنی بڕی پارەی داوە بە دۆلار
$paid_iqd_usd = $dolar_rate > 0 ? $paid_iqd / ($dolar_rate / 100) : 0;
$total_paid_usd = $paid_usd + $paid_iqd_usd + $discount;

// چێککردنی قەرز
$stmt = $pdo->prepare('SELECT debt_usd, opening_debt_usd FROM customers WHERE id = ?');
$stmt->execute([$customer_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$current_debt_usd = floatval($row['debt_usd'] ?? 0);
$opening_debt_usd = floatval($row['opening_debt_usd'] ?? 0);
$total_debt_usd = $current_debt_usd + $opening_debt_usd;
if ($total_paid_usd > $total_debt_usd) {
    echo json_encode(['success' => false, 'msg' => 'بڕی پارەی داوە نابێت زیاتر بێت لە قەرز!']);
    exit;
}

// FIFO نوێ: سەرەتا opening_debt_usd کەم بکە، پاشان sales
$usd_left = $total_paid_usd;
$from_sales_usd = 0;
$from_opening_debt_usd = 0;
if ($opening_debt_usd > 0) {
    $to_deduct = min($opening_debt_usd, $usd_left);
    $upd = $pdo->prepare("UPDATE customers SET opening_debt_usd = GREATEST(opening_debt_usd - ?, 0) WHERE id = ?");
    $upd->execute([$to_deduct, $customer_id]);
    $from_opening_debt_usd = $to_deduct;
    $usd_left -= $to_deduct;
}
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
        $from_sales_usd += $to_deduct;
    }
}

// نوێکردنەوەی customer_debt_payments
$stmt = $pdo->prepare('UPDATE customer_debt_payments SET date=?, dolar_rate=?, paid_usd=?, paid_iqd=?, discount=?, note=?, from_opening_debt_usd=?, from_sales_usd=? WHERE id=?');
$ok = $stmt->execute([$date, $dolar_rate, $paid_usd, $paid_iqd, $discount, $note, $from_opening_debt_usd, $from_sales_usd, $id]);
if (!$ok) {
    echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە']);
    exit;
}
require_once __DIR__ . '/../../includes/notify.php';
notify('update', 'customer_debt_payments', $id, 'پارەدانی قەرزی کڕیار نوێکرایەوە (کڕیار: ' . $customer_id . ')');
echo json_encode(['success' => true, 'msg' => 'دانەوەی قەرز بەسەرکەوتوویی نوێکرایەوە!']);
