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
if (!hasPermission('add_debt')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

$customer_id = $_POST['customer_id'] ?? null;
$date = $_POST['date'] ?? null;
$dolar_rate = floatval($_POST['dolar_rate'] ?? 0);
$paid_usd = floatval($_POST['paid_usd'] ?? 0);
$paid_iqd = floatval($_POST['paid_iqd'] ?? 0);
$discount = floatval($_POST['discount'] ?? 0);
$note = $_POST['note'] ?? '';

if (!$customer_id || !$date || ($paid_usd <= 0 && $paid_iqd <= 0 && $discount <= 0)) {
    echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان پڕ بکە!']);
    exit;
}

// 1. هەژمارکردنی بڕی پارەی داوە بە دۆلار
$paid_iqd_usd = $dolar_rate > 0 ? $paid_iqd / ($dolar_rate / 100) : 0;
$total_paid_usd = $paid_usd + $paid_iqd_usd + $discount;

// 2. چێککردنی قەرز
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

// زیادکردنی قەرزە گەڕاوەکە لە customer_debt_payments
$stmt = $pdo->prepare('INSERT INTO customer_debt_payments (customer_id, date, dolar_rate, paid_usd, paid_iqd, discount, note, from_opening_debt_usd, from_sales_usd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$ok = $stmt->execute([$customer_id, $date, $dolar_rate, $paid_usd, $paid_iqd, $discount, $note, $paid_from_opening, $paid_from_sales]);
if (!$ok) {
    echo json_encode(['success' => false, 'msg' => 'هەڵە لە تۆمارکردن']);
    exit;
}

// 5. تەنها debt_usd بە قەدەغەی paid_from_sales کەم بکە
$upd = $pdo->prepare("UPDATE customers SET debt_usd = GREATEST(debt_usd - ?, 0) WHERE id = ?");
$upd->execute([$paid_from_sales, $customer_id]);

echo json_encode(['success' => true, 'msg' => 'دانەوەی قەرز بەسەرکەوتوویی تۆمارکرا!']);
