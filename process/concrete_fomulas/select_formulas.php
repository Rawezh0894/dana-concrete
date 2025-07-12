<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');

if (!hasPermission('view_concrete_formulas')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگە پێنەدراوە بۆ بینین.']);
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM concrete_formulas WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($data);
    exit;
}

$sql = "SELECT * FROM concrete_formulas ORDER BY id DESC";
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
