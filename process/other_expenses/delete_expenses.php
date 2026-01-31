<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!hasPermission('delete_other_expenses')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'msg' => 'ID پێویستە']);
        exit;
    }
    // Get full record before delete
    $info = $pdo->prepare('SELECT * FROM other_expenses WHERE id = ?');
    $info->execute([$id]);
    $row = $info->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        echo json_encode(['success' => false, 'msg' => 'خەرجی نەدۆزرایەوە']);
        exit;
    }

    // Get related information for notification
    $person_name = 'هیچ کەسێک نییە';
    $employee_name = 'هیچ کارمەندێک نییە';
    $car_name = 'هیچ سەیارەیەک نییە';
    $material_name = 'هیچ مادەیەک نییە';

    if ($row['person_id']) {
        $stmt = $pdo->prepare("SELECT name FROM other_expense_persons WHERE id = ?");
        $stmt->execute([$row['person_id']]);
        $person = $stmt->fetch();
        $person_name = $person['name'] ?? 'Unknown';
    }

    if ($row['employee_id']) {
        $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
        $stmt->execute([$row['employee_id']]);
        $employee = $stmt->fetch();
        $employee_name = $employee['name'] ?? 'Unknown';
    }

    if ($row['car_id']) {
        $stmt = $pdo->prepare("SELECT name FROM cars WHERE id = ?");
        $stmt->execute([$row['car_id']]);
        $car = $stmt->fetch();
        $car_name = $car['name'] ?? 'Unknown';
    }

    if ($row['material_id']) {
        $stmt = $pdo->prepare("SELECT name FROM materials WHERE id = ?");
        $stmt->execute([$row['material_id']]);
        $material = $stmt->fetch();
        $material_name = $material['name'] ?? 'Unknown';
    }

    // Create old values for notification
    $old_values = [
        'person_id' => $row['person_id'],
        'person_name' => $person_name,
        'employee_id' => $row['employee_id'],
        'employee_name' => $employee_name,
        'car_id' => $row['car_id'],
        'car_name' => $car_name,
        'gas_liters' => $row['gas_liters'],
        'expense_type' => $row['expense_type'],
        'material_id' => $row['material_id'],
        'material_name' => $material_name,
        'material_quantity' => $row['material_quantity'],
        'material_purchase_price_iqd' => $row['material_purchase_price_iqd'],
        'material_purchase_price_usd' => $row['material_purchase_price_usd'],
        'material_total_cost' => $row['material_total_cost'],
        'gas_purchase_price_input' => $row['gas_purchase_price_input'],
        'gas_total_cost' => $row['gas_total_cost'],
        'payment_type' => $row['payment_type'],
        'currency_type' => $row['currency_type'],
        'invoice_number' => $row['invoice_number'],
        'amount_iqd' => $row['amount_iqd'],
        'amount_usd' => $row['amount_usd'],
        'paid_iqd' => $row['paid_iqd'],
        'paid_usd' => $row['paid_usd'],
        'exchange_rate' => $row['exchange_rate'],
        'remaining_iqd' => $row['remaining_iqd'],
        'remaining_usd' => $row['remaining_usd'],
        'date' => $row['date']
    ];

    $additional_info = [
        'action_type' => 'other_expense_deletion',
        'payment_status' => $row['payment_type'] === 'نەقد' ? 'paid' : 'credit',
        'currency_used' => $row['paid_usd'] > 0 ? 'USD' : ($row['paid_iqd'] > 0 ? 'IQD' : 'none'),
        'total_paid' => $row['paid_usd'] + $row['paid_iqd'],
        'remaining_debt' => $row['remaining_usd'] + $row['remaining_iqd'],
        'expense_category' => $row['expense_type']
    ];

    // If expense has line items (چەند کاڵا بۆ هەمان سەیارە), restore stock from each line and delete line items first
    $line_items_stmt = $pdo->prepare('SELECT id, material_id, base_material_quantity FROM expense_line_items WHERE expense_id = ?');
    $line_items_stmt->execute([$id]);
    $line_items = $line_items_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($line_items)) {
        foreach ($line_items as $li) {
            if ($li['material_id'] && $li['base_material_quantity'] > 0) {
                $update_stock = $pdo->prepare("UPDATE list_materials SET quantity = quantity + ? WHERE id = ?");
                $update_stock->execute([$li['base_material_quantity'], $li['material_id']]);
            }
        }
        $pdo->prepare('DELETE FROM expense_line_items WHERE expense_id = ?')->execute([$id]);
    }

    $stmt = $pdo->prepare('DELETE FROM other_expenses WHERE id = ?');
    $ok = $stmt->execute([$id]);

    if ($ok) {
        if ($row['person_id']) {
            $update = $pdo->prepare('UPDATE other_expense_persons SET expense_usd = expense_usd - ?, expense_iqd = expense_iqd - ? WHERE id = ?');
            $update->execute([$row['remaining_usd'], $row['remaining_iqd'], $row['person_id']]);
        }

        // Reverse stock increment if it was a warehouse purchase
        if ($row['expense_type'] === 'کڕینی کاڵا بۆ کۆگا' && $row['material_id'] && $row['base_material_quantity'] > 0) {
            $update_stock = $pdo->prepare("UPDATE list_materials SET quantity = quantity - ? WHERE id = ?");
            $update_stock->execute([$row['base_material_quantity'], $row['material_id']]);
        }

        // Reverse stock deduction if it was warehouse material usage (single material, no line items)
        if (empty($line_items) && $row['expense_type'] === 'بەکارهێنانی کاڵای کۆگا' && $row['material_id'] && $row['base_material_quantity'] > 0) {
            $update_stock = $pdo->prepare("UPDATE list_materials SET quantity = quantity + ? WHERE id = ?");
            $update_stock->execute([$row['base_material_quantity'], $row['material_id']]);
        }

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'delete',
            'other_expenses',
            $id,
            "خەرجی تر سڕایەوە (ID: $id, جۆر: {$row['expense_type']}, کەس: $person_name, کارمەند: $employee_name, سەیارە: $car_name)",
            $old_values,
            null, // No new values for delete
            $additional_info,
            getUserIP()
        );

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە']);
    }
    exit;
}
echo json_encode(['success' => false, 'msg' => 'POST تەنها ڕێگەپێدراوە']);
