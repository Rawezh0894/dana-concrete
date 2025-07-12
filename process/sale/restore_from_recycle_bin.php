<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !hasPermission('delete_sale')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare('SELECT * FROM recycle_bin_sales WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'msg' => 'مامەڵە نەدۆزرایەوە']);
        exit;
    }
    // Insert back to sales
    $ins = $pdo->prepare('INSERT INTO sales (
        customer_id, recipient, location, quantity, price_per_unit, total_price, payment_type, amount_paid_usd, amount_paid_iq, dolar_rate, remaining_amount, invoice_number, order_date, notes, formula_id, discount
    ) VALUES (
        :customer_id, :recipient, :location, :quantity, :price_per_unit, :total_price, :payment_type, :amount_paid_usd, :amount_paid_iq, :dolar_rate, :remaining_amount, :invoice_number, :order_date, :notes, :formula_id, :discount
    )');
    $ok = $ins->execute([
        ':customer_id' => $row['customer_id'],
        ':recipient' => $row['recipient'],
        ':location' => $row['location'],
        ':quantity' => $row['quantity'],
        ':price_per_unit' => $row['price_per_unit'],
        ':total_price' => $row['total_price'],
        ':payment_type' => $row['payment_type'],
        ':amount_paid_usd' => $row['amount_paid_usd'],
        ':amount_paid_iq' => $row['amount_paid_iq'],
        ':dolar_rate' => $row['dolar_rate'],
        ':remaining_amount' => $row['remaining_amount'],
        ':invoice_number' => $row['invoice_number'],
        ':order_date' => $row['order_date'],
        ':notes' => $row['notes'],
        ':formula_id' => $row['formula_id'],
        ':discount' => $row['discount'],
    ]);
    if (!$ok) {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە گەڕاندنەوە']);
        exit;
    }
    // Delete from recycle_bin_sales
    $del = $pdo->prepare('DELETE FROM recycle_bin_sales WHERE id = ?');
    $del->execute([$id]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
} 