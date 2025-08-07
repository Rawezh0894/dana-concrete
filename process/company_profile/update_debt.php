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
    $amount_usd = floatval($_POST['amount_usd'] ?? 0);
    $amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
    $dollar_rate = floatval($_POST['dollar_rate'] ?? 0);
    $note = $_POST['note'] ?? '';

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', company_id='$company_id', date='$date', amount_usd='$amount_usd', amount_iqd='$amount_iqd', dollar_rate='$dollar_rate', note='$note'");

    if (!$id || !$company_id || !$date || ($amount_usd <= 0 && $amount_iqd <= 0)) {
        error_log('Missing required fields for company debt update');
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان پڕ بکە!']);
        exit;
    }

    // Check if debt payment exists
    $checkStmt = $pdo->prepare('SELECT id, amount_usd, amount_iqd FROM debt_payments WHERE id = ?');
    $checkStmt->execute([$id]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        error_log('Company debt payment not found: ID=' . $id);
        echo json_encode(['success' => false, 'msg' => 'قەرز نەدۆزرایەوە!']);
        exit;
    }
    
    error_log('Found company debt payment for update: ' . print_r($row, true));

    // وەرگرتنی بڕەکانی کۆن
    $old_amount_usd = floatval($row['amount_usd'] ?? 0);
    $old_amount_iqd = floatval($row['amount_iqd'] ?? 0);

    // بگەڕێنەوە بۆ شوێنەکانیان (یەکسان بە delete)
    if ($old_amount_usd > 0) {
        // Reduce opening_debt_usd first
        $stmt = $pdo->prepare('SELECT opening_debt_usd FROM company WHERE id = ?');
        $stmt->execute([$company_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $opening = floatval($row['opening_debt_usd']);
        if ($opening > 0) {
            $toPay = min($opening, $old_amount_usd);
            $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')->execute([$toPay, $company_id]);
        }
        // Then FIFO from purchases
        $remaining = $old_amount_usd - min($old_amount_usd, $opening);
        if ($remaining > 0) {
            $purchases = $pdo->prepare('SELECT id, remaining_usd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" AND remaining_usd > 0 ORDER BY date ASC, id ASC');
            $purchases->execute([$company_id, 'دۆلار']);
            foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($remaining <= 0) break;
                $toPay = min($row['remaining_usd'], $remaining);
                $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd + ? WHERE id = ?')->execute([$toPay, $row['id']]);
                $remaining -= $toPay;
            }
        }
    }
    
    if ($old_amount_iqd > 0) {
        // Reduce opening_debt_iqd first
        $stmt = $pdo->prepare('SELECT opening_debt_iqd FROM company WHERE id = ?');
        $stmt->execute([$company_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $opening = floatval($row['opening_debt_iqd']);
        if ($opening > 0) {
            $toPay = min($opening, $old_amount_iqd);
            $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd + ? WHERE id = ?')->execute([$toPay, $company_id]);
        }
        // Then FIFO from purchases
        $remaining = $old_amount_iqd - min($old_amount_iqd, $opening);
        if ($remaining > 0) {
            $purchases = $pdo->prepare('SELECT id, remaining_iqd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" AND remaining_iqd > 0 ORDER BY date ASC, id ASC');
            $purchases->execute([$company_id, 'دینار']);
            foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($remaining <= 0) break;
                $toPay = min($row['remaining_iqd'], $remaining);
                $pdo->prepare('UPDATE purchases SET remaining_iqd = remaining_iqd + ? WHERE id = ?')->execute([$toPay, $row['id']]);
                $remaining -= $toPay;
            }
        }
    }

    // نوێکردنەوەی قەرزەکە
    $upd = $pdo->prepare('UPDATE debt_payments SET date=?, amount_usd=?, amount_iqd=?, dollar_rate=?, note=? WHERE id=?');
    $result = $upd->execute([$date, $amount_usd, $amount_iqd, $dollar_rate, $note, $id]);

    if ($result) {
        // Apply new payment amounts
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
        }
        
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
        }

        // Get company information for notification
        $stmt = $pdo->prepare("SELECT name, phone FROM company WHERE id = ?");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch();
        $company_name = $company['name'] ?? 'Unknown';
        $company_phone = $company['phone'] ?? 'هیچ ژمارەیەک نییە';

        // Get old values for notification
        $stmt = $pdo->prepare("SELECT * FROM debt_payments WHERE id = ?");
        $stmt->execute([$id]);
        $old_record = $stmt->fetch();

        $old_values = [
            'company_id' => $old_record['company_id'],
            'date' => $old_record['date'],
            'amount_usd' => $old_record['amount_usd'],
            'amount_iqd' => $old_record['amount_iqd'],
            'dollar_rate' => $old_record['dollar_rate'],
            'note' => $old_record['note']
        ];

        $new_values = [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'company_phone' => $company_phone,
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
            "پارەدانی قەرزی کۆمپانیا نوێکرایەوە (کۆمپانیا: $company_name, تەلەفۆن: $company_phone)",
            $old_values,
            $new_values,
            $additional_info,
            getUserIP()
        );

        error_log('Company debt successfully updated: ID=' . $id . ', Company=' . $company_name . ' (ID: ' . $company_id . ')');
        echo json_encode(['success' => true, 'msg' => 'قەرز بەسەرکەوتوویی نوێکرایەوە!']);
    } else {
        error_log('Failed to update company debt: ID=' . $id);
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە!']);
    }
} catch (PDOException $e) {
    error_log('PDOException in update_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in update_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
