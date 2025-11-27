<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/debt_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('update_debt')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
$date = $_POST['date'] ?? null;
$dollar_rate = floatval($_POST['dollar_rate'] ?? 0);
$amount_usd = max(0, floatval($_POST['amount_usd'] ?? 0));
$amount_iqd = max(0, floatval($_POST['amount_iqd'] ?? 0));
$discount_usd = max(0, floatval($_POST['discount_usd'] ?? 0));
$discount_iqd = max(0, floatval($_POST['discount_iqd'] ?? 0));
$note = trim($_POST['note'] ?? '');
$user_id = $_SESSION['user_id'];

if (
    !$id ||
    !$company_id ||
    !$date ||
    ($amount_usd <= 0 && $amount_iqd <= 0 && $discount_usd <= 0 && $discount_iqd <= 0)
) {
    echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان بە دروستی پڕبکە!']);
    exit;
}

try {
    $companyStmt = $pdo->prepare('SELECT name, currency_type FROM company WHERE id = ?');
    $companyStmt->execute([$company_id]);
    $company = $companyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        echo json_encode(['success' => false, 'msg' => 'کۆمپانیا نەدۆزرایەوە!']);
        exit;
    }

    $company_name = $company['name'] ?? 'Unknown';

    $pdo->beginTransaction();

    $paymentStmt = $pdo->prepare('SELECT * FROM debt_payments WHERE id = ? FOR UPDATE');
    $paymentStmt->execute([$id]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment || intval($payment['company_id']) !== $company_id) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'دانەوەی قەرز نەدۆزرایەوە!']);
        exit;
    }

    restoreCompanyCurrencyAmount($pdo, $company_id, 'usd', floatval($payment['amount_usd'] ?? 0));
    restoreCompanyCurrencyAmount($pdo, $company_id, 'usd', floatval($payment['discount_usd'] ?? 0));
    restoreCompanyCurrencyAmount($pdo, $company_id, 'iqd', floatval($payment['amount_iqd'] ?? 0));
    restoreCompanyCurrencyAmount($pdo, $company_id, 'iqd', floatval($payment['discount_iqd'] ?? 0));

    $snapshot = getCompanyDebtSnapshot($pdo, $company_id);
    $total_usd_available = $snapshot['opening_debt_usd'] + $snapshot['remaining_usd'];
    $total_iqd_available = $snapshot['opening_debt_iqd'] + $snapshot['remaining_iqd'];
    $tolerance = 0.0001;

    if (($amount_usd + $discount_usd) - $total_usd_available > $tolerance) {
        throw new RuntimeException('بڕی پارەی دۆلاری داوە زیاترە لە قەرزی ماوە!');
    }

    if (($amount_iqd + $discount_iqd) - $total_iqd_available > $tolerance) {
        throw new RuntimeException('بڕی پارەی دیناری داوە زیاترە لە قەرزی ماوە!');
    }

    $updateStmt = $pdo->prepare('
        UPDATE debt_payments
        SET date = ?, dollar_rate = ?, amount_usd = ?, amount_iqd = ?, discount_usd = ?, discount_iqd = ?, note = ?, updated_by = ?
        WHERE id = ?
    ');

    $updated = $updateStmt->execute([
        $date,
        $dollar_rate,
        $amount_usd,
        $amount_iqd,
        $discount_usd,
        $discount_iqd,
        $note,
        $user_id,
        $id
    ]);

    if (!$updated) {
        throw new RuntimeException('هەڵە لە نوێکردنەوەی دانەوەی قەرز');
    }

    applyCompanyCurrencyReduction($pdo, $company_id, 'usd', $amount_usd);
    applyCompanyCurrencyReduction($pdo, $company_id, 'usd', $discount_usd);
    applyCompanyCurrencyReduction($pdo, $company_id, 'iqd', $amount_iqd);
    applyCompanyCurrencyReduction($pdo, $company_id, 'iqd', $discount_iqd);

    $old_values = [
        'company_id' => $payment['company_id'],
        'date' => $payment['date'],
        'dollar_rate' => $payment['dollar_rate'],
        'amount_usd' => $payment['amount_usd'],
        'amount_iqd' => $payment['amount_iqd'],
        'discount_usd' => $payment['discount_usd'],
        'discount_iqd' => $payment['discount_iqd'],
        'note' => $payment['note']
    ];

    $new_values = [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'date' => $date,
        'dollar_rate' => $dollar_rate,
        'amount_usd' => $amount_usd,
        'amount_iqd' => $amount_iqd,
        'discount_usd' => $discount_usd,
        'discount_iqd' => $discount_iqd,
        'note' => $note
    ];

    $additional_info = [
        'action_type' => 'company_debt_payment_update',
        'payment_method' => $amount_usd > 0 ? 'USD' : ($amount_iqd > 0 ? 'IQD' : 'none'),
        'total_amount' => $amount_usd + $amount_iqd
    ];

    createDetailedNotification(
        $pdo,
        $user_id,
        'update',
        'debt_payments',
        $id,
        "پارەدانی قەرزی کۆمپانیا نوێکرایەوە (کۆمپانیا: $company_name)",
        $old_values,
        $new_values,
        $additional_info,
        getUserIP()
    );

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'قەرز بەسەرکەوتوویی نوێکرایەوە!']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('update_debt.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()]);
}

