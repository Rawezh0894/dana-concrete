<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/debt_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە!']);
    exit;
}

if (!hasPermission('delete_debt')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ سڕینەوەی قەرز']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'msg' => 'ناسنامەی دانەوە پێویستە!']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM debt_payments WHERE id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'دانەوەی قەرز نەدۆزرایەوە']);
        exit;
    }

    $company_id = intval($payment['company_id']);

    $companyStmt = $pdo->prepare('SELECT name FROM company WHERE id = ?');
    $companyStmt->execute([$company_id]);
    $company = $companyStmt->fetch(PDO::FETCH_ASSOC);
    $company_name = $company['name'] ?? 'Unknown';

    $dollar_rate = floatval($payment['dollar_rate'] ?? 0);
    $net_usd = floatval($payment['amount_usd'] ?? 0) + floatval($payment['discount_usd'] ?? 0) - floatval($payment['change_back_usd'] ?? 0);
    $net_iqd = floatval($payment['amount_iqd'] ?? 0) + floatval($payment['discount_iqd'] ?? 0) - floatval($payment['change_back_iqd'] ?? 0);

    restoreCompanyCurrencyAmount($pdo, $company_id, 'usd', $net_usd, $dollar_rate);
    restoreCompanyCurrencyAmount($pdo, $company_id, 'iqd', $net_iqd, $dollar_rate);

    $delete = $pdo->prepare('DELETE FROM debt_payments WHERE id = ?');
    if (!$delete->execute([$id])) {
        throw new RuntimeException('هەڵە لە سڕینەوەی دانەوەی قەرز');
    }

    $old_values = [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'date' => $payment['date'],
        'dollar_rate' => $payment['dollar_rate'],
        'amount_usd' => $payment['amount_usd'],
        'amount_iqd' => $payment['amount_iqd'],
        'discount_usd' => $payment['discount_usd'],
        'discount_iqd' => $payment['discount_iqd'],
        'note' => $payment['note']
    ];

    $additional_info = [
        'action_type' => 'company_debt_payment_deletion',
        'payment_method' => $payment['amount_usd'] > 0 ? 'USD' : ($payment['amount_iqd'] > 0 ? 'IQD' : 'none'),
        'total_amount' => floatval($payment['amount_usd']) + floatval($payment['amount_iqd'])
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'delete',
        'debt_payments',
        $id,
        "پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: $company_name)",
        $old_values,
        null,
        $additional_info,
        getUserIP()
    );

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'دانەوەی قەرز بەسەرکەوتوویی سڕایەوە!']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('delete_debt.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()]);
}

