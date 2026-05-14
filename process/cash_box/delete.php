<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id         = $_POST['id'] ?? null;
$changed_by = (int) $_SESSION['user_id'];

if (!$id || !is_numeric($id)) {
    echo json_encode(['success' => false, 'error' => 'ID نادروستە']);
    exit;
}

// Ensure audit log table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS cash_box_audit_log (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        cash_box_id  INT         NOT NULL,
        action       VARCHAR(20) NOT NULL,
        changed_by   INT         NOT NULL,
        changed_at   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
        old_data     TEXT        DEFAULT NULL,
        new_data     TEXT        DEFAULT NULL,
        INDEX idx_cb_audit_cash (cash_box_id),
        INDEX idx_cb_audit_at   (changed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

try {
    // Snapshot the row before deletion
    $snap_stmt = $pdo->prepare("SELECT date, type, amount_iqd, amount_usd, currency, note FROM cash_box WHERE id = ?");
    $snap_stmt->execute([$id]);
    $snap     = $snap_stmt->fetch(PDO::FETCH_ASSOC);
    $old_data = $snap ? json_encode($snap, JSON_UNESCAPED_UNICODE) : null;

    $stmt = $pdo->prepare('DELETE FROM cash_box WHERE id = ?');
    $stmt->execute([$id]);

    // Log deletion even after the row is gone
    $audit = $pdo->prepare(
        "INSERT INTO cash_box_audit_log (cash_box_id, action, changed_by, old_data, new_data)
         VALUES (?, 'deleted', ?, ?, NULL)"
    );
    $audit->execute([$id, $changed_by, $old_data]);

    echo json_encode(['success' => true]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
