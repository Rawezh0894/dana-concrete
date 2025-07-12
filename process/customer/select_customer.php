<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!hasPermission('view_customer')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM customers WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($data);
    exit;
}

$sql = "SELECT id, name, mobile1, mobile2, debt_usd, debt_iqd, opening_debt_usd, opening_debt_iqd FROM customers ORDER BY id DESC";
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
