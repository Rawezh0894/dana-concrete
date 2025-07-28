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
    $dolar_rate = floatval($_POST['dolar_rate'] ?? 0);
    $paid_usd = floatval($_POST['paid_usd'] ?? 0);
    $paid_iqd = floatval($_POST['paid_iqd'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $note = $_POST['note'] ?? '';

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', company_id='$company_id', date='$date', dolar_rate='$dolar_rate', paid_usd='$paid_usd', paid_iqd='$paid_iqd', discount='$discount', note='$note'");

    if (!$id || !$company_id || !$date || ($paid_usd <= 0 && $paid_iqd <= 0 && $discount <= 0)) {
        error_log('Missing required fields for company debt update');
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان پڕ بکە!']);
        exit;
    }

    // Check if debt payment exists
    $checkStmt = $pdo->prepare('SELECT id, from_opening_debt_usd, from_purchases_usd FROM debt_payments WHERE id = ?');
    $checkStmt->execute([$id]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        error_log('Company debt payment not found: ID=' . $id);
        echo json_encode(['success' => false, 'msg' => 'قەرز نەدۆزرایەوە!']);
        exit;
    }
    
    error_log('Found company debt payment for update: ' . print_r($row, true));

    // وەرگرتنی بڕەکانی کۆن
    $old_from_opening = floatval($row['from_opening_debt_usd'] ?? 0);
    $old_from_purchases = floatval($row['from_purchases_usd'] ?? 0);

    // بگەڕێنەوە بۆ شوێنەکانیان (یەکسان بە delete)
    if ($old_from_opening > 0) {
        $upd = $pdo->prepare("UPDATE company SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?");
        $upd->execute([$old_from_opening, $company_id]);
    }

    if ($old_from_purchases > 0) {
        // زیادکردنی بۆ purchases.remaining_usd بە FIFO
        $usd_left = $old_from_purchases;
        $stmt = $pdo->prepare("SELECT id, remaining_usd, paid_usd FROM purchases WHERE company_id = ? ORDER BY date ASC, id ASC");
        $stmt->execute([$company_id]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($purchases as $purchase) {
            if ($usd_left <= 0) break;
            $max_add = $purchase['paid_usd'] - $purchase['remaining_usd'];
            $to_add = min($max_add, $usd_left);
            if ($to_add > 0) {
                $upd = $pdo->prepare("UPDATE purchases SET remaining_usd = remaining_usd + ? WHERE id = ?");
                $upd->execute([$to_add, $purchase['id']]);
                $usd_left -= $to_add;
            }
        }
    }

    // هەژمارکردنی بڕی نوێ
    $paid_iqd_usd = $dolar_rate > 0 ? $paid_iqd / ($dolar_rate / 100) : 0;
    $total_usd = $paid_usd + $paid_iqd_usd + $discount;

    // هەژمارکردنی بۆ opening_debt_usd و purchases.remaining_usd
    $from_opening_debt_usd = 0;
    $from_purchases_usd = 0;

    // یەکەم بۆ opening_debt_usd
    $stmt = $pdo->prepare("SELECT opening_debt_usd FROM company WHERE id = ?");
    $stmt->execute([$company_id]);
    $opening_debt = floatval($stmt->fetchColumn() ?? 0);

    if ($opening_debt > 0) {
        $from_opening_debt_usd = min($opening_debt, $total_usd);
        $total_usd -= $from_opening_debt_usd;
    }

    // پاشان بۆ purchases.remaining_usd
    if ($total_usd > 0) {
        $stmt = $pdo->prepare("SELECT id, remaining_usd FROM purchases WHERE company_id = ? AND remaining_usd > 0 ORDER BY date ASC, id ASC");
        $stmt->execute([$company_id]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($purchases as $purchase) {
            if ($total_usd <= 0) break;
            $to_deduct = min($purchase['remaining_usd'], $total_usd);
            if ($to_deduct > 0) {
                $upd = $pdo->prepare("UPDATE purchases SET remaining_usd = remaining_usd - ? WHERE id = ?");
                $upd->execute([$to_deduct, $purchase['id']]);
                $from_purchases_usd += $to_deduct;
                $total_usd -= $to_deduct;
            }
        }
    }

    // نوێکردنەوەی قەرزەکە
    $upd = $pdo->prepare('UPDATE debt_payments SET date=?, dolar_rate=?, paid_usd=?, paid_iqd=?, discount=?, note=?, from_opening_debt_usd=?, from_purchases_usd=? WHERE id=?');
    $result = $upd->execute([$date, $dolar_rate, $paid_usd, $paid_iqd, $discount, $note, $from_opening_debt_usd, $from_purchases_usd, $id]);

    if ($result) {
        // کەمکردنەوەی بڕەکانی نوێ
        if ($from_opening_debt_usd > 0) {
            $upd = $pdo->prepare("UPDATE company SET opening_debt_usd = opening_debt_usd - ? WHERE id = ?");
            $upd->execute([$from_opening_debt_usd, $company_id]);
        }

        if ($from_purchases_usd > 0) {
            // No need to update company debt_usd anymore - it's handled by purchases.remaining_usd
        }

        require_once __DIR__ . '/../../includes/notify.php';
        notify('update', 'debt_payments', $id, 'پارەدانی قەرزی کۆمپانیا نوێکرایەوە (کۆمپانیا: ' . $company_id . ')');
        error_log('Company debt successfully updated: ID=' . $id . ', Company=' . $company_id);
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
