<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('delete_purchase')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ سڕینەوەی کڕین']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    // Fetch full purchase info before delete
    $infoStmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ?');
    $infoStmt->execute([$id]);
    $purchase = $infoStmt->fetch(PDO::FETCH_ASSOC);
    if (!$purchase) {
        echo json_encode(['success' => false, 'msg' => 'کڕین نەدۆزرایەوە']);
        exit;
    }
    // Copy to recycle_bin_purchases
    $copyStmt = $pdo->prepare('INSERT INTO recycle_bin_purchases (
        original_id, date, invoice_number, driver, location, material_id, kg, price, payment_type, exchange_rate, company_id, type, paid_usd, paid_iqd, remaining_usd, remaining_iqd, bin_id, amount_iqd, price_per_kg_iqd, price_per_kg_usd
    ) VALUES (
        :original_id, :date, :invoice_number, :driver, :location, :material_id, :kg, :price, :payment_type, :exchange_rate, :company_id, :type, :paid_usd, :paid_iqd, :remaining_usd, :remaining_iqd, :bin_id, :amount_iqd, :price_per_kg_iqd, :price_per_kg_usd
    )');
    $copyOk = $copyStmt->execute([
        ':original_id' => $purchase['id'],
        ':date' => $purchase['date'],
        ':invoice_number' => $purchase['invoice_number'],
        ':driver' => $purchase['driver'],
        ':location' => $purchase['location'],
        ':material_id' => $purchase['material_id'],
        ':kg' => $purchase['kg'],
        ':price' => $purchase['price'],
        ':payment_type' => $purchase['payment_type'],
        ':exchange_rate' => $purchase['exchange_rate'],
        ':company_id' => $purchase['company_id'],
        ':type' => $purchase['type'],
        ':paid_usd' => $purchase['paid_usd'],
        ':paid_iqd' => $purchase['paid_iqd'],
        ':remaining_usd' => $purchase['remaining_usd'],
        ':remaining_iqd' => $purchase['remaining_iqd'],
        ':bin_id' => $purchase['bin_id'],
        ':amount_iqd' => $purchase['amount_iqd'],
        ':price_per_kg_iqd' => $purchase['price_per_kg_iqd'],
        ':price_per_kg_usd' => $purchase['price_per_kg_usd'],
    ]);
    if (!$copyOk) {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە گواستنەوە بۆ ڕیسایکڵ بین']);
        exit;
    }
    // Now delete from purchases
    $stmt = $pdo->prepare('DELETE FROM purchases WHERE id = ?');
    $ok = $stmt->execute([$id]);
    if ($ok && $stmt->rowCount() > 0) {
        // If credit, update company debt
        if ($purchase['payment_type'] === 'قەرز') {
            if ($purchase['type'] === 'دۆلار') {
                $updateDebt = $pdo->prepare('UPDATE company SET debt_usd = debt_usd - ? WHERE id = ?');
                $updateDebt->execute([$purchase['remaining_usd'], $purchase['company_id']]);
            } elseif ($purchase['type'] === 'دینار') {
                $updateDebt = $pdo->prepare('UPDATE company SET debt_iqd = debt_iqd - ? WHERE id = ?');
                $updateDebt->execute([$purchase['remaining_iqd'], $purchase['company_id']]);
            }
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە یان id نەدۆزرایەوە']);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
}
