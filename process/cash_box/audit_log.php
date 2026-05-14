<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !hasPermission('view_cash_box')) {
    echo json_encode(['success' => false, 'error' => 'دەستپێگەیشتن قەدەغەیە']);
    exit;
}

$cash_box_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$cash_box_id) {
    echo json_encode(['success' => false, 'error' => 'ID نادروستە']);
    exit;
}

// Auto-create audit table if it does not exist yet
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
    $stmt = $pdo->prepare("
        SELECT al.*, u.username AS changed_by_username
        FROM cash_box_audit_log al
        LEFT JOIN users u ON al.changed_by = u.id
        WHERE al.cash_box_id = ?
        ORDER BY al.changed_at ASC
    ");
    $stmt->execute([$cash_box_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode JSON blobs for convenience
    foreach ($rows as &$row) {
        $row['old_data'] = $row['old_data'] ? json_decode($row['old_data'], true) : null;
        $row['new_data'] = $row['new_data'] ? json_decode($row['new_data'], true) : null;
    }

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
