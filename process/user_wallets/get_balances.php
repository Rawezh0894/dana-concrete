<?php
session_start();
require_once '../../config/db_conected.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT currency_code, balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallets = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

echo json_encode([
    'success' => true,
    'usd' => floatval($wallets['USD'] ?? 0),
    'iqd' => floatval($wallets['IQD'] ?? 0)
]);
