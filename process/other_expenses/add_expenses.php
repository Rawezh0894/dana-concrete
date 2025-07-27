<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../../config/db_conected.php';
    require_once '../../config/permissions.php';
    header('Content-Type: application/json');

    if (!hasPermission('add_other_expenses')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
        exit;
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Only expense_type is required
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
        
        if ($available_quantity < $required_quantity) {
            echo json_encode([
                'success' => false, 
                'msg' => "بڕی پێویست لە کۆگا نەماوە. بڕی بەردەست: {$available_quantity}، بڕی پێویست: {$required_quantity}"
            ]);
            exit;
        }
    }

    // Check for duplicate invoice_number
    if ($invoice_number) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM other_expenses WHERE invoice_number = ?');
        $stmt->execute([$invoice_number]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'msg' => 'ئەم ژمارەی پسوڵەیە پێشتر تۆمارکراوە!']);
            exit;
        }
    }

    $sql = "INSERT INTO other_expenses (
        purpose, person_id, employee_id, car_id, gas_liters, expense_type, material_id, material_quantity, material_purchase_price_iqd, material_purchase_price_usd, material_total_cost, gas_purchase_price_input, gas_total_cost, payment_type, currency_type, invoice_number,
        amount_iqd, amount_usd, paid_iqd, paid_usd, exchange_rate, remaining_iqd, remaining_usd, date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
        $date
    ]);
    if ($ok) {
        // Update person expense if person_id is set
        if ($person_id) {
            $update = $pdo->prepare('UPDATE other_expense_persons SET expense_usd = expense_usd + ?, expense_iqd = expense_iqd + ? WHERE id = ?');
            $update->execute([$remaining_usd, $remaining_iqd, $person_id]);
        }
        // If gas_liters is set and > 0, decrement from bins_silos (gas tank)
        if ($gas_liters && $gas_liters > 0) {
            // Find the gas tank
            $stmt_gas = $pdo->prepare("SELECT id, amount, total_value, average_price FROM bins_silos WHERE type='تەنکی' AND material_type='گاز' LIMIT 1");
            $stmt_gas->execute();
            $gas_tank = $stmt_gas->fetch(PDO::FETCH_ASSOC);
            if ($gas_tank && $gas_tank['amount'] >= $gas_liters) {
                $new_amount = $gas_tank['amount'] - $gas_liters;
                $update_gas = $pdo->prepare("UPDATE bins_silos SET amount=? WHERE id=?");
                $update_gas->execute([$new_amount, $gas_tank['id']]);
            } else if ($gas_tank) {
                // Not enough gas in tank
                echo json_encode(['success' => false, 'msg' => 'بڕی گاز لە تەنکی کەمە!']);
                exit;
            }
        }
        require_once __DIR__ . '/../../includes/notify.php';
        notify('insert', 'other_expenses', $pdo->lastInsertId(), 'خەرجی تر زیادکرا (invoice: ' . $invoice_number . ')');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردن']);
    }
    exit;
}
echo json_encode(['success' => false, 'msg' => 'POST تەنها ڕێگەپێدراوە']);
} catch (Exception $e) {
    error_log('Error in add_expenses.php: ' . $e->getMessage());
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
