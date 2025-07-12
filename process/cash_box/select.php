<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$where = [];
$params = [];
if ($from) {
    $where[] = 'cb.date >= ?';
    $params[] = $from;
}
if ($to) {
    $where[] = 'cb.date <= ?';
    $params[] = $to;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $sql = "SELECT cb.*, u.username as created_by_username
            FROM cash_box cb
            LEFT JOIN users u ON cb.created_by = u.id
            $whereSql
            ORDER BY cb.date DESC, cb.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
