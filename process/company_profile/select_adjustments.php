<?php
// c:\xampp\htdocs\dana-concrete\process\company_profile\select_adjustments.php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

$company_id = $_GET['company_id'] ?? 0;
if (!$company_id) {
    echo json_encode([]);
    exit;
}

$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$sql = "SELECT id, date, amount_usd, amount_iqd, note FROM company_adjustments WHERE company_id = ? ";
$params = [$company_id];

if ($from_date && $to_date) {
    $sql .= " AND date >= ? AND date <= ? ";
    $params[] = $from_date;
    $params[] = $to_date;
}

$sql .= " ORDER BY date DESC, id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (PDOException $e) {
    echo json_encode([]);
}
