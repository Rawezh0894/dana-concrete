<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !hasPermission('delete_purchase')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare('SELECT * FROM recycle_bin_purchases WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'msg' => 'مامەڵە نەدۆزرایەوە']);
        exit;
    }
    // Insert back to purchases
    $ins = $pdo->prepare('INSERT INTO purchases (
        date, invoice_number, driver, location, material_id, kg, price, payment_type, exchange_rate, company_id, type, paid_usd, paid_iqd, remaining_usd, remaining_iqd, bin_id, amount_iqd, price_per_kg_iqd, price_per_kg_usd
    ) VALUES (
        :date, :invoice_number, :driver, :location, :material_id, :kg, :price, :payment_type, :exchange_rate, :company_id, :type, :paid_usd, :paid_iqd, :remaining_usd, :remaining_iqd, :bin_id, :amount_iqd, :price_per_kg_iqd, :price_per_kg_usd
    )');
    $ok = $ins->execute([
        ':date' => $row['date'],
        ':invoice_number' => $row['invoice_number'],
        ':driver' => $row['driver'],
        ':location' => $row['location'],
        ':material_id' => $row['material_id'],
        ':kg' => $row['kg'],
        ':price' => $row['price'],
        ':payment_type' => $row['payment_type'],
        ':exchange_rate' => $row['exchange_rate'],
        ':company_id' => $row['company_id'],
        ':type' => $row['type'],
        ':paid_usd' => $row['paid_usd'],
        ':paid_iqd' => $row['paid_iqd'],
        ':remaining_usd' => $row['remaining_usd'],
        ':remaining_iqd' => $row['remaining_iqd'],
        ':bin_id' => $row['bin_id'],
        ':amount_iqd' => $row['amount_iqd'],
        ':price_per_kg_iqd' => $row['price_per_kg_iqd'],
        ':price_per_kg_usd' => $row['price_per_kg_usd'],
    ]);
    if (!$ok) {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە گەڕاندنەوە']);
        exit;
    }
    // Delete from recycle_bin_purchases
    $del = $pdo->prepare('DELETE FROM recycle_bin_purchases WHERE id = ?');
    $del->execute([$id]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
} 