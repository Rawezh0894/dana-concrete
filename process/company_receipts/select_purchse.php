<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');
$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : (isset($_POST['company_id']) ? intval($_POST['company_id']) : 0);
if (!$company_id) {
    echo json_encode(['success' => false, 'message' => 'company_id پێویستە']);
    exit;
}
$sql = "SELECT p.*, m.name AS material_name FROM purchases p LEFT JOIN materials m ON p.material_id = m.id WHERE p.company_id = ? ORDER BY p.date DESC, p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$company_id]);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'data' => $purchases]);
