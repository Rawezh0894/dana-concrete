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

if (!hasPermission('view_person_other_expenses_profile')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراوە!']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$person_id = isset($_POST['person_id']) ? intval($_POST['person_id']) : 0;
$date = $_POST['date'] ?? date('Y-m-d');
$amount_usd = max(0, floatval($_POST['amount_usd'] ?? 0));
$amount_iqd = max(0, floatval($_POST['amount_iqd'] ?? 0));
$discount_usd = max(0, floatval($_POST['discount_usd'] ?? 0));
$discount_iqd = max(0, floatval($_POST['discount_iqd'] ?? 0));
$note = trim($_POST['note'] ?? '');

if (
    !$id ||
    !$person_id ||
    ($amount_usd <= 0 && $amount_iqd <= 0 && $discount_usd <= 0 && $discount_iqd <= 0)
) {
    echo json_encode(['success' => false, 'msg' => 'زانیاری پێویست بە شێوەیەکی دروست داخڵ بکە!']);
    exit;
}

try {
    $pdo->beginTransaction();

    $paymentStmt = $pdo->prepare('SELECT * FROM person_other_expenses_debt_payments WHERE id = ? FOR UPDATE');
    $paymentStmt->execute([$id]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment || intval($payment['person_id']) !== $person_id) {
        throw new RuntimeException('دانەوەی قەرز نەدۆزرایەوە!');
    }

    restorePersonCurrencyAmount($pdo, $person_id, 'usd', floatval($payment['amount_usd'] ?? 0));
    restorePersonCurrencyAmount($pdo, $person_id, 'usd', floatval($payment['discount_usd'] ?? 0));
    restorePersonCurrencyAmount($pdo, $person_id, 'iqd', floatval($payment['amount_iqd'] ?? 0));
    restorePersonCurrencyAmount($pdo, $person_id, 'iqd', floatval($payment['discount_iqd'] ?? 0));

    $snapshot = getPersonDebtSnapshot($pdo, $person_id);
    $total_usd_available = $snapshot['total_debt_usd'];
    $total_iqd_available = $snapshot['total_debt_iqd'];
    
    $rate = floatval($_POST['exchange_rate'] ?? 150000);
    if ($rate <= 0) $rate = 150000;
    $rate_per_usd = $rate / 100;

    $payment_usd = $amount_usd + $discount_usd;
    $payment_iqd = $amount_iqd + $discount_iqd;

    // Calculate excesses
    $excess_usd = max(0, $payment_usd - $total_usd_available);
    $excess_iqd = max(0, $payment_iqd - $total_iqd_available);

    // Calculate cross-currency values
    $cross_usd_to_iqd = $excess_usd * $rate_per_usd;
    $cross_iqd_to_usd = $excess_iqd / $rate_per_usd;

    // Calculate final effective reductions
    $final_usd_reduction = min($payment_usd, $total_usd_available) + $cross_iqd_to_usd;
    $final_iqd_reduction = min($payment_iqd, $total_iqd_available) + $cross_usd_to_iqd;

    // Validate Total Value
    $total_payment_value_usd = $payment_usd + ($payment_iqd / $rate_per_usd);
    $total_debt_value_usd = $total_usd_available + ($total_iqd_available / $rate_per_usd);

    if ($total_payment_value_usd - $total_debt_value_usd > 1.0) { // Allow $1 tolerance
        throw new RuntimeException('بڕی پارەی دراو زیاترە لە کۆی گشتی قەرز! (لە کاتی نوێکردنەوە)');
    }

    // Check if discount columns exist
    $checkDiscount = $pdo->query("SHOW COLUMNS FROM `person_other_expenses_debt_payments` LIKE 'discount_usd'");
    $hasDiscount = $checkDiscount->rowCount() > 0;
    
    if ($hasDiscount) {
        $updateStmt = $pdo->prepare('
            UPDATE person_other_expenses_debt_payments
            SET date = ?, amount_usd = ?, amount_iqd = ?, discount_usd = ?, discount_iqd = ?, note = ?
            WHERE id = ?
        ');
        $updateStmt->execute([
            $date,
            $amount_usd,
            $amount_iqd,
            $discount_usd,
            $discount_iqd,
            $note,
            $id
        ]);
    } else {
        $updateStmt = $pdo->prepare('
            UPDATE person_other_expenses_debt_payments
            SET date = ?, amount_usd = ?, amount_iqd = ?, note = ?
            WHERE id = ?
        ');
        $updateStmt->execute([
            $date,
            $amount_usd,
            $amount_iqd,
            $note,
            $id
        ]);
    }

    // Apply reductions using calculated effective amounts
    if ($final_usd_reduction > 0) {
        applyPersonCurrencyReduction($pdo, $person_id, 'usd', $final_usd_reduction);
    }
    if ($final_iqd_reduction > 0) {
        applyPersonCurrencyReduction($pdo, $person_id, 'iqd', $final_iqd_reduction);
    }

    require_once __DIR__ . '/../../includes/notify.php';
    notify(
        'update',
        'person_other_expenses_debt_payments',
        $id,
        'پارەدانی قەرزی کەسانی تر نوێکرایەوە (کەس: ' . $person_id . ')'
    );

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'قەرز بەسەرکەوتوویی نوێکرایەوە!']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('person update_debt error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()]);
}

