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
        // Update company debt
        if ($purchase['payment_type'] === 'قەرز') {
            if ($purchase['type'] === 'دۆلار') {
                // No need to update company debt_usd/debt_iqd anymore
                // The remaining amount is tracked in the purchases table
            } elseif ($purchase['type'] === 'دینار') {
                // No need to update company debt_usd/debt_iqd anymore
                // The remaining amount is tracked in the purchases table
            }
        }
        // Get company and material information for notification
        $stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
        $stmt->execute([$purchase['company_id']]);
        $company = $stmt->fetch();
        $company_name = $company['name'] ?? 'Unknown';

        $stmt = $pdo->prepare("SELECT name FROM materials WHERE id = ?");
        $stmt->execute([$purchase['material_id']]);
        $material = $stmt->fetch();
        $material_name = $material['name'] ?? 'Unknown';

        // Create old values for notification
        $old_values = [
            'company_id' => $purchase['company_id'],
            'company_name' => $company_name,
            'driver' => $purchase['driver'],
            'location' => $purchase['location'],
            'material_id' => $purchase['material_id'],
            'material_name' => $material_name,
            'amount_iqd' => $purchase['amount_iqd'],
            'kg' => $purchase['kg'],
            'price' => $purchase['price'],
            'payment_type' => $purchase['payment_type'],
            'exchange_rate' => $purchase['exchange_rate'],
            'type' => $purchase['type'],
            'paid_usd' => $purchase['paid_usd'],
            'paid_iqd' => $purchase['paid_iqd'],
            'remaining_usd' => $purchase['remaining_usd'],
            'remaining_iqd' => $purchase['remaining_iqd'],
            'bin_id' => $purchase['bin_id'],
            'price_per_kg_iqd' => $purchase['price_per_kg_iqd'],
            'price_per_kg_usd' => $purchase['price_per_kg_usd'],
            'invoice_number' => $purchase['invoice_number'],
            'date' => $purchase['date']
        ];

        $additional_info = [
            'action_type' => 'purchase_deletion',
            'payment_status' => $purchase['payment_type'] === 'نەقد' ? 'paid' : 'credit',
            'currency_used' => $purchase['paid_usd'] > 0 ? 'USD' : ($purchase['paid_iqd'] > 0 ? 'IQD' : 'none'),
            'total_paid' => $purchase['paid_usd'] + $purchase['paid_iqd'],
            'remaining_debt' => $purchase['remaining_usd'] + $purchase['remaining_iqd']
        ];

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'delete',
            'purchases',
            $id,
            "کڕینەکە سڕایەوە (invoice: {$purchase['invoice_number']}, کۆمپانیا: $company_name, مادە: $material_name)",
            $old_values,
            null, // No new values for delete
            $additional_info,
            getUserIP()
        );

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە یان id نەدۆزرایەوە']);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
}
