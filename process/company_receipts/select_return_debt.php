<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');
$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : (isset($_POST['company_id']) ? intval($_POST['company_id']) : 0);
if (!$company_id) {
    echo json_encode(['success' => false, 'message' => 'company_id پێویستە']);
    exit;
}
$sql = "SELECT id, date, amount_usd, amount_iqd, dollar_rate, note FROM debt_payments WHERE company_id = ? ORDER BY date DESC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$company_id]);
$debts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'data' => $debts]);
