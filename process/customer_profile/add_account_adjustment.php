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

if (!hasPermission('update_debt')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $date = $_POST['date'] ?? date('Y-m-d');
    $adjustment_type = $_POST['adjustment_type'] ?? '';
    $amount_usd = floatval($_POST['amount_usd'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if (!$customer_id || !$date || !in_array($adjustment_type, ['increase', 'decrease'], true) || $amount_usd <= 0 || $reason === '') {
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان بە دروستی پڕ بکە']);
        exit;
    }

    $pdo->beginTransaction();

    // Ensure history table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customer_account_adjustments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            date DATE NOT NULL,
            adjustment_type ENUM('increase','decrease') NOT NULL,
            amount_usd DECIMAL(18,4) NOT NULL DEFAULT 0,
            reason TEXT NOT NULL,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer_date (customer_id, date),
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $stmt = $pdo->prepare('SELECT opening_debt_usd, name, mobile1 FROM customers WHERE id = ? FOR UPDATE');
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'کڕیار نەدۆزرایەوە']);
        exit;
    }

    $current_opening = floatval($customer['opening_debt_usd'] ?? 0);
    $signed_amount = $adjustment_type === 'increase' ? $amount_usd : (-1 * $amount_usd);
    $new_opening = $current_opening + $signed_amount;

    $upd = $pdo->prepare('UPDATE customers SET opening_debt_usd = ? WHERE id = ?');
    $upd->execute([$new_opening, $customer_id]);

    $ins = $pdo->prepare('INSERT INTO customer_account_adjustments (customer_id, date, adjustment_type, amount_usd, reason, created_by) VALUES (?, ?, ?, ?, ?, ?)');
    $ins->execute([$customer_id, $date, $adjustment_type, $amount_usd, $reason, $_SESSION['user_id']]);
    $adjustment_id = $pdo->lastInsertId();

    $old_values = [
        'opening_debt_usd' => $current_opening
    ];
    $new_values = [
        'opening_debt_usd' => $new_opening,
        'adjustment_type' => $adjustment_type,
        'amount_usd' => $amount_usd,
        'reason' => $reason,
        'customer_name' => $customer['name'] ?? 'Unknown',
        'customer_phone' => $customer['mobile1'] ?? 'N/A'
    ];

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'insert',
        'customer_account_adjustments',
        $adjustment_id,
        "ڕێکخستنەوەی حیسابی کڕیار تۆمارکرا (کڕیار: " . ($customer['name'] ?? 'Unknown') . ")",
        $old_values,
        $new_values,
        ['action_type' => 'customer_account_adjustment'],
        getUserIP()
    );

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'ڕێکخستنەوەکە بە سەرکەوتوویی تۆمارکرا']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('add_account_adjustment.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕوویدا']);
}

