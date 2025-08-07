<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('update_debt.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for company debt update');
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('update_debt')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to update company debt');
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    $company_id = $_POST['company_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $dollar_rate = floatval($_POST['dollar_rate'] ?? 150000);
    $amount_usd = floatval($_POST['amount_usd'] ?? 0);
    $amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
    $note = $_POST['note'] ?? '';

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', company_id='$company_id', date='$date', dollar_rate='$dollar_rate', amount_usd='$amount_usd', amount_iqd='$amount_iqd', note='$note'");

    if (!$id || !$company_id || !$date || ($amount_usd <= 0 && $amount_iqd <= 0)) {
        error_log('Missing required fields for company debt update');
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان پڕ بکە!']);
        exit;
    }

    // Check if debt payment exists and get old values
    $checkStmt = $pdo->prepare('SELECT id, amount_usd, amount_iqd, dollar_rate, note FROM debt_payments WHERE id = ?');
    $checkStmt->execute([$id]);
    $old_record = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$old_record) {
        error_log('Company debt payment not found: ID=' . $id);
        echo json_encode(['success' => false, 'msg' => 'قەرز نەدۆزرایەوە!']);
        exit;
    }
    
    error_log('Found company debt payment for update: ' . print_r($old_record, true));

    // Get old amounts
    $old_amount_usd = floatval($old_record['amount_usd'] ?? 0);
    $old_amount_iqd = floatval($old_record['amount_iqd'] ?? 0);

    // Restore old amounts (reverse the payment)
    if ($old_amount_usd > 0) {
        $remaining = $old_amount_usd;
        
        // First restore to purchases.remaining_usd (FIFO)
        $purchases = $pdo->prepare('SELECT id, remaining_usd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" ORDER BY date ASC, id ASC');
        $purchases->execute([$company_id, 'دۆلار']);
        foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($remaining <= 0) break;
            $toRestore = min($row['remaining_usd'], $remaining);
            $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd + ? WHERE id = ?')->execute([$toRestore, $row['id']]);
            $remaining -= $toRestore;
        }
        
        // Then restore opening_debt_usd
        if ($remaining > 0) {
            $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')->execute([$remaining, $company_id]);
        }
    }
    
    if ($old_amount_iqd > 0) {
        $remaining = $old_amount_iqd;
        
        // First restore to purchases.remaining_iqd (FIFO)
        $purchases = $pdo->prepare('SELECT id, remaining_iqd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" ORDER BY date ASC, id ASC');
        $purchases->execute([$company_id, 'دینار']);
        foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($remaining <= 0) break;
            $toRestore = min($row['remaining_iqd'], $remaining);
            $pdo->prepare('UPDATE purchases SET remaining_iqd = remaining_iqd + ? WHERE id = ?')->execute([$toRestore, $row['id']]);
            $remaining -= $toRestore;
        }
        
        // Then restore opening_debt_iqd
        if ($remaining > 0) {
            $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd + ? WHERE id = ?')->execute([$remaining, $company_id]);
        }
    }

    // Get company data for new payment calculation
    $stmt = $pdo->prepare('SELECT opening_debt_usd, opening_debt_iqd FROM company WHERE id = ?');
    $stmt->execute([$company_id]);
    $company_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Apply new payment using FIFO
    if ($amount_usd > 0) {
        $remaining = $amount_usd;
        
        // Reduce opening_debt_usd first
        $opening = floatval($company_data['opening_debt_usd'] ?? 0);
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
    }
    
    if ($amount_iqd > 0) {
        $remaining = $amount_iqd;
        
        // Reduce opening_debt_iqd first
        $opening = floatval($company_data['opening_debt_iqd'] ?? 0);
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
    }

    // Update the debt payment record
    $upd = $pdo->prepare('UPDATE debt_payments SET date=?, amount_usd=?, amount_iqd=?, dollar_rate=?, note=? WHERE id=?');
    $result = $upd->execute([$date, $amount_usd, $amount_iqd, $dollar_rate, $note, $id]);

    if (!$result) {
        error_log('Failed to update debt payment record');
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە']);
        exit;
    }

    // Get company information for notification
    $stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    $company_name = $company['name'] ?? 'Unknown';

    // Create detailed notification
    $old_values = [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'date' => $old_record['date'],
        'amount_usd' => $old_record['amount_usd'],
        'amount_iqd' => $old_record['amount_iqd'],
        'dollar_rate' => $old_record['dollar_rate'],
        'note' => $old_record['note']
    ];

    $new_values = [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'date' => $date,
        'amount_usd' => $amount_usd,
        'amount_iqd' => $amount_iqd,
        'dollar_rate' => $dollar_rate,
        'note' => $note
    ];

    $additional_info = [
        'action_type' => 'company_debt_payment_update',
        'payment_method' => $amount_usd > 0 ? 'USD' : ($amount_iqd > 0 ? 'IQD' : 'none'),
        'total_amount' => $amount_usd + $amount_iqd
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'update',
        'debt_payments',
        $id,
        "پارەدانی قەرزی کۆمپانیا نوێکرایەوە (کۆمپانیا: $company_name)",
        $old_values,
        $new_values,
        $additional_info,
        getUserIP()
    );

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('Exception in update_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()]);
}
