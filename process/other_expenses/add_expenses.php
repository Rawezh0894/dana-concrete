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
    $expense_type = $_POST['expense_type'] ?? 'خەرجی تر'; // Default to خەرجی تر if empty
    // Ensure expense_type is valid
    if (!in_array($expense_type, ['بەکارهێنانی کاڵای کۆگا', 'بەکارهێنانی گاز', 'خەرجی تر', 'خواردنگە', 'ئۆفیس'])) {
        $expense_type = 'خەرجی تر';
    }
    $material_id = $_POST['material_id'] ?? null;
    // Convert empty string to null for foreign key constraint
    if ($material_id === '') {
        $material_id = null;
    }
    $material_quantity = isset($_POST['material_quantity']) ? floatval($_POST['material_quantity']) : null;
    $usage_unit_type = $_POST['usage_unit_type'] ?? null;
    
    // Debug logging
    error_log("Received usage_unit_type: " . var_export($usage_unit_type, true));
    
    // Convert empty string to null and validate usage_unit_type against enum values
    if ($usage_unit_type === '' || $usage_unit_type === 'null' || $usage_unit_type === 'NULL') {
        $usage_unit_type = null;
        error_log("Converted usage_unit_type to null");
    } elseif ($usage_unit_type && !in_array($usage_unit_type, ['کارتۆن', 'دانە', 'بەرمیل', 'دەبە', 'لیتر'])) {
        $usage_unit_type = null;
        error_log("Invalid usage_unit_type value, converted to null");
    }
    
    error_log("Final usage_unit_type value: " . var_export($usage_unit_type, true));
    $material_purchase_price_iqd = !empty($_POST['material_purchase_price_iqd']) ? floatval($_POST['material_purchase_price_iqd']) : 0;
    $material_purchase_price_usd = !empty($_POST['material_purchase_price_usd']) ? floatval($_POST['material_purchase_price_usd']) : 0;
    $material_total_cost = !empty($_POST['material_total_cost']) ? floatval($_POST['material_total_cost']) : 0;
    $gas_purchase_price_input = !empty($_POST['gas_purchase_price_input']) ? floatval($_POST['gas_purchase_price_input']) : 0;
    $gas_total_cost = !empty($_POST['gas_total_cost']) ? floatval($_POST['gas_total_cost']) : 0;
    $payment_type = $_POST['payment_type'] ?? 'نەقد'; // Default to نەقد if empty
    // Ensure payment_type is valid
    if (!in_array($payment_type, ['نەقد', 'قەرز'])) {
        $payment_type = 'نەقد';
    }
    $currency_type = $_POST['currency_type'] ?? 'دینار'; // Default to دینار if empty
    // Ensure currency_type is valid
    if (!in_array($currency_type, ['دینار', 'دۆلار', 'تێکەڵ'])) {
        $currency_type = 'دینار';
    }
    $invoice_number = $_POST['invoice_number'] ?? '';
    $amount_iqd = isset($_POST['amount_iqd']) ? floatval($_POST['amount_iqd']) : 0;
    $amount_usd = isset($_POST['amount_usd']) ? floatval($_POST['amount_usd']) : 0;
    $paid_iqd = isset($_POST['paid_iqd']) ? floatval($_POST['paid_iqd']) : 0;
    $paid_usd = isset($_POST['paid_usd']) ? floatval($_POST['paid_usd']) : 0;
    $exchange_rate = isset($_POST['exchange_rate']) ? floatval($_POST['exchange_rate']) : 139250;
    $remaining_iqd = isset($_POST['remaining_iqd']) ? floatval($_POST['remaining_iqd']) : 0;
    $remaining_usd = isset($_POST['remaining_usd']) ? floatval($_POST['remaining_usd']) : 0;
    $date = $_POST['date'] ?? '';

    $base_material_quantity = null;

    // Only expense_type is required
    if ($expense_type === '') {
        echo json_encode(['success' => false, 'msg' => 'جۆری خەرجی پێویستە']);
        exit;
    }

    // Validations for Payment Type vs Remaining Amount
    $totalRemaining = $remaining_iqd + $remaining_usd;
    
    if ($payment_type === 'قەرز' && $totalRemaining == 0) {
        echo json_encode(['success' => false, 'msg' => 'بۆ مامەڵەی قەرز، نابێت پارەی ماوە سفر بێت!']);
        exit;
    }
    
    if ($payment_type === 'نەقد' && $totalRemaining > 0) {
        echo json_encode(['success' => false, 'msg' => 'بۆ مامەڵەی نەقد، نابێت هیچ پارەیەک بمێنێتەوە!']);
        exit;
    }

    // Check material availability for warehouse material usage
    if ($expense_type === 'بەکارهێنانی کاڵای کۆگا' && $material_id && $material_quantity && $usage_unit_type) {
        // Get current stock quantity and unit info for the material
        $stock_sql = "SELECT quantity, name, unit_type, pieces_per_carton, buckets_per_barrel, liters_per_bucket, liters_per_barrel FROM list_materials WHERE id = ?";
        $stock_stmt = $pdo->prepare($stock_sql);
        $stock_stmt->execute([$material_id]);
        $material_stock = $stock_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$material_stock) {
            echo json_encode(['success' => false, 'msg' => 'کاڵا نەدۆزرایەوە']);
            exit;
        }
        
        $available_quantity = floatval($material_stock['quantity']);
        $required_quantity = floatval($material_quantity);
        $material_unit_type = $material_stock['unit_type'];
        $pieces_per_carton = floatval($material_stock['pieces_per_carton'] ?? 0);
        $buckets_per_barrel = floatval($material_stock['buckets_per_barrel'] ?? 0);
        $liters_per_bucket = floatval($material_stock['liters_per_bucket'] ?? 0);
        $liters_per_barrel = floatval($material_stock['liters_per_barrel'] ?? 0);
        
        // Calculate base_material_quantity based on usage_unit_type
        $base_material_quantity = $required_quantity;
        
        // Convert usage unit to base unit (دانە/لیتر)
        if ($usage_unit_type === 'کارتۆن' && $material_unit_type === 'کارتۆن' && $pieces_per_carton > 0) {
            $base_material_quantity = $required_quantity * $pieces_per_carton;
        } elseif ($usage_unit_type === 'دانە' && $material_unit_type === 'کارتۆن') {
            $base_material_quantity = $required_quantity;
        } elseif ($usage_unit_type === 'بەرمیل' && $material_unit_type === 'بەرمیل' && $liters_per_barrel > 0) {
            $base_material_quantity = $required_quantity * $liters_per_barrel;
        } elseif ($usage_unit_type === 'لیتر' && $material_unit_type === 'بەرمیل') {
            $base_material_quantity = $required_quantity;
        } elseif ($usage_unit_type === 'دەبە' && $material_unit_type === 'بەرمیل' && $liters_per_bucket > 0) {
            $base_material_quantity = $required_quantity * $liters_per_bucket;
        } elseif ($usage_unit_type === 'دەبە' && $material_unit_type === 'دەبە' && $liters_per_bucket > 0) {
            $base_material_quantity = $required_quantity * $liters_per_bucket;
        } elseif ($usage_unit_type === 'لیتر' && $material_unit_type === 'دەبە') {
            $base_material_quantity = $required_quantity;
        } elseif ($usage_unit_type === 'لیتر' && $material_unit_type === 'لیتر') {
            $base_material_quantity = $required_quantity;
        } elseif ($usage_unit_type === 'دانە' && $material_unit_type === 'دانە') {
            $base_material_quantity = $required_quantity;
        } else {
            // Fallback: use the usage quantity as base quantity
            $base_material_quantity = $required_quantity;
        }
        
        // Check stock using base_material_quantity
        if ($available_quantity < $base_material_quantity) {
            echo json_encode([
                'success' => false, 
                'msg' => "بڕی پێویست لە کۆگا نەماوە. بڕی بەردەست: {$available_quantity}، بڕی پێویست: {$base_material_quantity} (بنەڕەتی)"
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
        
        if ($available_gas < $required_gas) {
            echo json_encode([
                'success' => false, 
                'msg' => "بڕی گاز لە تەنکی کەمە. بڕی بەردەست: {$available_gas} لیتر، بڕی پێویست: {$required_gas} لیتر"
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
        amount_iqd, amount_usd, paid_iqd, paid_usd, exchange_rate, remaining_iqd, remaining_usd, date, base_material_quantity, usage_unit_type
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
        $base_material_quantity,
        $usage_unit_type
    ]);
    if ($ok) {
                            // Update person expense if person_id is set
                    if ($person_id) {
                        $update = $pdo->prepare('UPDATE other_expense_persons SET expense_usd = expense_usd + ?, expense_iqd = expense_iqd + ? WHERE id = ?');
                        $update->execute([$remaining_usd, $remaining_iqd, $person_id]);
                    }
                    // Note: Gas consumption is now handled automatically by database triggers
                    // when expense_type = 'بەکارهێنانی گاز' and gas_liters > 0
        $expense_id = $pdo->lastInsertId();

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

        // Create detailed notification
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
            'action_type' => 'other_expense_creation',
            'payment_status' => $payment_type === 'نەقد' ? 'paid' : 'credit',
            'currency_used' => $paid_usd > 0 ? 'USD' : ($paid_iqd > 0 ? 'IQD' : 'none'),
            'total_paid' => $paid_usd + $paid_iqd,
            'remaining_debt' => $remaining_usd + $remaining_iqd,
            'expense_category' => $expense_type
        ];

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'insert',
            'other_expenses',
            $expense_id,
            "خەرجی تر زیادکرا (invoice: $invoice_number, جۆر: $expense_type, کەس: $person_name, کارمەند: $employee_name, سەیارە: $car_name)",
            null, // No old values for insert
            $new_values,
            $additional_info,
            getUserIP()
        );

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
