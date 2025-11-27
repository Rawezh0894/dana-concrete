<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/debt_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('add_debt')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ دانەوەی قەرز']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
    exit;
}

$company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
$date = $_POST['date'] ?? null;
$amount_usd = max(0, floatval($_POST['amount_usd'] ?? 0));
$amount_iqd = max(0, floatval($_POST['amount_iqd'] ?? 0));
$discount_usd = max(0, floatval($_POST['discount_usd'] ?? 0));
$discount_iqd = max(0, floatval($_POST['discount_iqd'] ?? 0));
$dollar_rate = floatval($_POST['dollar_rate'] ?? 0);
$note = trim($_POST['note'] ?? '');
$user_id = $_SESSION['user_id'];

if (
    !$company_id ||
    !$date ||
    ($amount_usd <= 0 && $amount_iqd <= 0 && $discount_usd <= 0 && $discount_iqd <= 0)
) {
    echo json_encode(['success' => false, 'msg' => 'بە لایەنی کەم یەک بڕ پڕبکە (پارە یان داشکاندن)']);
    exit;
}

$companyStmt = $pdo->prepare('SELECT name, currency_type FROM company WHERE id = ?');
$companyStmt->execute([$company_id]);
$company = $companyStmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    echo json_encode(['success' => false, 'msg' => 'کۆمپانیا نەدۆزرایەوە!']);
    exit;
}

$company_name = $company['name'] ?? 'Unknown';
$currency_type = $company['currency_type'] ?? null;

try {
    $snapshot = getCompanyDebtSnapshot($pdo, $company_id);
    $total_usd_available = $snapshot['opening_debt_usd'] + $snapshot['remaining_usd'];
    $total_iqd_available = $snapshot['opening_debt_iqd'] + $snapshot['remaining_iqd'];
    $tolerance = 0.0001;

    if (($amount_usd + $discount_usd) - $total_usd_available > $tolerance) {
        echo json_encode(['success' => false, 'msg' => 'بڕی پارەی دۆلاری داوە زیاترە لە قەرزی کۆمپانیا!']);
        exit;
    }

    if (($amount_iqd + $discount_iqd) - $total_iqd_available > $tolerance) {
        echo json_encode(['success' => false, 'msg' => 'بڕی پارەی دیناری داوە زیاترە لە قەرزی کۆمپانیا!']);
        exit;
    }

    $pdo->beginTransaction();

    $insert = $pdo->prepare('
        INSERT INTO debt_payments (company_id, date, amount_usd, amount_iqd, discount_usd, discount_iqd, dollar_rate, note, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $ok = $insert->execute([
        $company_id,
        $date,
        $amount_usd,
        $amount_iqd,
        $discount_usd,
        $discount_iqd,
        $dollar_rate,
        $note,
        $user_id
    ]);

    if (!$ok) {
        throw new RuntimeException('هەڵە لە تۆمارکردنی دانەوەی قەرز');
    }

    $debt_payment_id = (int)$pdo->lastInsertId();

    applyCompanyCurrencyReduction($pdo, $company_id, 'usd', $amount_usd);
    applyCompanyCurrencyReduction($pdo, $company_id, 'usd', $discount_usd);
    applyCompanyCurrencyReduction($pdo, $company_id, 'iqd', $amount_iqd);
    applyCompanyCurrencyReduction($pdo, $company_id, 'iqd', $discount_iqd);

    $new_values = [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'date' => $date,
        'amount_usd' => $amount_usd,
        'amount_iqd' => $amount_iqd,
        'discount_usd' => $discount_usd,
        'discount_iqd' => $discount_iqd,
        'dollar_rate' => $dollar_rate,
        'note' => $note,
        'created_by' => $user_id
    ];

    $additional_info = [
        'action_type' => 'company_debt_payment',
        'payment_method' => $amount_usd > 0 ? 'USD' : ($amount_iqd > 0 ? 'IQD' : 'none'),
        'total_amount' => $amount_usd + $amount_iqd,
        'discount_usd' => $discount_usd,
        'discount_iqd' => $discount_iqd
    ];

    createDetailedNotification(
        $pdo,
        $user_id,
        'insert',
        'debt_payments',
        $debt_payment_id,
        "پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: $company_name)",
        null,
        $new_values,
        $additional_info,
        getUserIP()
    );

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'دانەوەی قەرز بەسەرکەوتوویی تۆمارکرا!']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('add_debt.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()]);
}

