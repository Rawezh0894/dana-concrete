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

if (!$id) {
    echo json_encode(['success' => false, 'msg' => 'ناسنامەی دانەوە پێویستە!']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM person_other_expenses_debt_payments WHERE id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new RuntimeException('دانەوەی قەرز نەدۆزرایەوە');
    }

    $person_id = intval($payment['person_id']);

    $dollar_rate = floatval($payment['dollar_rate'] ?? 0);
    $amount_usd = floatval($payment['amount_usd'] ?? 0);
    $discount_usd = floatval($payment['discount_usd'] ?? 0);
    $change_back_usd = floatval($payment['change_back_usd'] ?? 0);

    $amount_iqd = floatval($payment['amount_iqd'] ?? 0);
    $discount_iqd = floatval($payment['discount_iqd'] ?? 0);
    $change_back_iqd = floatval($payment['change_back_iqd'] ?? 0);

    // 1. Restore what was paid/discounted (increases debt)
    restorePersonCurrencyAmount($pdo, $person_id, 'usd', $amount_usd + $discount_usd, $dollar_rate);
    restorePersonCurrencyAmount($pdo, $person_id, 'iqd', $amount_iqd + $discount_iqd, $dollar_rate);

    // 2. Remove what was given back as change (decreases debt)
    if ($change_back_usd > 0) {
        applyPersonCurrencyReduction($pdo, $person_id, 'usd', $change_back_usd, $dollar_rate);
    }
    if ($change_back_iqd > 0) {
        applyPersonCurrencyReduction($pdo, $person_id, 'iqd', $change_back_iqd, $dollar_rate);
    }

    $deleteStmt = $pdo->prepare('DELETE FROM person_other_expenses_debt_payments WHERE id = ?');
    $deleteStmt->execute([$id]);

    require_once __DIR__ . '/../../includes/notify.php';
    notify(
        'delete',
        'person_other_expenses_debt_payments',
        $id,
        'پارەدانی قەرزی کەسانی تر سڕایەوە (کەس: ' . $person_id . ')'
    );

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'دانەوەی قەرز بەسەرکەوتوویی سڕایەوە!']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('person delete_debt error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()]);
}

