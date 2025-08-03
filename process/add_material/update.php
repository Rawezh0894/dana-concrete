<?php
require_once '../../config/db_conected.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $currency_type = $_POST['currency_type'] ?? 'دینار';
    $purchase_price_usd = $_POST['purchase_price_usd'] ?? 0;
    $purchase_price_iqd = $_POST['purchase_price_iqd'] ?? 0;
    if ($id && $name !== '') {
        $stmt = $pdo->prepare("UPDATE list_materials SET name=?, quantity=?, currency_type=?, purchase_price_usd=?, purchase_price_iqd=? WHERE id=?");
        $stmt->execute([$name, $quantity, $currency_type, $purchase_price_usd, $purchase_price_iqd, $id]);
        echo 'success';
    } else {
        echo 'error';
    }
}
