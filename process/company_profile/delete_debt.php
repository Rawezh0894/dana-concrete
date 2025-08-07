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
    $debt_payment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$debt_payment) {
        echo json_encode(['success' => false, 'msg' => 'دانەوەی قەرز نەدۆزرایەوە']);
        exit;
    }
    $company_id = $debt_payment['company_id'];
    $amount_usd = floatval($debt_payment['amount_usd']);
    $amount_iqd = floatval($debt_payment['amount_iqd']);
    // Get company information for notification
    $stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    $company_name = $company['name'] ?? 'Unknown';

    // Create detailed notification
    $old_values = [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'date' => $debt_payment['date'],
        'amount_usd' => $debt_payment['amount_usd'],
        'amount_iqd' => $debt_payment['amount_iqd'],
        'dollar_rate' => $debt_payment['dollar_rate'],
        'note' => $debt_payment['note']
    ];

    $additional_info = [
        'action_type' => 'company_debt_payment_delete',
        'payment_method' => $amount_usd > 0 ? 'USD' : ($amount_iqd > 0 ? 'IQD' : 'none'),
        'total_amount' => $amount_usd + $amount_iqd
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'delete',
        'debt_payments',
        $id,
        "پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: $company_name)",
        $old_values,
        null, // No new values for delete
        $additional_info,
        getUserIP()
    );

    // Delete the debt payment record
    $del = $pdo->prepare('DELETE FROM debt_payments WHERE id = ?');
    $ok = $del->execute([$id]);
    if (!$ok) {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە']);
        exit;
    }

    // Restore the amounts that were paid
    if ($amount_usd > 0) {
        $remaining = $amount_usd;
        
        // 1. First restore to purchases.remaining_usd (FIFO)
        $purchases = $pdo->prepare('SELECT id, remaining_usd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" ORDER BY date ASC, id ASC');
        $purchases->execute([$company_id, 'دۆلار']);
        foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($remaining <= 0) break;
            $toRestore = min($row['remaining_usd'], $remaining);
            $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd + ? WHERE id = ?')->execute([$toRestore, $row['id']]);
            $remaining -= $toRestore;
        }
        
        // 2. Then restore opening_debt_usd
        if ($remaining > 0) {
            $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')->execute([$remaining, $company_id]);
        }
    }
    
    if ($amount_iqd > 0) {
        $remaining = $amount_iqd;
        
        // 1. First restore to purchases.remaining_iqd (FIFO)
        $purchases = $pdo->prepare('SELECT id, remaining_iqd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" ORDER BY date ASC, id ASC');
        $purchases->execute([$company_id, 'دینار']);
        foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($remaining <= 0) break;
            $toRestore = min($row['remaining_iqd'], $remaining);
            $pdo->prepare('UPDATE purchases SET remaining_iqd = remaining_iqd + ? WHERE id = ?')->execute([$toRestore, $row['id']]);
            $remaining -= $toRestore;
        }
        
        // 2. Then restore opening_debt_iqd
        if ($remaining > 0) {
            $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd + ? WHERE id = ?')->execute([$remaining, $company_id]);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
