<?php
require_once '../../config/db_conected.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';
    $material_type = $_POST['material_type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $average_price = $_POST['average_price'] ?? 0;
    $total_value = $average_price * $amount;
    if ($name !== '' && $type !== '' && $material_type !== '') {
        $stmt = $pdo->prepare("INSERT INTO bins_silos (name, type, material_type, amount, average_price, total_value) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $type, $material_type, $amount, $average_price, $total_value]);
        echo 'success';
    } else {
        echo 'error';
    }
}
