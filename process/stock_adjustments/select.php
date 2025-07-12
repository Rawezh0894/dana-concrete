<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');
$sql = "SELECT sa.id, b.name AS bin_name, sa.adjustment, sa.reason, sa.price_usd, sa.price_iqd, sa.created_at, u.username FROM stock_adjustments sa LEFT JOIN bins_silos b ON sa.bin_id = b.id LEFT JOIN users u ON sa.user_id = u.id ORDER BY sa.created_at DESC, sa.id DESC";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'data' => $rows]);
