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
    $discount_usd = floatval($debt['discount_usd'] ?? 0);

    // Get company information for notification
    $stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    $company_name = $company['name'] ?? 'Unknown';

    // Create old values for notification
    $old_values = [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'amount_usd' => $amount_usd,
        'amount_iqd' => $amount_iqd,
        'date' => $debt['date'],
        'note' => $debt['note'] ?? ''
    ];

    $additional_info = [
        'action_type' => 'company_debt_payment_deletion',
        'payment_method' => $amount_usd > 0 ? 'USD' : ($amount_iqd > 0 ? 'IQD' : 'none'),
        'total_amount' => $amount_usd + $amount_iqd,
        'discount_usd' => $discount_usd
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
        "پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: $company_name)",
        $old_values,
        null, // No new values for delete
        $additional_info,
        getUserIP()
    );
    
    // Reverse the payment (LIFO - restore to purchases first, then opening debt)
    if ($amount_usd > 0) {
        $remaining_to_restore = $amount_usd;
        // First restore to purchases (LIFO - newest first)
        $purchases = $pdo->prepare('SELECT id, remaining_usd, price FROM purchases WHERE company_id = ? AND payment_type = "قەرز" AND type = "دۆلار" ORDER BY date DESC, id DESC');
        $purchases->execute([$company_id]);
        foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($remaining_to_restore <= 0) break;
            $max_restore = $row['price'] - $row['remaining_usd'];
            if ($max_restore <= 0) continue;
            $toRestore = min($max_restore, $remaining_to_restore);
            $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd + ? WHERE id = ?')->execute([$toRestore, $row['id']]);
            $remaining_to_restore -= $toRestore;
        }
        // Then restore to opening debt
        if ($remaining_to_restore > 0) {
            $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')->execute([$remaining_to_restore, $company_id]);
        }
    }
    
    if ($amount_iqd > 0) {
        $remaining_to_restore = $amount_iqd;
        // First restore to purchases (LIFO - newest first)
        $purchases = $pdo->prepare('SELECT id, remaining_iqd, amount_iqd FROM purchases WHERE company_id = ? AND payment_type = "قەرز" AND type = "دینار" ORDER BY date DESC, id DESC');
        $purchases->execute([$company_id]);
        foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($remaining_to_restore <= 0) break;
            $max_restore = $row['amount_iqd'] - $row['remaining_iqd'];
            if ($max_restore <= 0) continue;
            $toRestore = min($max_restore, $remaining_to_restore);
            $pdo->prepare('UPDATE purchases SET remaining_iqd = remaining_iqd + ? WHERE id = ?')->execute([$toRestore, $row['id']]);
            $remaining_to_restore -= $toRestore;
        }
        // Then restore to opening debt
        if ($remaining_to_restore > 0) {
            $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd + ? WHERE id = ?')->execute([$remaining_to_restore, $company_id]);
        }
    }
    
    // Reverse USD discount: restore debt (LIFO purchases, then opening debt)
    if ($discount_usd > 0) {
        $to_restore = $discount_usd;
        // Restore to purchases first (LIFO - newest first)
        $purchases = $pdo->prepare('SELECT id, remaining_usd, price FROM purchases WHERE company_id = ? AND payment_type = "قەرز" AND type = "دۆلار" ORDER BY date DESC, id DESC');
        $purchases->execute([$company_id]);
        foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($to_restore <= 0) break;
            $max_restore = $row['price'] - $row['remaining_usd'];
            if ($max_restore <= 0) continue;
            $toRestore = min($max_restore, $to_restore);
            $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd + ? WHERE id = ?')->execute([$toRestore, $row['id']]);
            $to_restore -= $toRestore;
        }
        // Then restore to opening debt
        if ($to_restore > 0) {
            $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')->execute([$to_restore, $company_id]);
        }
    }
    
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
