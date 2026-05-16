<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/cash_box_helpers.php';
header('Content-Type: application/json; charset=utf-8');

cash_box_ensure_no_withdraw_balance_block($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}
if (!isset($_SESSION['user_id']) || !hasPermission('add_cash_box')) {
    echo json_encode(['success' => false, 'error' => 'دەستپێگەیشتن قەدەغەیە']);
    exit;
}

$date       = $_POST['date']       ?? '';
$type       = $_POST['type']       ?? '';
$amount_iqd = (float) ($_POST['amount_iqd'] ?? 0);
$amount_usd = (float) ($_POST['amount_usd'] ?? 0);
$currency   = $_POST['currency']   ?? '';
$note       = isset($_POST['note']) ? trim($_POST['note']) : '';
$created_by = (int) $_SESSION['user_id'];

if (!$date || !$type || !$currency) {
    echo json_encode(['success' => false, 'error' => 'هەموو خانەکان پڕ بکە']);
    exit;
}
$noteLen = function_exists('mb_strlen') ? mb_strlen($note, 'UTF-8') : strlen($note);
if ($noteLen < 10) {
    echo json_encode(['success' => false, 'error' => 'تێبینی پێویستە بە ماناداری بنوسرێت (کەمترین ١٠ پیت)']);
    exit;
}

// Ensure audit log table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS cash_box_audit_log (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        cash_box_id  INT         NOT NULL,
        action       VARCHAR(20) NOT NULL COMMENT 'created, updated, deleted',
        changed_by   INT         NOT NULL,
        changed_at   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
        old_data     TEXT        DEFAULT NULL,
        new_data     TEXT        DEFAULT NULL,
        INDEX idx_cb_audit_cash (cash_box_id),
        INDEX idx_cb_audit_at   (changed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

try {
    $stmt = $pdo->prepare(
        "INSERT INTO cash_box (date, type, amount_iqd, amount_usd, currency, note, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$date, $type, $amount_iqd, $amount_usd, $currency, $note, $created_by]);
    $new_id = (int) $pdo->lastInsertId();

    $new_data = json_encode([
        'date' => $date, 'type' => $type,
        'amount_iqd' => $amount_iqd, 'amount_usd' => $amount_usd,
        'currency' => $currency, 'note' => $note,
    ], JSON_UNESCAPED_UNICODE);

    $audit = $pdo->prepare(
        "INSERT INTO cash_box_audit_log (cash_box_id, action, changed_by, old_data, new_data)
         VALUES (?, 'created', ?, NULL, ?)"
    );
    $audit->execute([$new_id, $created_by, $new_data]);

    echo json_encode(['success' => true]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
