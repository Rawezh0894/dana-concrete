<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('add_debt')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ دانەوەی قەرز']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = $_POST['company_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $amount_usd = floatval($_POST['amount_usd'] ?? 0);
    $amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
    $dollar_rate = floatval($_POST['dollar_rate'] ?? 150000);
    $note = $_POST['note'] ?? '';
    $user_id = $_SESSION['user_id'];
    if (!$company_id || !$date || ($amount_usd <= 0 && $amount_iqd <= 0)) {
        echo json_encode(['success' => false, 'msg' => 'بە لایەنی کەم یەک بڕ پڕبکە (دۆلار یان دینار)']);
        exit;
    }
    // Check not exceeding current debt (opening + current)
    $debt = $pdo->prepare('SELECT debt_usd, debt_iqd, opening_debt_usd, opening_debt_iqd FROM company WHERE id = ?');
    $debt->execute([$company_id]);
    $row = $debt->fetch(PDO::FETCH_ASSOC);
    $total_usd = floatval($row['debt_usd']) + floatval($row['opening_debt_usd']);
    $total_iqd = floatval($row['debt_iqd']) + floatval($row['opening_debt_iqd']);
    if (($amount_usd > 0 && $amount_usd > $total_usd) || ($amount_iqd > 0 && $amount_iqd > $total_iqd)) {
        echo json_encode(['success' => false, 'msg' => 'نابێت بڕی پارەی گەرەوا زیاتر بێت لە بڕی قەرز!']);
        exit;
    }
    // Insert into debt_payments
    $stmt = $pdo->prepare('INSERT INTO debt_payments (company_id, date, amount_usd, amount_iqd, dollar_rate, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $ok = $stmt->execute([$company_id, $date, $amount_usd, $amount_iqd, $dollar_rate, $note, $user_id]);
    if (!$ok) {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە تۆمارکردن']);
        exit;
    }
    // FIFO: Reduce opening_debt_usd first, then remaining_usd in purchases
    if ($amount_usd > 0) {
        $remaining = $amount_usd;
        // Reduce opening_debt_usd first
        $stmt = $pdo->prepare('SELECT opening_debt_usd FROM company WHERE id = ?');
        $stmt->execute([$company_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $opening = floatval($row['opening_debt_usd']);
        if ($opening > 0) {
            $toPay = min($opening, $remaining);
            $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd - ? WHERE id = ?')->execute([$toPay, $company_id]);
            $remaining -= $toPay;
        }
        // Then FIFO from purchases
        if ($remaining > 0) {
            $purchases = $pdo->prepare('SELECT id, remaining_usd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" AND remaining_usd > 0 ORDER BY date ASC, id ASC');
            $purchases->execute([$company_id, 'دۆلار']);
            foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($remaining <= 0) break;
                $toPay = min($row['remaining_usd'], $remaining);
                $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd - ? WHERE id = ?')->execute([$toPay, $row['id']]);
                $remaining -= $toPay;
            }
        }
        // Update debt_usd by the total paid from purchases (not opening debt)
        $paid_from_purchases = $amount_usd - ($amount_usd - $remaining) - min($amount_usd, $opening);
        $pdo->prepare('UPDATE company SET debt_usd = debt_usd - ? WHERE id = ?')->execute([$amount_usd - min($amount_usd, $opening), $company_id]);
    }
    // FIFO: Reduce opening_debt_iqd first, then remaining_iqd in purchases
    if ($amount_iqd > 0) {
        $remaining = $amount_iqd;
        // Reduce opening_debt_iqd first
        $stmt = $pdo->prepare('SELECT opening_debt_iqd FROM company WHERE id = ?');
        $stmt->execute([$company_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $opening = floatval($row['opening_debt_iqd']);
        if ($opening > 0) {
            $toPay = min($opening, $remaining);
            $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd - ? WHERE id = ?')->execute([$toPay, $company_id]);
            $remaining -= $toPay;
        }
        // Then FIFO from purchases
        if ($remaining > 0) {
            $purchases = $pdo->prepare('SELECT id, remaining_iqd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" AND remaining_iqd > 0 ORDER BY date ASC, id ASC');
            $purchases->execute([$company_id, 'دینار']);
            foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($remaining <= 0) break;
                $toPay = min($row['remaining_iqd'], $remaining);
                $pdo->prepare('UPDATE purchases SET remaining_iqd = remaining_iqd - ? WHERE id = ?')->execute([$toPay, $row['id']]);
                $remaining -= $toPay;
            }
        }
        // Update debt_iqd by the total paid from purchases (not opening debt)
        $pdo->prepare('UPDATE company SET debt_iqd = debt_iqd - ? WHERE id = ?')->execute([$amount_iqd - min($amount_iqd, $opening), $company_id]);
    }
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
