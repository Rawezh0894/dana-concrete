<?php
require_once '../../config/db_conected.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';
    $material_type = $_POST['material_type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $average_price = $_POST['average_price'] ?? 0;
    $total_value = $average_price * $amount;
    if ($id && $name !== '' && $type !== '' && $material_type !== '') {
        $stmt = $pdo->prepare("UPDATE bins_silos SET name=?, type=?, material_type=?, amount=?, average_price=?, total_value=? WHERE id=?");
        $stmt->execute([$name, $type, $material_type, $amount, $average_price, $total_value, $id]);
        echo 'success';
    } else {
        echo 'error';
    }
}
