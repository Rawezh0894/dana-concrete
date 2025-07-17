<?php
header('Content-Type: application/json');
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!hasPermission('delete_sale')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

$id = $_POST['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی فرۆشتن نادیارە']);
    exit;
}

try {
    // Get full sale info before delete
    $stmt = $pdo->prepare('SELECT * FROM sales WHERE id = ?');
    $stmt->execute([$id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        echo json_encode(['success' => false, 'message' => 'فرۆشتن نەدۆزرایەوە!']);
        exit;
    }

    // Copy to recycle_bin_sales
    $copyStmt = $pdo->prepare('INSERT INTO recycle_bin_sales (
        original_id, customer_id, recipient, location, quantity, price_per_unit, total_price, payment_type, amount_paid_usd, amount_paid_iq, dolar_rate, remaining_amount, invoice_number, order_date, notes, formula_id, discount
    ) VALUES (
        :original_id, :customer_id, :recipient, :location, :quantity, :price_per_unit, :total_price, :payment_type, :amount_paid_usd, :amount_paid_iq, :dolar_rate, :remaining_amount, :invoice_number, :order_date, :notes, :formula_id, :discount
    )');
    $copyOk = $copyStmt->execute([
        ':original_id' => $sale['id'],
        ':customer_id' => $sale['customer_id'],
        ':recipient' => $sale['recipient'],
        ':location' => $sale['location'],
        ':quantity' => $sale['quantity'],
        ':price_per_unit' => $sale['price_per_unit'],
        ':total_price' => $sale['total_price'],
        ':payment_type' => $sale['payment_type'],
        ':amount_paid_usd' => $sale['amount_paid_usd'],
        ':amount_paid_iq' => $sale['amount_paid_iq'],
        ':dolar_rate' => $sale['dolar_rate'],
        ':remaining_amount' => $sale['remaining_amount'],
        ':invoice_number' => $sale['invoice_number'],
        ':order_date' => $sale['order_date'],
        ':notes' => $sale['notes'],
        ':formula_id' => $sale['formula_id'],
        ':discount' => $sale['discount'],
    ]);
    if (!$copyOk) {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە گواستنەوە بۆ ڕیسایکڵ بین!']);
        exit;
    }

    // If credit, update customer debt
    if ($sale['customer_id'] !== null && $sale['payment_type'] === 'قەرز') {
        $stmt2 = $pdo->prepare("UPDATE customers SET debt_usd = IFNULL(debt_usd,0) - ? WHERE id = ?");
        $stmt2->execute([$sale['remaining_amount'], $sale['customer_id']]);
    }

    $stmt = $pdo->prepare('DELETE FROM sales WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount()) {
        require_once __DIR__ . '/../../includes/notify.php';
        notify('delete', 'sales', $id, 'فرۆشتنەکە سڕایەوە (invoice: ' . $sale['invoice_number'] . ')');
        echo json_encode(['success' => true, 'message' => 'فرۆشتن بەسەرکەوتوویی سڕایەوە!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فرۆشتن نەدۆزرایەوە!']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
