<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../config/db_conected.php';
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
    $debt = $pdo->prepare('SELECT opening_debt_usd, opening_debt_iqd FROM company WHERE id = ?');
    $debt->execute([$company_id]);
    $row = $debt->fetch(PDO::FETCH_ASSOC);
    
    // Get remaining amounts from purchases
    $purchases_data = $pdo->prepare("
        SELECT 
            COALESCE(SUM(remaining_usd), 0) as remaining_usd,
            COALESCE(SUM(remaining_iqd), 0) as remaining_iqd,
            COALESCE(SUM(remaining_iqd / NULLIF(exchange_rate / 100, 0)), 0) as remaining_iqd_converted
        FROM purchases 
        WHERE company_id = ? AND payment_type = 'قەرز'
    ");
    $purchases_data->execute([$company_id]);
    $purchases_result = $purchases_data->fetch(PDO::FETCH_ASSOC);
    
    $total_usd = floatval($purchases_result['remaining_usd']) + floatval($row['opening_debt_usd']) + floatval($purchases_result['remaining_iqd_converted']);
    $total_iqd = floatval($purchases_result['remaining_iqd']) + floatval($row['opening_debt_iqd']);
    if (($amount_usd > 0 && $amount_usd > $total_usd) || ($amount_iqd > 0 && $amount_iqd > $total_iqd)) {
        echo json_encode(['success' => false, 'msg' => 'نابێت بڕی پارەی گەرەوا زیاتر بێت لە بڕی قەرز!']);
        exit;
    }
    // Get company information for notification
    $stmt = $pdo->prepare("SELECT name, phone FROM company WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    $company_name = $company['name'] ?? 'Unknown';
    $company_phone = $company['phone'] ?? 'هیچ ژمارەیەک نییە';

    // Insert into debt_payments
    $stmt = $pdo->prepare('INSERT INTO debt_payments (company_id, date, amount_usd, amount_iqd, dollar_rate, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $ok = $stmt->execute([$company_id, $date, $amount_usd, $amount_iqd, $dollar_rate, $note, $user_id]);
    if (!$ok) {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە تۆمارکردن']);
        exit;
    }

    $debt_payment_id = $pdo->lastInsertId();

    // Create detailed notification with company information
    $new_values = [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'company_phone' => $company_phone,
        'date' => $date,
        'amount_usd' => $amount_usd,
        'amount_iqd' => $amount_iqd,
        'dollar_rate' => $dollar_rate,
        'note' => $note,
        'created_by' => $user_id
    ];

    $additional_info = [
        'action_type' => 'company_debt_payment',
        'payment_method' => $amount_usd > 0 ? 'USD' : ($amount_iqd > 0 ? 'IQD' : 'none'),
        'total_amount' => $amount_usd + $amount_iqd
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'insert',
        'debt_payments',
        $debt_payment_id,
        "پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: $company_name, تەلەفۆن: $company_phone)",
        null, // No old values for insert
        $new_values,
        $additional_info,
        getUserIP()
    );
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
        // Note: debt_usd column doesn't exist in company table, so we don't update it
        // The debt is calculated from purchases table and opening_debt columns
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
        // Note: debt_iqd column doesn't exist in company table, so we don't update it
        // The debt is calculated from purchases table and opening_debt columns
    }
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
