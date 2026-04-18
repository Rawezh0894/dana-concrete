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

if (!hasPermission('delete_debt')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) {
        echo json_encode(['success' => false, 'msg' => 'ناسنامە پێویستە']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM customer_account_adjustments WHERE id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'ڕیکۆرد نەدۆزرایەوە']);
        exit;
    }

    $customer_id = intval($row['customer_id']);
    $stmt = $pdo->prepare('SELECT opening_debt_usd, name FROM customers WHERE id = ? FOR UPDATE');
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'کڕیار نەدۆزرایەوە']);
        exit;
    }

    $current_opening = floatval($customer['opening_debt_usd'] ?? 0);
    $signed_amount = ($row['adjustment_type'] === 'increase' ? 1 : -1) * floatval($row['amount_usd']);
    $new_opening = $current_opening - $signed_amount;

    $upd = $pdo->prepare('UPDATE customers SET opening_debt_usd = ? WHERE id = ?');
    $upd->execute([$new_opening, $customer_id]);

    $del = $pdo->prepare('DELETE FROM customer_account_adjustments WHERE id = ?');
    $del->execute([$id]);

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'delete',
        'customer_account_adjustments',
        $id,
        "ڕیکۆردی ڕێکخستنەوەی حیساب سڕایەوە (کڕیار: " . ($customer['name'] ?? 'Unknown') . ")",
        [
            'date' => $row['date'],
            'adjustment_type' => $row['adjustment_type'],
            'amount_usd' => $row['amount_usd'],
            'reason' => $row['reason']
        ],
        null,
        ['action_type' => 'customer_account_adjustment_delete'],
        getUserIP()
    );

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'ڕیکۆردەکە سڕایەوە']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('delete_account_adjustment.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵە: ' . $e->getMessage()]);
}

