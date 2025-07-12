<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

$where = [];
$params = [];
if ($from) {
    $where[] = 'date >= ?';
    $params[] = $from;
}
if ($to) {
    $where[] = 'date <= ?';
    $params[] = $to;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    // USD
    $sql_usd = "SELECT SUM(CASE WHEN type='deposit' THEN amount_usd ELSE -amount_usd END) as total_usd FROM cash_box $whereSql WHERE currency='دۆلار'";
    $stmt_usd = $pdo->prepare(str_replace('WHERE WHERE', 'WHERE', $sql_usd));
    $stmt_usd->execute($params);
    $total_usd = $stmt_usd->fetchColumn() ?: 0;
    // IQD
    $sql_iqd = "SELECT SUM(CASE WHEN type='deposit' THEN amount_iqd ELSE -amount_iqd END) as total_iqd FROM cash_box $whereSql WHERE currency='دینار'";
    $stmt_iqd = $pdo->prepare(str_replace('WHERE WHERE', 'WHERE', $sql_iqd));
    $stmt_iqd->execute($params);
    $total_iqd = $stmt_iqd->fetchColumn() ?: 0;
    // Get USD to IQD rate from settings
    $rate_stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1");
    $rate_stmt->execute();
    $usd_iqd_rate = $rate_stmt->fetchColumn();
    if (!$usd_iqd_rate) $usd_iqd_rate = 150000;
    // Convert IQD to USD (total_iqd / rate * 100)
    $iqd_to_usd = $usd_iqd_rate > 0 ? ($total_iqd / ($usd_iqd_rate / 100)) : 0;
    $total_usd_all = round($total_usd + $iqd_to_usd, 2);
    echo json_encode(['success' => true, 'data' => [
        'total_usd_all' => $total_usd_all
    ]]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 