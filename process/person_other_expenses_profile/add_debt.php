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
$note = trim($_POST['note'] ?? '');

if (
    !$person_id ||
    ($amount_usd <= 0 && $amount_iqd <= 0 && $discount_usd <= 0 && $discount_iqd <= 0)
) {
    echo json_encode(['success' => false, 'msg' => 'زانیاری پێویست بە شێوەیەکی دروست داخڵ بکە!']);
    exit;
}

try {
    $snapshot = getPersonDebtSnapshot($pdo, $person_id);
    $total_usd_available = $snapshot['total_debt_usd'];
    $total_iqd_available = $snapshot['total_debt_iqd'];
    $tolerance = 0.0001;

    if (($amount_usd + $discount_usd) - $total_usd_available > $tolerance) {
        echo json_encode(['success' => false, 'msg' => 'بڕی پارەی دۆلار زیاترە لە قەرزی ماوە!']);
        exit;
    }

    if (($amount_iqd + $discount_iqd) - $total_iqd_available > $tolerance) {
        echo json_encode(['success' => false, 'msg' => 'بڕی پارەی دینار زیاترە لە قەرزی ماوە!']);
        exit;
    }

    $pdo->beginTransaction();

    $insert = $pdo->prepare("
        INSERT INTO person_other_expenses_debt_payments
        (person_id, date, amount_usd, amount_iqd, discount_usd, discount_iqd, note)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->execute([
        $person_id,
        $date,
        $amount_usd,
        $amount_iqd,
        $discount_usd,
        $discount_iqd,
        $note
    ]);

    $debt_payment_id = (int)$pdo->lastInsertId();

    applyPersonCurrencyReduction($pdo, $person_id, 'usd', $amount_usd);
    applyPersonCurrencyReduction($pdo, $person_id, 'usd', $discount_usd);
    applyPersonCurrencyReduction($pdo, $person_id, 'iqd', $amount_iqd);
    applyPersonCurrencyReduction($pdo, $person_id, 'iqd', $discount_iqd);

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

