<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !hasPermission('view_customer')) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}
if (isset($_GET['debt_id'])) {
    $debt_id = intval($_GET['debt_id']);
    $stmt = $pdo->prepare("SELECT * FROM customer_debt_payments WHERE id = ?");
    $stmt->execute([$debt_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($row ?: []);
    exit;
}
$customer_id = $_GET['customer_id'] ?? null;
if (!$customer_id) {
    echo json_encode([]);
    exit;
}
$stmt = $pdo->prepare('SELECT id, date, dolar_rate, paid_usd, paid_iqd, discount, note FROM customer_debt_payments WHERE customer_id = ? ORDER BY date DESC, id DESC');
$stmt->execute([$customer_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows);
