<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !hasPermission('add_company')) {
    echo json_encode([]);
    exit;
}
$stmt = $pdo->query('SELECT id, name, debt_usd, debt_iqd, opening_debt_usd, opening_debt_iqd, currency_type FROM company ORDER BY id ASC');
$companies = $stmt->fetchAll();
echo json_encode($companies);
