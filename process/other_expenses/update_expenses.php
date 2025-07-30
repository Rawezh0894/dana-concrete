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
    // Convert empty string to null for foreign key constraint
    if ($person_id === '' || $person_id === 'null' || $person_id === 'NULL') {
        $person_id = null;
    }
    // Ensure person_id is either a valid integer or null
    if ($person_id !== null && !is_numeric($person_id)) {
        $person_id = null;
    }
    $employee_id = $_POST['employee_id'] ?? null;
    // Convert empty string to null for foreign key constraint
    if ($employee_id === '') {
        $employee_id = null;
    }
    $car_id = $_POST['car_id'] ?? null;
    // Convert empty string to null for foreign key constraint
    if ($car_id === '') {
        $car_id = null;
    }
    $gas_liters = isset($_POST['gas_liters']) ? floatval($_POST['gas_liters']) : null;
    $expense_type = $_POST['expense_type'] ?? '';
    $material_id = $_POST['material_id'] ?? null;
    // Convert empty string to null for foreign key constraint
    if ($material_id === '') {
        $material_id = null;
    }
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

    // Check gas availability for gas usage expenses
    if ($expense_type === 'بەکارهێنانی گاز' && $gas_liters && $gas_liters > 0) {
        // Get current gas amount in the tank
        $gas_sql = "SELECT amount FROM bins_silos WHERE type = 'تەنکی' AND material_type = 'گاز' LIMIT 1";
        $gas_stmt = $pdo->prepare($gas_sql);
        $gas_stmt->execute();
        $gas_tank = $gas_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$gas_tank) {
            echo json_encode(['success' => false, 'msg' => 'تەنکی گاز لە سیستەمەکەدا نییە']);
            exit;
        }
        
        $available_gas = floatval($gas_tank['amount']);
        $required_gas = floatval($gas_liters);

        // Get the current expense record to see if we need to adjust the check
        $current_gas_sql = "SELECT gas_liters FROM other_expenses WHERE id = ?";
        $current_gas_stmt = $pdo->prepare($current_gas_sql);
        $current_gas_stmt->execute([$id]);
        $current_gas_expense = $current_gas_stmt->fetch(PDO::FETCH_ASSOC);

        $current_gas = $current_gas_expense ? floatval($current_gas_expense['gas_liters']) : 0;
        $gas_difference = $required_gas - $current_gas;

        // Only check if we're requesting more gas than what was already used
        if ($gas_difference > 0 && $available_gas < $gas_difference) {
            echo json_encode([
                'success' => false,
                'msg' => "بڕی گاز لە تەنکی کەمە. بڕی بەردەست: {$available_gas} لیتر، بڕی پێویست: {$gas_difference} لیتر"
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
                        // Note: Gas consumption is now handled automatically by database triggers
                        // when expense_type = 'بەکارهێنانی گاز' and gas_liters > 0

                        // Get related information for notification
                        $person_name = 'هیچ کەسێک نییە';
                        $employee_name = 'هیچ کارمەندێک نییە';
                        $car_name = 'هیچ سەیارەیەک نییە';
                        $material_name = 'هیچ مادەیەک نییە';

                        if ($person_id) {
                            $stmt = $pdo->prepare("SELECT name FROM other_expense_persons WHERE id = ?");
                            $stmt->execute([$person_id]);
                            $person = $stmt->fetch();
                            $person_name = $person['name'] ?? 'Unknown';
                        }

                        if ($employee_id) {
                            $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
                            $stmt->execute([$employee_id]);
                            $employee = $stmt->fetch();
                            $employee_name = $employee['name'] ?? 'Unknown';
                        }

                        if ($car_id) {
                            $stmt = $pdo->prepare("SELECT name FROM cars WHERE id = ?");
                            $stmt->execute([$car_id]);
                            $car = $stmt->fetch();
                            $car_name = $car['name'] ?? 'Unknown';
                        }

                        if ($material_id) {
                            $stmt = $pdo->prepare("SELECT name FROM materials WHERE id = ?");
                            $stmt->execute([$material_id]);
                            $material = $stmt->fetch();
                            $material_name = $material['name'] ?? 'Unknown';
                        }

                        // Get old values for notification
                        $stmt = $pdo->prepare("SELECT * FROM other_expenses WHERE id = ?");
                        $stmt->execute([$id]);
                        $old_record = $stmt->fetch();

                        // Get old related information
                        $old_person_name = 'هیچ کەسێک نییە';
                        $old_employee_name = 'هیچ کارمەندێک نییە';
                        $old_car_name = 'هیچ سەیارەیەک نییە';
                        $old_material_name = 'هیچ مادەیەک نییە';

                        if ($old_record['person_id']) {
                            $stmt = $pdo->prepare("SELECT name FROM other_expense_persons WHERE id = ?");
                            $stmt->execute([$old_record['person_id']]);
                            $old_person = $stmt->fetch();
                            $old_person_name = $old_person['name'] ?? 'Unknown';
                        }

                        if ($old_record['employee_id']) {
                            $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
                            $stmt->execute([$old_record['employee_id']]);
                            $old_employee = $stmt->fetch();
                            $old_employee_name = $old_employee['name'] ?? 'Unknown';
                        }

                        if ($old_record['car_id']) {
                            $stmt = $pdo->prepare("SELECT name FROM cars WHERE id = ?");
                            $stmt->execute([$old_record['car_id']]);
                            $old_car = $stmt->fetch();
                            $old_car_name = $old_car['name'] ?? 'Unknown';
                        }

                        if ($old_record['material_id']) {
                            $stmt = $pdo->prepare("SELECT name FROM materials WHERE id = ?");
                            $stmt->execute([$old_record['material_id']]);
                            $old_material = $stmt->fetch();
                            $old_material_name = $old_material['name'] ?? 'Unknown';
                        }

                        $old_values = [
                            'person_id' => $old_record['person_id'],
                            'person_name' => $old_person_name,
                            'employee_id' => $old_record['employee_id'],
                            'employee_name' => $old_employee_name,
                            'car_id' => $old_record['car_id'],
                            'car_name' => $old_car_name,
                            'gas_liters' => $old_record['gas_liters'],
                            'expense_type' => $old_record['expense_type'],
                            'material_id' => $old_record['material_id'],
                            'material_name' => $old_material_name,
                            'material_quantity' => $old_record['material_quantity'],
                            'material_purchase_price_iqd' => $old_record['material_purchase_price_iqd'],
                            'material_purchase_price_usd' => $old_record['material_purchase_price_usd'],
                            'material_total_cost' => $old_record['material_total_cost'],
                            'gas_purchase_price_input' => $old_record['gas_purchase_price_input'],
                            'gas_total_cost' => $old_record['gas_total_cost'],
                            'payment_type' => $old_record['payment_type'],
                            'currency_type' => $old_record['currency_type'],
                            'invoice_number' => $old_record['invoice_number'],
                            'amount_iqd' => $old_record['amount_iqd'],
                            'amount_usd' => $old_record['amount_usd'],
                            'paid_iqd' => $old_record['paid_iqd'],
                            'paid_usd' => $old_record['paid_usd'],
                            'exchange_rate' => $old_record['exchange_rate'],
                            'remaining_iqd' => $old_record['remaining_iqd'],
                            'remaining_usd' => $old_record['remaining_usd'],
                            'date' => $old_record['date']
                        ];

                        $new_values = [
                            'person_id' => $person_id,
                            'person_name' => $person_name,
                            'employee_id' => $employee_id,
                            'employee_name' => $employee_name,
                            'car_id' => $car_id,
                            'car_name' => $car_name,
                            'gas_liters' => $gas_liters,
                            'expense_type' => $expense_type,
                            'material_id' => $material_id,
                            'material_name' => $material_name,
                            'material_quantity' => $material_quantity,
                            'material_purchase_price_iqd' => $material_purchase_price_iqd,
                            'material_purchase_price_usd' => $material_purchase_price_usd,
                            'material_total_cost' => $material_total_cost,
                            'gas_purchase_price_input' => $gas_purchase_price_input,
                            'gas_total_cost' => $gas_total_cost,
                            'payment_type' => $payment_type,
                            'currency_type' => $currency_type,
                            'invoice_number' => $invoice_number,
                            'amount_iqd' => $amount_iqd,
                            'amount_usd' => $amount_usd,
                            'paid_iqd' => $paid_iqd,
                            'paid_usd' => $paid_usd,
                            'exchange_rate' => $exchange_rate,
                            'remaining_iqd' => $remaining_iqd,
                            'remaining_usd' => $remaining_usd,
                            'date' => $date
                        ];

                        $additional_info = [
                            'action_type' => 'other_expense_update',
                            'payment_status' => $payment_type === 'نەقد' ? 'paid' : 'credit',
                            'currency_used' => $paid_usd > 0 ? 'USD' : ($paid_iqd > 0 ? 'IQD' : 'none'),
                            'total_paid' => $paid_usd + $paid_iqd,
                            'remaining_debt' => $remaining_usd + $remaining_iqd,
                            'expense_category' => $expense_type
                        ];

                        createDetailedNotification(
                            $pdo,
                            $_SESSION['user_id'],
                            'update',
                            'other_expenses',
                            $id,
                            "خەرجی تر نوێکرایەوە (ID: $id, جۆر: $expense_type, کەس: $person_name, کارمەند: $employee_name, سەیارە: $car_name)",
                            $old_values,
                            $new_values,
                            $additional_info,
                            getUserIP()
                        );

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
        
        // Check for specific foreign key constraint violations
        if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
            if (strpos($e->getMessage(), 'person_id') !== false) {
                echo json_encode([
                    'success' => false, 
                    'msg' => 'کەسی هەڵبژێردراو لە سیستەمەکەدا نییە. تکایە کەسێکی تر هەڵبژێرە یان کەسێکی نوێ زیاد بکە.',
                    'debug_info' => [
                        'error_type' => 'foreign_key_violation',
                        'field' => 'person_id',
                        'message' => $e->getMessage()
                    ]
                ]);
            } elseif (strpos($e->getMessage(), 'employee_id') !== false) {
                echo json_encode([
                    'success' => false, 
                    'msg' => 'کارمەندی هەڵبژێردراو لە سیستەمەکەدا نییە. تکایە کارمەندێکی تر هەڵبژێرە.',
                    'debug_info' => [
                        'error_type' => 'foreign_key_violation',
                        'field' => 'employee_id',
                        'message' => $e->getMessage()
                    ]
                ]);
            } elseif (strpos($e->getMessage(), 'car_id') !== false) {
                echo json_encode([
                    'success' => false, 
                    'msg' => 'سەیارەی هەڵبژێردراو لە سیستەمەکەدا نییە. تکایە سەیارەیەکی تر هەڵبژێرە.',
                    'debug_info' => [
                        'error_type' => 'foreign_key_violation',
                        'field' => 'car_id',
                        'message' => $e->getMessage()
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'msg' => 'هەڵەی پەیوەندی داتا: بەهای هەڵبژێردراو لە سیستەمەکەدا نییە.',
                    'debug_info' => [
                        'error_type' => 'foreign_key_violation',
                        'message' => $e->getMessage()
                    ]
                ]);
            }
        } else {
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
    }
