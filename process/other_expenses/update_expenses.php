<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../../config/db_conected.php';
    require_once '../../config/permissions.php';
    header('Content-Type: application/json');
    if (!hasPermission('edit_other_expenses')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
        exit;
    }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $purpose = $_POST['purpose'] ?? '';
    $person_id = $_POST['person_id'] ?? null;
    $employee_id = $_POST['employee_id'] ?? null;
    $car_id = $_POST['car_id'] ?? null;
    $gas_liters = isset($_POST['gas_liters']) ? floatval($_POST['gas_liters']) : null;
    $expense_type = $_POST['expense_type'] ?? '';
    $material_id = $_POST['material_id'] ?? null;
    $material_quantity = isset($_POST['material_quantity']) ? floatval($_POST['material_quantity']) : null;
    $material_purchase_price_iqd = isset($_POST['material_purchase_price_iqd']) ? floatval($_POST['material_purchase_price_iqd']) : null;
    $material_purchase_price_usd = isset($_POST['material_purchase_price_usd']) ? floatval($_POST['material_purchase_price_usd']) : null;
    $material_total_cost = isset($_POST['material_total_cost']) ? floatval($_POST['material_total_cost']) : null;
    $gas_purchase_price_input = isset($_POST['gas_purchase_price_input']) ? floatval($_POST['gas_purchase_price_input']) : null;
    $gas_total_cost = isset($_POST['gas_total_cost']) ? floatval($_POST['gas_total_cost']) : null;
    $payment_type = $_POST['payment_type'] ?? '';
    $currency_type = $_POST['currency_type'] ?? '';
    $invoice_number = $_POST['invoice_number'] ?? '';
    $amount_iqd = isset($_POST['amount_iqd']) ? floatval($_POST['amount_iqd']) : 0;
    $amount_usd = isset($_POST['amount_usd']) ? floatval($_POST['amount_usd']) : 0;
    $paid_iqd = isset($_POST['paid_iqd']) ? floatval($_POST['paid_iqd']) : 0;
    $paid_usd = isset($_POST['paid_usd']) ? floatval($_POST['paid_usd']) : 0;
    $exchange_rate = isset($_POST['exchange_rate']) ? floatval($_POST['exchange_rate']) : 150000;
    $remaining_iqd = isset($_POST['remaining_iqd']) ? floatval($_POST['remaining_iqd']) : 0;
    $remaining_usd = isset($_POST['remaining_usd']) ? floatval($_POST['remaining_usd']) : 0;
    $date = $_POST['date'] ?? '';

    // id and expense_type are required
    if (!$id) {
        echo json_encode(['success' => false, 'msg' => 'ناسنامەی خەرجی پێویستە']);
        exit;
    }
    if ($expense_type === '') {
        echo json_encode(['success' => false, 'msg' => 'جۆری خەرجی پێویستە']);
        exit;
    }

    // Check material availability for warehouse material usage
    if ($expense_type === 'بەکارهێنانی کاڵای کۆگا' && $material_id && $material_quantity) {
        // Get current stock quantity for the material
        $stock_sql = "SELECT quantity, name FROM list_materials WHERE id = ?";
        $stock_stmt = $pdo->prepare($stock_sql);
        $stock_stmt->execute([$material_id]);
        $material_stock = $stock_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$material_stock) {
            echo json_encode(['success' => false, 'msg' => 'کاڵا نەدۆزرایەوە']);
            exit;
        }
        
        $available_quantity = floatval($material_stock['quantity']);
        $required_quantity = floatval($material_quantity);
        
        // For updates, we need to consider the current expense quantity
        // Get the current expense record to see if we need to adjust the check
        $current_sql = "SELECT material_quantity FROM other_expenses WHERE id = ?";
        $current_stmt = $pdo->prepare($current_sql);
        $current_stmt->execute([$id]);
        $current_expense = $current_stmt->fetch(PDO::FETCH_ASSOC);
        
        $current_quantity = $current_expense ? floatval($current_expense['material_quantity']) : 0;
        $quantity_difference = $required_quantity - $current_quantity;
        
        // Only check if we're requesting more than what was already used
        if ($quantity_difference > 0 && $available_quantity < $quantity_difference) {
            echo json_encode([
                'success' => false, 
                'msg' => "بڕی پێویست لە کۆگا نەماوە. بڕی بەردەست: {$available_quantity}، بڕی پێویست: {$quantity_difference}"
            ]);
            exit;
        }
    }

    // Check for duplicate invoice_number (except for this record)
    if ($invoice_number) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM other_expenses WHERE invoice_number = ? AND id != ?');
        $stmt->execute([$invoice_number, $id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'msg' => 'ئەم ژمارەی پسوڵەیە پێشتر تۆمارکراوە!']);
            exit;
        }
    }

    // Fetch old gas_liters value for this expense
    $stmt_old = $pdo->prepare('SELECT gas_liters FROM other_expenses WHERE id=?');
    $stmt_old->execute([$id]);
    $old_gas_liters = $stmt_old->fetchColumn();

    $sql = "UPDATE other_expenses SET
        purpose=?, person_id=?, employee_id=?, car_id=?, gas_liters=?, expense_type=?, material_id=?, material_quantity=?, material_purchase_price_iqd=?, material_purchase_price_usd=?, material_total_cost=?, gas_purchase_price_input=?, gas_total_cost=?, payment_type=?, currency_type=?, invoice_number=?,
        amount_iqd=?, amount_usd=?, paid_iqd=?, paid_usd=?, exchange_rate=?, remaining_iqd=?, remaining_usd=?, date=?
        WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        $purpose,
        $person_id,
        $employee_id ?: null,
        $car_id ?: null,
        $gas_liters,
        $expense_type,
        $material_id,
        $material_quantity,
        $material_purchase_price_iqd,
        $material_purchase_price_usd,
        $material_total_cost,
        $gas_purchase_price_input,
        $gas_total_cost,
        $payment_type,
        $currency_type,
        $invoice_number,
        $amount_iqd,
        $amount_usd,
        $paid_iqd,
        $paid_usd,
        $exchange_rate,
        $remaining_iqd,
        $remaining_usd,
        $date,
        $id
    ]);
    if ($ok) {
        // If gas_liters is set and > 0, update bins_silos (gas tank)
        if (($gas_liters && $gas_liters > 0) || ($old_gas_liters && $old_gas_liters > 0)) {
            $stmt_gas = $pdo->prepare("SELECT id, amount, total_value, average_price FROM bins_silos WHERE type='تەنکی' AND material_type='گاز' LIMIT 1");
            $stmt_gas->execute();
            $gas_tank = $stmt_gas->fetch(PDO::FETCH_ASSOC);
            if ($gas_tank) {
                $new_amount = $gas_tank['amount'];
                // Restore old gas_liters if any
                if ($old_gas_liters && $old_gas_liters > 0) {
                    $new_amount += $old_gas_liters;
                }
                // Subtract new gas_liters if any
                if ($gas_liters && $gas_liters > 0) {
                    if ($new_amount < $gas_liters) {
                        echo json_encode(['success' => false, 'msg' => 'بڕی گاز لە تەنکی کەمە!']);
                        exit;
                    }
                    $new_amount -= $gas_liters;
                }
                $update_gas = $pdo->prepare("UPDATE bins_silos SET amount=? WHERE id=?");
                $update_gas->execute([$new_amount, $gas_tank['id']]);
            }
        }
        require_once __DIR__ . '/../../includes/notify.php';
        notify('update', 'other_expenses', $id, 'خەرجی تر نوێکرایەوە (ID: ' . $id . ')');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە']);
    }
    exit;
}
echo json_encode(['success' => false, 'msg' => 'POST تەنها ڕێگەپێدراوە']);
} catch (Exception $e) {
    error_log('Error in update_expenses.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'msg' => 'هەڵەی سیستەم: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
