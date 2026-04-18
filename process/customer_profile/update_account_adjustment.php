<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە!']);
    exit;
}

if (!hasPermission('update_debt')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $date = $_POST['date'] ?? date('Y-m-d');
    $adjustment_type = $_POST['adjustment_type'] ?? '';
    $amount_usd = floatval($_POST['amount_usd'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if (!$id || !$date || !in_array($adjustment_type, ['increase', 'decrease'], true) || $amount_usd <= 0 || $reason === '') {
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان بە دروستی پڕ بکە']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM customer_account_adjustments WHERE id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$old) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'ڕیکۆرد نەدۆزرایەوە']);
        exit;
    }

    $customer_id = intval($old['customer_id']);
    $stmt = $pdo->prepare('SELECT opening_debt_usd, name, mobile1 FROM customers WHERE id = ? FOR UPDATE');
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'کڕیار نەدۆزرایەوە']);
        exit;
    }

    $current_opening = floatval($customer['opening_debt_usd'] ?? 0);
    $old_signed = ($old['adjustment_type'] === 'increase' ? 1 : -1) * floatval($old['amount_usd']);
    $new_signed = ($adjustment_type === 'increase' ? 1 : -1) * $amount_usd;
    $delta = $new_signed - $old_signed;
    $new_opening = $current_opening + $delta;

    $updCustomer = $pdo->prepare('UPDATE customers SET opening_debt_usd = ? WHERE id = ?');
    $updCustomer->execute([$new_opening, $customer_id]);

    $updAdj = $pdo->prepare('UPDATE customer_account_adjustments SET date = ?, adjustment_type = ?, amount_usd = ?, reason = ? WHERE id = ?');
    $updAdj->execute([$date, $adjustment_type, $amount_usd, $reason, $id]);

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'update',
        'customer_account_adjustments',
        $id,
        "ڕێکخستنەوەی حیسابی کڕیار نوێکرایەوە (کڕیار: " . ($customer['name'] ?? 'Unknown') . ")",
        [
            'date' => $old['date'],
            'adjustment_type' => $old['adjustment_type'],
            'amount_usd' => $old['amount_usd'],
            'reason' => $old['reason'],
            'opening_debt_usd' => $current_opening - $delta
        ],
        [
            'date' => $date,
            'adjustment_type' => $adjustment_type,
            'amount_usd' => $amount_usd,
            'reason' => $reason,
            'opening_debt_usd' => $new_opening
        ],
        ['action_type' => 'customer_account_adjustment_update'],
        getUserIP()
    );

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'ڕێکخستنەوەکە نوێکرایەوە']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('update_account_adjustment.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵە: ' . $e->getMessage()]);
}

