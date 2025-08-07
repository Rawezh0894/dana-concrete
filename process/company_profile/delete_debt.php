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
if (!hasPermission('delete_debt')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ سڕینەوەی دانەوەی قەرز']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    // Get the debt payment info
    $stmt = $pdo->prepare('SELECT * FROM debt_payments WHERE id = ?');
    $stmt->execute([$id]);
    $debt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$debt) {
        echo json_encode(['success' => false, 'msg' => 'دانەوەی قەرز نەدۆزرایەوە']);
        exit;
    }
    $company_id = $debt['company_id'];
    $amount_usd = floatval($debt['amount_usd']);
    $amount_iqd = floatval($debt['amount_iqd']);
    // Get company information for notification
    $stmt = $pdo->prepare("SELECT name, phone FROM company WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    $company_name = $company['name'] ?? 'Unknown';
    $company_phone = $company['phone'] ?? 'هیچ ژمارەیەک نییە';

    // Create old values for notification
    $old_values = [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'company_phone' => $company_phone,
        'amount_usd' => $amount_usd,
        'amount_iqd' => $amount_iqd,
        'date' => $debt['date'],
        'note' => $debt['note'] ?? ''
    ];

    $additional_info = [
        'action_type' => 'company_debt_payment_deletion',
        'payment_method' => $amount_usd > 0 ? 'USD' : ($amount_iqd > 0 ? 'IQD' : 'none'),
        'total_amount' => $amount_usd + $amount_iqd
    ];

    // Delete the debt payment
    $del = $pdo->prepare('DELETE FROM debt_payments WHERE id = ?');
    $ok = $del->execute([$id]);
    if (!$ok) {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە']);
        exit;
    }

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'delete',
        'debt_payments',
        $id,
        "پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: $company_name, تەلەفۆن: $company_phone)",
        $old_values,
        null, // No new values for delete
        $additional_info,
        getUserIP()
    );
    // Reverse FIFO: add back to purchases first, then opening debt
    if ($amount_usd > 0) {
        $remaining = $amount_usd;
        // 1. FIFO to purchases (from latest to oldest)
        $purchases = $pdo->prepare('SELECT id, remaining_usd, price FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" ORDER BY date DESC, id DESC');
        $purchases->execute([$company_id, 'دۆلار']);
        foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($remaining <= 0) break;
            $max_add = $row['price'] - $row['remaining_usd'];
            if ($max_add <= 0) continue;
            $toAdd = min($max_add, $remaining);
            $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd + ? WHERE id = ?')->execute([$toAdd, $row['id']]);
            $remaining -= $toAdd;
        }
        // 2. Then restore opening_debt_usd
        if ($remaining > 0) {
            $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')->execute([$remaining, $company_id]);
        }
        // Note: debt_usd column doesn't exist in company table
        // The debt is calculated from opening_debt + remaining amounts in purchases
    }
    if ($amount_iqd > 0) {
        $remaining = $amount_iqd;
        // 1. FIFO to purchases (from latest to oldest)
        $purchases = $pdo->prepare('SELECT id, remaining_iqd, amount_iqd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" ORDER BY date DESC, id DESC');
        $purchases->execute([$company_id, 'دینار']);
        foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($remaining <= 0) break;
            $max_add = $row['amount_iqd'] - $row['remaining_iqd'];
            if ($max_add <= 0) continue;
            $toAdd = min($max_add, $remaining);
            $pdo->prepare('UPDATE purchases SET remaining_iqd = remaining_iqd + ? WHERE id = ?')->execute([$toAdd, $row['id']]);
            $remaining -= $toAdd;
        }
        // 2. Then restore opening_debt_iqd
        if ($remaining > 0) {
            $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd + ? WHERE id = ?')->execute([$remaining, $company_id]);
        }
        // Note: debt_iqd column doesn't exist in company table
        // The debt is calculated from opening_debt + remaining amounts in purchases
    }
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
