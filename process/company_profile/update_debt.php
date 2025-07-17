<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('update_debt')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ دەستکاری دانەوەی قەرز']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $date = $_POST['date'] ?? null;
    $amount_usd = floatval($_POST['amount_usd'] ?? 0);
    $amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
    $dollar_rate = floatval($_POST['dollar_rate'] ?? 150000);
    $note = $_POST['note'] ?? '';
    if (!$date || ($amount_usd <= 0 && $amount_iqd <= 0)) {
        echo json_encode(['success' => false, 'msg' => 'بە لایەنی کەم یەک بڕ پڕبکە (دۆلار یان دینار)']);
        exit;
    }
    // Get old debt info
    $stmt = $pdo->prepare('SELECT * FROM debt_payments WHERE id = ?');
    $stmt->execute([$id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$old) {
        echo json_encode(['success' => false, 'msg' => 'دانەوەی قەرز نەدۆزرایەوە']);
        exit;
    }
    $company_id = $old['company_id'];
    $old_usd = floatval($old['amount_usd']);
    $old_iqd = floatval($old['amount_iqd']);
    // Check not exceeding current debt (after reversing old value, opening + current)
    $debt = $pdo->prepare('SELECT debt_usd, debt_iqd, opening_debt_usd, opening_debt_iqd FROM company WHERE id = ?');
    $debt->execute([$company_id]);
    $row = $debt->fetch(PDO::FETCH_ASSOC);
    $max_usd = floatval($row['debt_usd']) + floatval($row['opening_debt_usd']) + $old_usd;
    $max_iqd = floatval($row['debt_iqd']) + floatval($row['opening_debt_iqd']) + $old_iqd;
    if (($amount_usd > 0 && $amount_usd > $max_usd) || ($amount_iqd > 0 && $amount_iqd > $max_iqd)) {
        echo json_encode(['success' => false, 'msg' => 'نابێت بڕی پارەی گەرەوا زیاتر بێت لە بڕی قەرز!']);
        exit;
    }
    // Reverse old FIFO effect (like delete, but for opening debt too)
    if ($old_usd > 0) {
        // Restore opening_debt_usd first
        $stmt = $pdo->prepare('SELECT opening_debt_usd FROM company WHERE id = ?');
        $stmt->execute([$company_id]);
        $opening = floatval($stmt->fetchColumn());
        $toRestore = min($old_usd, $opening + $old_usd); // restore up to original
        $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')->execute([min($old_usd, $old_usd), $company_id]);
        $remaining = $old_usd - min($old_usd, $old_usd);
        // Restore FIFO to purchases
        if ($remaining > 0) {
            $purchases = $pdo->prepare('SELECT id, remaining_usd, price FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" ORDER BY date ASC, id ASC');
            $purchases->execute([$company_id, 'دۆلار']);
            foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($remaining <= 0) break;
                $max_add = $row['price'] - $row['remaining_usd'];
                if ($max_add <= 0) continue;
                $toAdd = min($max_add, $remaining);
                $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd + ? WHERE id = ?')->execute([$toAdd, $row['id']]);
                $remaining -= $toAdd;
            }
        }
        $pdo->prepare('UPDATE company SET debt_usd = debt_usd + ? WHERE id = ?')->execute([$old_usd - min($old_usd, $old_usd), $company_id]);
    }
    if ($old_iqd > 0) {
        // Restore opening_debt_iqd first
        $stmt = $pdo->prepare('SELECT opening_debt_iqd FROM company WHERE id = ?');
        $stmt->execute([$company_id]);
        $opening = floatval($stmt->fetchColumn());
        $toRestore = min($old_iqd, $old_iqd);
        $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd + ? WHERE id = ?')->execute([$toRestore, $company_id]);
        $remaining = $old_iqd - $toRestore;
        // Restore FIFO to purchases
        if ($remaining > 0) {
            $purchases = $pdo->prepare('SELECT id, remaining_iqd, amount_iqd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" ORDER BY date ASC, id ASC');
            $purchases->execute([$company_id, 'دینار']);
            foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($remaining <= 0) break;
                $max_add = $row['amount_iqd'] - $row['remaining_iqd'];
                if ($max_add <= 0) continue;
                $toAdd = min($max_add, $remaining);
                $pdo->prepare('UPDATE purchases SET remaining_iqd = remaining_iqd + ? WHERE id = ?')->execute([$toAdd, $row['id']]);
                $remaining -= $toAdd;
            }
        }
        $pdo->prepare('UPDATE company SET debt_iqd = debt_iqd + ? WHERE id = ?')->execute([$old_iqd - $toRestore, $company_id]);
    }
    // Update debt_payments
    $stmt = $pdo->prepare('UPDATE debt_payments SET date = ?, amount_usd = ?, amount_iqd = ?, dollar_rate = ?, note = ? WHERE id = ? AND company_id = ?');
    $ok = $stmt->execute([$date, $amount_usd, $amount_iqd, $dollar_rate, $note, $id, $company_id]);
    if (!$ok) {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە']);
        exit;
    }
    require_once __DIR__ . '/../../includes/notify.php';
    notify('update', 'debt_payments', $id, 'پارەدانی قەرزی کۆمپانیا نوێکرایەوە (کۆمپانیا: ' . $company_id . ')');
    // Apply new FIFO effect (like add)
    if ($amount_usd > 0) {
        $remaining = $amount_usd;
        // 1. Reduce opening_debt_usd first
        $stmt = $pdo->prepare('SELECT opening_debt_usd FROM company WHERE id = ?');
        $stmt->execute([$company_id]);
        $opening = floatval($stmt->fetchColumn());
        if ($opening > 0) {
            $toPay = min($opening, $remaining);
            $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd - ? WHERE id = ?')->execute([$toPay, $company_id]);
            $remaining -= $toPay;
        }
        // 2. Then FIFO from purchases
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
        $pdo->prepare('UPDATE company SET debt_usd = debt_usd - ? WHERE id = ?')->execute([$amount_usd - min($amount_usd, $opening), $company_id]);
    }
    if ($amount_iqd > 0) {
        $remaining = $amount_iqd;
        // 1. Reduce opening_debt_iqd first
        $stmt = $pdo->prepare('SELECT opening_debt_iqd FROM company WHERE id = ?');
        $stmt->execute([$company_id]);
        $opening = floatval($stmt->fetchColumn());
        if ($opening > 0) {
            $toPay = min($opening, $remaining);
            $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd - ? WHERE id = ?')->execute([$toPay, $company_id]);
            $remaining -= $toPay;
        }
        // 2. Then FIFO from purchases
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
        $pdo->prepare('UPDATE company SET debt_iqd = debt_iqd - ? WHERE id = ?')->execute([$amount_iqd - min($amount_iqd, $opening), $company_id]);
    }
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
