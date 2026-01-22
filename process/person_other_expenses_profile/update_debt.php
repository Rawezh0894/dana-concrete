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

    // Fetch exchange rate
    $stmt_rate = $pdo->prepare("SELECT value FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1");
    $stmt_rate->execute();
    $usd_iqd_rate = floatval($stmt_rate->fetchColumn() ?: 150000);
    $rate_per_dollar = $usd_iqd_rate / 100;

    // Total debt in USD terms for validation
    $combined_debt_usd = $total_usd_available + ($total_iqd_available / $rate_per_dollar);
    
    $paid_usd_total = $amount_usd + $discount_usd;
    $paid_iqd_total = $amount_iqd + $discount_iqd;
    $combined_paid_usd = $paid_usd_total + ($paid_iqd_total / $rate_per_dollar);

    if ($combined_paid_usd - $combined_debt_usd > $tolerance) {
        throw new RuntimeException('بڕی پارەی دیاریکراو زیاترە لە کۆی گشتی قەرزەکان!');
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

    $remaining_usd_to_apply = $amount_usd + $discount_usd;
    $remaining_iqd_to_apply = $amount_iqd + $discount_iqd;

    // First apply USD payments to USD debt
    $usd_to_deduct = min($total_usd_available, $remaining_usd_to_apply);
    applyPersonCurrencyReduction($pdo, $person_id, 'usd', $usd_to_deduct);
    $remaining_usd_to_apply -= $usd_to_deduct;

    // Then apply IQD payments to IQD debt
    $iqd_to_deduct = min($total_iqd_available, $remaining_iqd_to_apply);
    applyPersonCurrencyReduction($pdo, $person_id, 'iqd', $iqd_to_deduct);
    $remaining_iqd_to_apply -= $iqd_to_deduct;

    // Handle Surplus USD (apply to IQD debt)
    if ($remaining_usd_to_apply > 0) {
        $surplus_as_iqd = $remaining_usd_to_apply * $rate_per_dollar;
        applyPersonCurrencyReduction($pdo, $person_id, 'iqd', $surplus_as_iqd);
    }

    // Handle Surplus IQD (apply to USD debt)
    if ($remaining_iqd_to_apply > 0) {
        $surplus_as_usd = $remaining_iqd_to_apply / $rate_per_dollar;
        applyPersonCurrencyReduction($pdo, $person_id, 'usd', $surplus_as_usd);
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

