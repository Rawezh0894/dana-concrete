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
    !$person_id ||
    ($amount_usd <= 0 && $amount_iqd <= 0 && $discount_usd <= 0 && $discount_iqd <= 0 && $change_back_usd <= 0 && $change_back_iqd <= 0)
) {
    echo json_encode(['success' => false, 'msg' => 'زانیاری پێویست بە شێوەیەکی دروست داخڵ بکە!']);
    exit;
}

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare("
        INSERT INTO person_other_expenses_debt_payments
        (person_id, date, amount_usd, amount_iqd, discount_usd, discount_iqd, change_back_usd, change_back_iqd, dollar_rate, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->execute([
        $person_id,
        $date,
        $amount_usd,
        $amount_iqd,
        $discount_usd,
        $discount_iqd,
        $change_back_usd,
        $change_back_iqd,
        $dollar_rate,
        $note
    ]);

    $debt_payment_id = (int)$pdo->lastInsertId();

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

    require_once __DIR__ . '/../../includes/notify.php';
    notify(
        'insert',
        'person_other_expenses_debt_payments',
        $debt_payment_id,
        'پارەدان بۆ قەرزی کەسانی تر زیادکرا (کەس: ' . $person_id . ')'
    );

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'دانەوەی قەرز بەسەرکەوتوویی تۆمارکرا!']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('person add_debt error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()]);
}

