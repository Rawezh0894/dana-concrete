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
$change_back_usd = max(0, floatval($_POST['change_back_usd'] ?? 0));
$change_back_iqd = max(0, floatval($_POST['change_back_iqd'] ?? 0));
$dollar_rate = floatval($_POST['exchange_rate'] ?? 150000);
if ($dollar_rate <= 0) $dollar_rate = 150000;
$note = trim($_POST['note'] ?? '');

if (
    !$id ||
    !$person_id ||
    ($amount_usd <= 0 && $amount_iqd <= 0 && $discount_usd <= 0 && $discount_iqd <= 0 && $change_back_usd <= 0 && $change_back_iqd <= 0)
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

    // 1. Revert OLD effect
    $old_dollar_rate = floatval($payment['dollar_rate'] ?? 0);
    $old_deducted_usd = floatval($payment['deducted_usd'] ?? 0);
    $old_deducted_iqd = floatval($payment['deducted_iqd'] ?? 0);

    if ($old_deducted_usd != 0 || $old_deducted_iqd != 0) {
        if ($old_deducted_usd > 0) {
            restorePersonCurrencyAmount($pdo, $person_id, 'usd', $old_deducted_usd, $old_dollar_rate);
        } elseif ($old_deducted_usd < 0) {
            applyPersonCurrencyReduction($pdo, $person_id, 'usd', abs($old_deducted_usd), $old_dollar_rate);
        }

        if ($old_deducted_iqd > 0) {
            restorePersonCurrencyAmount($pdo, $person_id, 'iqd', $old_deducted_iqd, $old_dollar_rate);
        } elseif ($old_deducted_iqd < 0) {
            applyPersonCurrencyReduction($pdo, $person_id, 'iqd', abs($old_deducted_iqd), $old_dollar_rate);
        }
    } else {
        // Fallback for old records
        $old_amount_usd = floatval($payment['amount_usd'] ?? 0);
        $old_discount_usd = floatval($payment['discount_usd'] ?? 0);
        $old_change_back_usd = floatval($payment['change_back_usd'] ?? 0);
        $old_amount_iqd = floatval($payment['amount_iqd'] ?? 0);
        $old_discount_iqd = floatval($payment['discount_iqd'] ?? 0);
        $old_change_back_iqd = floatval($payment['change_back_iqd'] ?? 0);

        restorePersonCurrencyAmount($pdo, $person_id, 'usd', $old_amount_usd + $old_discount_usd, $old_dollar_rate);
        restorePersonCurrencyAmount($pdo, $person_id, 'iqd', $old_amount_iqd + $old_discount_iqd, $old_dollar_rate);

        if ($old_change_back_usd > 0) {
            applyPersonCurrencyReduction($pdo, $person_id, 'usd', $old_change_back_usd, $old_dollar_rate);
        }
        if ($old_change_back_iqd > 0) {
            applyPersonCurrencyReduction($pdo, $person_id, 'iqd', $old_change_back_iqd, $old_dollar_rate);
        }
    }

    // 2. Apply NEW effect
    $snapshotBefore = getPersonDebtSnapshot($pdo, $person_id);

    // Apply reductions for payment and discount
    applyPersonCurrencyReduction($pdo, $person_id, 'usd', $amount_usd + $discount_usd, $dollar_rate);
    applyPersonCurrencyReduction($pdo, $person_id, 'iqd', $amount_iqd + $discount_iqd, $dollar_rate);

    // Apply restoration for change back
    if ($change_back_usd > 0) {
        restorePersonCurrencyAmount($pdo, $person_id, 'usd', $change_back_usd, $dollar_rate);
    }
    if ($change_back_iqd > 0) {
        restorePersonCurrencyAmount($pdo, $person_id, 'iqd', $change_back_iqd, $dollar_rate);
    }

    $snapshotAfter = getPersonDebtSnapshot($pdo, $person_id);
    $deducted_usd = $snapshotBefore['total_debt_usd'] - $snapshotAfter['total_debt_usd'];
    $deducted_iqd = $snapshotBefore['total_debt_iqd'] - $snapshotAfter['total_debt_iqd'];

    // 3. Update the record
    $updateStmt = $pdo->prepare('
        UPDATE person_other_expenses_debt_payments
        SET date = ?, amount_usd = ?, amount_iqd = ?, discount_usd = ?, discount_iqd = ?, change_back_usd = ?, change_back_iqd = ?, dollar_rate = ?, note = ?, deducted_usd = ?, deducted_iqd = ?
        WHERE id = ?
    ');
    $updateStmt->execute([
        $date,
        $amount_usd,
        $amount_iqd,
        $discount_usd,
        $discount_iqd,
        $change_back_usd,
        $change_back_iqd,
        $dollar_rate,
        $note,
        $deducted_usd,
        $deducted_iqd,
        $id
    ]);

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

