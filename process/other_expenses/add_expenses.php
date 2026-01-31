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
    if (!in_array($expense_type, ['بەکارهێنانی کاڵای کۆگا', 'بەکارهێنانی گاز', 'خەرجی تر', 'خواردنگە', 'ئۆفیس', 'کڕینی کاڵا بۆ کۆگا'])) {
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

    // material_lines: چەند کاڵا بۆ هەمان سەیارە (بەکارهێنانی کاڵای کۆگا)
    $material_lines_json = $_POST['material_lines'] ?? null;
    $material_lines = [];
    if ($material_lines_json && $expense_type === 'بەکارهێنانی کاڵای کۆگا') {
        $material_lines = json_decode($material_lines_json, true);
        if (!is_array($material_lines)) {
            $material_lines = [];
        }
    }

    // Check for invoice_number (with material_lines allow auto-generate)
    if (empty($material_lines) && empty($invoice_number)) {
        echo json_encode(['success' => false, 'msg' => 'تکایە ژمارەی وەسڵ بنووسە']);
        exit;
    }
    if (!empty($material_lines) && empty($invoice_number)) {
        $invoice_number = 'WH-' . str_replace('-', '', $date) . '-' . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    // Check for duplicate invoice_number on the same date
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM other_expenses WHERE invoice_number = ? AND date = ?');
    $stmt->execute([$invoice_number, $date]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'msg' => 'ئەم ژمارەی پسوڵەیە پێشتر لەم بەروارەدا تۆمارکراوە!']);
        exit;
    }

    $invoice_splits_json = $_POST['invoice_splits'] ?? null;
    $splits = [];
    if ($invoice_splits_json) {
        $splits = json_decode($invoice_splits_json, true);
    }

    $pdo->beginTransaction();

    // ----- چەند کاڵا بۆ هەمان سەیارە (material_lines) -----
    if (!empty($material_lines) && $expense_type === 'بەکارهێنانی کاڵای کۆگا' && $car_id) {
        $helper_sql = "SELECT unit_type, pieces_per_carton, buckets_per_barrel, liters_per_bucket, liters_per_barrel, quantity FROM list_materials WHERE id = ?";
        $helper_stmt = $pdo->prepare($helper_sql);

        $lines_with_base = [];
        foreach ($material_lines as $line) {
            $mid = (int) ($line['material_id'] ?? 0);
            $qty = (float) ($line['material_quantity'] ?? 0);
            $unit = $line['usage_unit_type'] ?? null;
            if (!$mid || $qty <= 0) {
                continue;
            }
            $helper_stmt->execute([$mid]);
            $mat = $helper_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$mat) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'msg' => 'کاڵا نەدۆزرایەوە (ID: ' . $mid . ')']);
                exit;
            }
            $available = (float) $mat['quantity'];
            $base_qty = $qty;
            $ut = $mat['unit_type'];
            $ppc = (float) ($mat['pieces_per_carton'] ?? 0);
            $lpb = (float) ($mat['liters_per_barrel'] ?? 0);
            $lpbucket = (float) ($mat['liters_per_bucket'] ?? 0);
            if ($unit === 'کارتۆن' && $ut === 'کارتۆن' && $ppc > 0) {
                $base_qty = $qty * $ppc;
            } elseif ($unit === 'بەرمیل' && $ut === 'بەرمیل' && $lpb > 0) {
                $base_qty = $qty * $lpb;
            } elseif ($unit === 'دەبە' && ($ut === 'بەرمیل' || $ut === 'دەبە') && $lpbucket > 0) {
                $base_qty = $qty * $lpbucket;
            }
            if ($available < $base_qty) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'msg' => "بڕی پێویست لە کۆگا نەماوە (کاڵا ID: {$mid}). بەردەست: {$available}، پێویست: {$base_qty}"
                ]);
                exit;
            }
            $lines_with_base[] = [
                'material_id' => $mid,
                'material_quantity' => $qty,
                'usage_unit_type' => $unit,
                'material_purchase_price_iqd' => (float) ($line['material_purchase_price_iqd'] ?? 0),
                'material_purchase_price_usd' => (float) ($line['material_purchase_price_usd'] ?? 0),
                'material_total_cost' => (float) ($line['material_total_cost'] ?? 0),
                'base_material_quantity' => $base_qty
            ];
        }

        if (empty($lines_with_base)) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'msg' => 'تکایە لانیکەم یەک کاڵا بە بڕی دروست زیاد بکە']);
            exit;
        }

        $total_iqd = 0;
        $total_usd = 0;
        foreach ($lines_with_base as $l) {
            $total_iqd += $l['material_total_cost'];
            $total_usd += $l['material_total_cost'];
        }
        if ($currency_type === 'دۆلار') {
            $amount_iqd_multi = 0;
            $amount_usd_multi = $total_usd;
        } else {
            $amount_iqd_multi = $total_iqd;
            $amount_usd_multi = 0;
        }

        $ins_exp = "INSERT INTO other_expenses (
            purpose, person_id, employee_id, car_id, gas_liters, expense_type, material_id, material_quantity,
            material_purchase_price_iqd, material_purchase_price_usd, material_total_cost, gas_purchase_price_input, gas_total_cost,
            payment_type, currency_type, invoice_number, amount_iqd, amount_usd, paid_iqd, paid_usd, exchange_rate,
            remaining_iqd, remaining_usd, date, base_material_quantity, usage_unit_type, is_split_invoice
        ) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, 1)";
        $stmt_exp = $pdo->prepare($ins_exp);
        $stmt_exp->execute([
            $purpose, $person_id, $employee_id ?: null, $car_id, $gas_liters, $expense_type,
            $gas_purchase_price_input, $gas_total_cost, $payment_type, $currency_type, $invoice_number,
            $amount_iqd_multi, $amount_usd_multi, $amount_iqd_multi, $amount_usd_multi, $exchange_rate, 0, 0, $date
        ]);
        $expense_id = (int) $pdo->lastInsertId();

        $ins_line = "INSERT INTO expense_line_items (
            expense_id, line_number, car_id, account_assignment, expense_type, material_id, material_quantity,
            usage_unit_type, base_material_quantity, material_purchase_price_iqd, material_purchase_price_usd,
            material_total_cost, amount_iqd, amount_usd
        ) VALUES (?, ?, ?, 'سەیارە', 'بەکارهێنانی کاڵای کۆگا', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_line = $pdo->prepare($ins_line);
        $line_num = 0;
        foreach ($lines_with_base as $l) {
            $line_num++;
            $stmt_line->execute([
                $expense_id, $line_num, $car_id, $l['material_id'], $l['material_quantity'], $l['usage_unit_type'],
                $l['base_material_quantity'], $l['material_purchase_price_iqd'], $l['material_purchase_price_usd'],
                $l['material_total_cost'], $l['material_total_cost'], 0
            ]);
            $update_stock = $pdo->prepare("UPDATE list_materials SET quantity = quantity - ? WHERE id = ?");
            $update_stock->execute([$l['base_material_quantity'], $l['material_id']]);
        }

        if ($person_id) {
            $update = $pdo->prepare('UPDATE other_expense_persons SET expense_usd = expense_usd + ?, expense_iqd = expense_iqd + ? WHERE id = ?');
            $update->execute([0, 0, $person_id]);
        }

        $c_stmt = $pdo->prepare("SELECT name FROM cars WHERE id = ?");
        $c_stmt->execute([$car_id]);
        $car_name = $c_stmt->fetch()['name'] ?? 'هیچ سەیارەیەک نییە';
        $new_values = [
            'person_id' => $person_id,
            'employee_id' => $employee_id,
            'car_id' => $car_id,
            'car_name' => $car_name,
            'expense_type' => $expense_type,
            'amount_iqd' => $amount_iqd_multi,
            'amount_usd' => $amount_usd_multi,
            'invoice_number' => $invoice_number,
            'date' => $date,
            'material_lines_count' => count($lines_with_base)
        ];
        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'insert',
            'other_expenses',
            $expense_id,
            "خەرجی تر زیادکرا (چەند کاڵا بۆ سەیارە, invoice: $invoice_number)",
            null,
            $new_values,
            [],
            getUserIP()
        );

        $pdo->commit();
        echo json_encode(['success' => true]);
        exit;
    }

    // Prepare objects for multi-insertion
    $insert_objects = [];

    if (!empty($splits)) {
        // Mixed split mode
        foreach ($splits as $split) {
            $split_row_type = $split['type']; // 'car' or 'stock'
            $split_car_id = ($split_row_type === 'car') ? $split['car_id'] : null;
            $split_material_id = ($split_row_type === 'stock') ? $split['material_id'] : null;
            $split_material_quantity = ($split_row_type === 'stock') ? floatval($split['quantity']) : null;
            $split_usage_unit_type = ($split_row_type === 'stock') ? ($split['usage_unit_type'] ?? 'دانە') : null;
            $split_amount_iqd = floatval($split['amount_iqd']);
            $split_amount_usd = floatval($split['amount_usd']);
            
            // Proportional distribution of payment
            $split_paid_iqd = 0;
            if ($amount_iqd > 0) {
                $split_paid_iqd = ($split_amount_iqd / $amount_iqd) * $paid_iqd;
            }
            $split_paid_usd = 0;
            if ($amount_usd > 0) {
                $split_paid_usd = ($split_amount_usd / $amount_usd) * $paid_usd;
            }
            
            $split_remaining_iqd = $split_amount_iqd - $split_paid_iqd;
            $split_remaining_usd = $split_amount_usd - $split_paid_usd;

            $insert_objects[] = [
                'type' => $split_row_type,
                'car_id' => $split_car_id,
                'material_id' => $split_material_id,
                'material_quantity' => $split_material_quantity,
                'usage_unit_type' => $split_usage_unit_type,
                'amount_iqd' => $split_amount_iqd,
                'amount_usd' => $split_amount_usd,
                'paid_iqd' => $split_paid_iqd,
                'paid_usd' => $split_paid_usd,
                'remaining_iqd' => $split_remaining_iqd,
                'remaining_usd' => $split_remaining_usd,
                'expense_type' => ($split_row_type === 'stock') ? 'کڕینی کاڵا بۆ کۆگا' : $expense_type
            ];
        }
    } else {
        // Single mode (could be car or material usage or general)
        $insert_objects[] = [
            'type' => ($car_id ? 'car' : ($material_id ? 'stock' : 'general')),
            'car_id' => $car_id ?: null,
            'material_id' => $material_id ?: null,
            'material_quantity' => $material_quantity,
            'usage_unit_type' => $usage_unit_type,
            'amount_iqd' => $amount_iqd,
            'amount_usd' => $amount_usd,
            'paid_iqd' => $paid_iqd,
            'paid_usd' => $paid_usd,
            'remaining_iqd' => $remaining_iqd,
            'remaining_usd' => $remaining_usd,
            'expense_type' => $expense_type
        ];
    }

    $sql = "INSERT INTO other_expenses (
        purpose, person_id, employee_id, car_id, gas_liters, expense_type, material_id, material_quantity, material_purchase_price_iqd, material_purchase_price_usd, material_total_cost, gas_purchase_price_input, gas_total_cost, payment_type, currency_type, invoice_number,
        amount_iqd, amount_usd, paid_iqd, paid_usd, exchange_rate, remaining_iqd, remaining_usd, date, base_material_quantity, usage_unit_type
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    foreach ($insert_objects as $obj) {
        // Calculate base_material_quantity if it's a stock-related row
        $current_base_qty = $obj['material_quantity'];
        if ($obj['material_id'] && $obj['material_quantity'] > 0 && $obj['usage_unit_type']) {
            $m_sql = "SELECT unit_type, pieces_per_carton, buckets_per_barrel, liters_per_bucket, liters_per_barrel FROM list_materials WHERE id = ?";
            $m_stmt = $pdo->prepare($m_sql);
            $m_stmt->execute([$obj['material_id']]);
            $m_data = $m_stmt->fetch(PDO::FETCH_ASSOC);

            if ($m_data) {
                $pieces_per_carton = floatval($m_data['pieces_per_carton'] ?? 0);
                $liters_per_barrel = floatval($m_data['liters_per_barrel'] ?? 0);
                $liters_per_bucket = floatval($m_data['liters_per_bucket'] ?? 0);
                $mat_unit = $m_data['unit_type'];
                $row_unit = $obj['usage_unit_type'];
                $qty = $obj['material_quantity'];

                if ($row_unit === 'کارتۆن' && $mat_unit === 'کارتۆن' && $pieces_per_carton > 0) {
                    $current_base_qty = $qty * $pieces_per_carton;
                } elseif ($row_unit === 'بەرمیل' && $mat_unit === 'بەرمیل' && $liters_per_barrel > 0) {
                    $current_base_qty = $qty * $liters_per_barrel;
                } elseif ($row_unit === 'دەبە' && ($mat_unit === 'بەرمیل' || $mat_unit === 'دەبە') && $liters_per_bucket > 0) {
                    $current_base_qty = $qty * $liters_per_bucket;
                } else {
                    $current_base_qty = $qty;
                }
            }
        }

        $ok = $stmt->execute([
            $purpose,
            $person_id,
            $employee_id ?: null,
            $obj['car_id'],
            $gas_liters,
            $obj['expense_type'],
            $obj['material_id'],
            $obj['material_quantity'],
            $material_purchase_price_iqd,
            $material_purchase_price_usd,
            $material_total_cost,
            $gas_purchase_price_input,
            $gas_total_cost,
            $payment_type,
            $currency_type,
            $invoice_number,
            $obj['amount_iqd'],
            $obj['amount_usd'],
            $obj['paid_iqd'],
            $obj['paid_usd'],
            $exchange_rate,
            $obj['remaining_iqd'],
            $obj['remaining_usd'],
            $date,
            $current_base_qty,
            $obj['usage_unit_type']
        ]);

        if (!$ok) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردن']);
            exit;
        }

        $expense_id = $pdo->lastInsertId();

        // Increment stock if it's a purchase
        if ($obj['expense_type'] === 'کڕینی کاڵا بۆ کۆگا' && $obj['material_id'] && $current_base_qty > 0) {
            $update_stock = $pdo->prepare("UPDATE list_materials SET quantity = quantity + ? WHERE id = ?");
            $update_stock->execute([$current_base_qty, $obj['material_id']]);
        }
        // Deduct stock if it's warehouse material usage (single material)
        if ($obj['expense_type'] === 'بەکارهێنانی کاڵای کۆگا' && $obj['material_id'] && $current_base_qty > 0) {
            $update_stock = $pdo->prepare("UPDATE list_materials SET quantity = quantity - ? WHERE id = ?");
            $update_stock->execute([$current_base_qty, $obj['material_id']]);
        }

        // Update person expense if person_id is set
        if ($person_id) {
            $update = $pdo->prepare('UPDATE other_expense_persons SET expense_usd = expense_usd + ?, expense_iqd = expense_iqd + ? WHERE id = ?');
            $update->execute([$obj['remaining_usd'], $obj['remaining_iqd'], $person_id]);
        }
        
        // Notifications
        $car_name = 'هیچ سەیارەیەک نییە';
        if ($obj['car_id']) {
            $c_stmt = $pdo->prepare("SELECT name FROM cars WHERE id = ?");
            $c_stmt->execute([$obj['car_id']]);
            $car_name = $c_stmt->fetch()['name'] ?? 'Unknown';
        }

        $material_name_split = 'هیچ مادەیەک نییە';
        if ($obj['material_id']) {
            $m_stmt = $pdo->prepare("SELECT name FROM list_materials WHERE id = ?");
            $m_stmt->execute([$obj['material_id']]);
            $material_name_split = $m_stmt->fetch()['name'] ?? 'Unknown';
        }

        // ... (rest of notification logic simplified for brevity or keep as is)
        static $cached_person_name = null;
        static $cached_employee_name = null;
        if ($cached_person_name === null && $person_id) {
            $p_stmt = $pdo->prepare("SELECT name FROM other_expense_persons WHERE id = ?");
            $p_stmt->execute([$person_id]);
            $cached_person_name = $p_stmt->fetch()['name'] ?? 'Unknown';
        }
        if ($cached_employee_name === null && $employee_id) {
            $e_stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
            $e_stmt->execute([$employee_id]);
            $cached_employee_name = $e_stmt->fetch()['name'] ?? 'Unknown';
        }

        $new_values = [
            'person_id' => $person_id,
            'person_name' => $cached_person_name,
            'employee_id' => $employee_id,
            'employee_name' => $cached_employee_name,
            'car_id' => $obj['car_id'],
            'car_name' => $car_name,
            'material_id' => $obj['material_id'],
            'material_name' => $material_name_split,
            'expense_type' => $obj['expense_type'],
            'amount_iqd' => $obj['amount_iqd'],
            'amount_usd' => $obj['amount_usd'],
            'paid_iqd' => $obj['paid_iqd'],
            'paid_usd' => $obj['paid_usd'],
            'remaining_iqd' => $obj['remaining_iqd'],
            'remaining_usd' => $obj['remaining_usd'],
            'invoice_number' => $invoice_number,
            'date' => $date
        ];

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'insert',
            'other_expenses',
            $expense_id,
            "خەرجی تر زیادکرا ({$obj['expense_type']}, invoice: $invoice_number)",
            null,
            $new_values,
            [],
            getUserIP()
        );
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
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
