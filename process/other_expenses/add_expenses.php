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
        
        $person_id = (!empty($_POST['person_id']) && is_numeric($_POST['person_id'])) ? $_POST['person_id'] : null;
        $employee_id = (!empty($_POST['employee_id'])) ? $_POST['employee_id'] : null;
        $car_id = (!empty($_POST['car_id'])) ? $_POST['car_id'] : null;
        $gas_liters = isset($_POST['gas_liters']) ? floatval($_POST['gas_liters']) : null;
        
        $expense_type = $_POST['expense_type'] ?? 'خەرجی تر';
        if (!in_array($expense_type, ['بەکارهێنانی کاڵای کۆگا', 'بەکارهێنانی گاز', 'خەرجی تر', 'خواردنگە', 'ئۆفیس', 'کڕینی کاڵا بۆ کۆگا'])) {
            $expense_type = 'خەرجی تر';
        }
        
        $material_id = (!empty($_POST['material_id'])) ? $_POST['material_id'] : null;
        $material_quantity = isset($_POST['material_quantity']) ? floatval($_POST['material_quantity']) : null;
        $usage_unit_type = $_POST['usage_unit_type'] ?? null;
        if ($usage_unit_type === '' || $usage_unit_type === 'null') $usage_unit_type = null;

        $material_purchase_price_iqd = !empty($_POST['material_purchase_price_iqd']) ? floatval($_POST['material_purchase_price_iqd']) : 0;
        $material_purchase_price_usd = !empty($_POST['material_purchase_price_usd']) ? floatval($_POST['material_purchase_price_usd']) : 0;
        $material_total_cost = !empty($_POST['material_total_cost']) ? floatval($_POST['material_total_cost']) : 0;

        $gas_purchase_price_input = !empty($_POST['gas_purchase_price_input']) ? floatval($_POST['gas_purchase_price_input']) : 0;
        $gas_total_cost = !empty($_POST['gas_total_cost']) ? floatval($_POST['gas_total_cost']) : 0;

        $payment_type = $_POST['payment_type'] ?? 'نەقد';
        $currency_type = $_POST['currency_type'] ?? 'دینار';
        $invoice_number = $_POST['invoice_number'] ?? '';
        
        $amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
        $amount_usd = floatval($_POST['amount_usd'] ?? 0);
        $paid_iqd = floatval($_POST['paid_iqd'] ?? 0);
        $paid_usd = floatval($_POST['paid_usd'] ?? 0);
        $exchange_rate = floatval($_POST['exchange_rate'] ?? 139250);
        $remaining_iqd = floatval($_POST['remaining_iqd'] ?? 0);
        $remaining_usd = floatval($_POST['remaining_usd'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');

        // Validation
        if ($expense_type === '') {
            echo json_encode(['success' => false, 'msg' => 'جۆری خەرجی پێویستە']);
            exit;
        }

        $totalRemaining = $remaining_iqd + $remaining_usd;
        if ($payment_type === 'قەرز' && $totalRemaining == 0) {
            echo json_encode(['success' => false, 'msg' => 'بۆ مامەڵەی قەرز، نابێت پارەی ماوە سفر بێت!']);
            exit;
        } 
        if ($payment_type === 'نەقد' && $totalRemaining > 1) { 
             echo json_encode(['success' => false, 'msg' => 'بۆ مامەڵەی نەقد، نابێت هیچ پارەیەک بمێنێتەوە!']);
             exit;
        }

        // Material Lines (Multi-Item)
        $material_lines_json = $_POST['material_lines'] ?? null;
        $material_lines = [];
        if ($material_lines_json && $expense_type === 'بەکارهێنانی کاڵای کۆگا') {
             $decoded = json_decode($material_lines_json, true);
             if (is_array($decoded)) $material_lines = $decoded;
        }

        // Auto Generate Invoice
        if (!empty($material_lines) && empty($invoice_number)) {
            $invoice_number = 'WH-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        }
        if (empty($invoice_number)) {
            echo json_encode(['success' => false, 'msg' => 'تکایە ژمارەی وەسڵ بنووسە']);
            exit;
        }

        // Check Duplicate
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM other_expenses WHERE invoice_number = ? AND date = ?');
        $stmt->execute([$invoice_number, $date]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'msg' => 'ئەم ژمارەی پسوڵەیە پێشتر لەم بەروارەدا تۆمارکراوە!']);
            exit;
        }

        $pdo->beginTransaction();

        // ---------------------------------------------------------
        // CASE 1: MULTI-ITEM WAREHOUSE EXPENSE
        // ---------------------------------------------------------
        if (!empty($material_lines) && $expense_type === 'بەکارهێنانی کاڵای کۆگا') {
            $helper_sql = "SELECT quantity, unit_type, pieces_per_carton, buckets_per_barrel, liters_per_bucket, liters_per_barrel FROM list_materials WHERE id = ?";
            $helper_stmt = $pdo->prepare($helper_sql);
            
            $lines_with_base = [];
            $material_usage_map = [];

            foreach ($material_lines as $line) {
                 $mid = (int) ($line['material_id'] ?? 0);
                 $qty = (float) ($line['material_quantity'] ?? 0);
                 $unit = $line['usage_unit_type'] ?? null;
                 
                 if (!$mid || $qty <= 0) continue;
                 
                 $helper_stmt->execute([$mid]);
                 $mat = $helper_stmt->fetch(PDO::FETCH_ASSOC);
                 
                 if(!$mat) {
                     $pdo->rollBack();
                     echo json_encode(['success' => false, 'msg' => 'کاڵا نەدۆزرایەوە (ID: ' . $mid . ')']);
                     exit;
                 }
                 
                 // Base Quantity
                 $base_qty = $qty;
                 $ut = $mat['unit_type'];
                 $ppc = (float) ($mat['pieces_per_carton'] ?? 0);
                 $lpb = (float) ($mat['liters_per_barrel'] ?? 0);
                 $lpbucket = (float) ($mat['liters_per_bucket'] ?? 0);
                 
                 if ($unit === 'کارتۆن' && $ut === 'کارتۆن' && $ppc > 0) $base_qty = $qty * $ppc;
                 elseif ($unit === 'بەرمیل' && $ut === 'بەرمیل' && $lpb > 0) $base_qty = $qty * $lpb;
                 elseif ($unit === 'دەبە' && ($ut === 'بەرمیل' || $ut === 'دەبە') && $lpbucket > 0) $base_qty = $qty * $lpbucket;
                 
                 // Check aggregated usage
                 if (!isset($material_usage_map[$mid])) $material_usage_map[$mid] = 0;
                 $material_usage_map[$mid] += $base_qty;
                 
                 // Check against current stock
                 if ($mat['quantity'] < $material_usage_map[$mid]) {
                     $pdo->rollBack();
                     echo json_encode([
                        'success' => false,
                        'msg' => "بڕی پێویست لە کۆگا نەماوە (کاڵا ID: {$mid}). بەردەست: {$mat['quantity']}، پێویست: {$material_usage_map[$mid]}"
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
                 echo json_encode(['success' => false, 'msg' => 'تکایە لانیکەم یەک کاڵا زیاد بکە']);
                 exit;
            }

            // Insert MAIN record
            $ins_exp = "INSERT INTO other_expenses (
                purpose, person_id, employee_id, car_id, gas_liters, expense_type, 
                payment_type, currency_type, invoice_number, 
                amount_iqd, amount_usd, paid_iqd, paid_usd, exchange_rate,
                remaining_iqd, remaining_usd, date, is_split_invoice,
                material_id, material_quantity
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NULL, NULL)";
            
            $stmt_exp = $pdo->prepare($ins_exp);
            $stmt_exp->execute([
                $purpose, $person_id, $employee_id, $car_id, $gas_liters, $expense_type,
                $payment_type, $currency_type, $invoice_number,
                $amount_iqd, $amount_usd, $paid_iqd, $paid_usd, $exchange_rate,
                $remaining_iqd, $remaining_usd, $date
            ]);
            $expense_id = $pdo->lastInsertId();

            // Insert LINE ITEMS
            $ins_line = "INSERT INTO expense_line_items (
                expense_id, line_number, car_id, account_assignment, expense_type, material_id, material_quantity,
                usage_unit_type, base_material_quantity, material_purchase_price_iqd, material_purchase_price_usd,
                material_total_cost, amount_iqd, amount_usd
            ) VALUES (?, ?, ?, 'سەیارە', 'بەکارهێنانی کاڵای کۆگا', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_line = $pdo->prepare($ins_line);
            $update_stock = $pdo->prepare("UPDATE list_materials SET quantity = quantity - ? WHERE id = ?");

            foreach ($lines_with_base as $index => $l) {
                $line_num = $index + 1;
                $stmt_line->execute([
                    $expense_id, $line_num, $car_id, $l['material_id'], $l['material_quantity'], $l['usage_unit_type'],
                    $l['base_material_quantity'], $l['material_purchase_price_iqd'], $l['material_purchase_price_usd'],
                    $l['material_total_cost'], 
                    ($currency_type !== 'دۆلار' ? $l['material_total_cost'] : 0), 
                    ($currency_type === 'دۆلار' ? $l['material_total_cost'] : 0)
                ]);
                $update_stock->execute([$l['base_material_quantity'], $l['material_id']]);
            }
            
            // Update Person
            if ($person_id) {
                $update = $pdo->prepare('UPDATE other_expense_persons SET expense_usd = expense_usd + ?, expense_iqd = expense_iqd + ? WHERE id = ?');
                $update->execute([$remaining_usd, $remaining_iqd, $person_id]);
            }
            
            // Notification
            $c_name = 'Unknown';
            if($car_id) {
                $stmt = $pdo->prepare("SELECT name FROM cars WHERE id=?");
                $stmt->execute([$car_id]);
                $c_name = $stmt->fetchColumn() ?: 'Unknown';
            }
            createDetailedNotification($pdo, $_SESSION['user_id'], 'insert', 'other_expenses', $expense_id, 
                "خەرجی تر زیادکرا (چەند کاڵا, $invoice_number)", 
                null, 
                ['invoice' => $invoice_number, 'lines' => count($lines_with_base), 'car' => $c_name], 
                [], getUserIP()
            );

            $pdo->commit();
            echo json_encode(['success' => true]);
            exit;
        }

        // ---------------------------------------------------------
        // CASE 2: SINGLE/LEGACY/SPLIT EXPENSES
        // ---------------------------------------------------------
        $invoice_splits_json = $_POST['invoice_splits'] ?? null;
        $splits = [];
        if ($invoice_splits_json) {
            $splits = json_decode($invoice_splits_json, true);
        }

        $insert_objects = [];
        if (!empty($splits)) {
             foreach ($splits as $split) {
                $split_row_type = $split['type'];
                $split_car_id = ($split_row_type === 'car') ? $split['car_id'] : null;
                $split_material_id = ($split_row_type === 'stock') ? $split['material_id'] : null;
                $split_material_quantity = ($split_row_type === 'stock') ? floatval($split['quantity']) : null;
                $split_usage_unit_type = ($split_row_type === 'stock') ? ($split['usage_unit_type'] ?? 'دانە') : null;
                $split_amount_iqd = floatval($split['amount_iqd']);
                $split_amount_usd = floatval($split['amount_usd']);
                
                $split_paid_iqd = 0;
                $split_paid_usd = 0;
                // Proportional Payment
                if ($amount_iqd > 0) $split_paid_iqd = ($split_amount_iqd / $amount_iqd) * $paid_iqd;
                if ($amount_usd > 0) $split_paid_usd = ($split_amount_usd / $amount_usd) * $paid_usd;
                
                $insert_objects[] = [
                    'car_id' => $split_car_id,
                    'material_id' => $split_material_id,
                    'material_quantity' => $split_material_quantity,
                    'usage_unit_type' => $split_usage_unit_type,
                    'amount_iqd' => $split_amount_iqd,
                    'amount_usd' => $split_amount_usd,
                    'paid_iqd' => $split_paid_iqd,
                    'paid_usd' => $split_paid_usd,
                    'remaining_iqd' => $split_amount_iqd - $split_paid_iqd,
                    'remaining_usd' => $split_amount_usd - $split_paid_usd,
                    'expense_type' => ($split_row_type === 'stock') ? 'کڕینی کاڵا بۆ کۆگا' : $expense_type
                ];
            }
        } else {
            // Standard Single Entry
            $insert_objects[] = [
                'car_id' => $car_id,
                'material_id' => $material_id,
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
            purpose, person_id, employee_id, car_id, gas_liters, expense_type, material_id, material_quantity, 
            material_purchase_price_iqd, material_purchase_price_usd, material_total_cost, gas_purchase_price_input, gas_total_cost, 
            payment_type, currency_type, invoice_number,
            amount_iqd, amount_usd, paid_iqd, paid_usd, exchange_rate, remaining_iqd, remaining_usd, date, base_material_quantity, usage_unit_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        foreach ($insert_objects as $obj) {
            $current_base_qty = $obj['material_quantity'];
            
            // Calculate Base Qty for Single Item
             if (($obj['expense_type'] === 'بەکارهێنانی کاڵای کۆگا' || $obj['expense_type'] === 'کڕینی کاڵا بۆ کۆگا') && $obj['material_id'] && $obj['material_quantity'] > 0) {
                 $m_sql = "SELECT unit_type, pieces_per_carton, buckets_per_barrel, liters_per_bucket, liters_per_barrel, quantity FROM list_materials WHERE id = ?";
                 $m_stmt = $pdo->prepare($m_sql);
                 $m_stmt->execute([$obj['material_id']]);
                 $m_data = $m_stmt->fetch(PDO::FETCH_ASSOC);
                 if ($m_data) {
                      $u = $obj['usage_unit_type'];
                      $mu = $m_data['unit_type'];
                      if ($u === 'کارتۆن' && $mu === 'کارتۆن') $current_base_qty *= ($m_data['pieces_per_carton'] ?: 1);
                      elseif ($u === 'بەرمیل' && $mu === 'بەرمیل') $current_base_qty *= ($m_data['liters_per_barrel'] ?: 1);
                      elseif ($u === 'دەبە' && ($mu === 'بەرمیل' || $mu === 'دەبە')) $current_base_qty *= ($m_data['liters_per_bucket'] ?: 1);
                      
                      // Check Stock for Usage
                      if ($obj['expense_type'] === 'بەکارهێنانی کاڵای کۆگا') {
                          if ($m_data['quantity'] < $current_base_qty) {
                              $pdo->rollBack();
                              echo json_encode(['success' => false, 'msg' => "بڕی پێویست لە کۆگا نەماوە"]);
                              exit;
                          }
                      }
                 }
            }

            $stmt->execute([
                $purpose, $person_id, $employee_id, $obj['car_id'], $gas_liters, $obj['expense_type'],
                $obj['material_id'], $obj['material_quantity'], $material_purchase_price_iqd, $material_purchase_price_usd, $material_total_cost,
                $gas_purchase_price_input, $gas_total_cost, $payment_type, $currency_type, $invoice_number,
                $obj['amount_iqd'], $obj['amount_usd'], $obj['paid_iqd'], $obj['paid_usd'], $exchange_rate,
                $obj['remaining_iqd'], $obj['remaining_usd'], $date, $current_base_qty, $obj['usage_unit_type']
            ]);
            
            $eid = $pdo->lastInsertId();

            // Stock Updates
            if ($obj['expense_type'] === 'کڕینی کاڵا بۆ کۆگا' && $obj['material_id']) {
                 $pdo->prepare("UPDATE list_materials SET quantity = quantity + ? WHERE id = ?")->execute([$current_base_qty, $obj['material_id']]);
            }
            if ($obj['expense_type'] === 'بەکارهێنانی کاڵای کۆگا' && $obj['material_id']) {
                 $pdo->prepare("UPDATE list_materials SET quantity = quantity - ? WHERE id = ?")->execute([$current_base_qty, $obj['material_id']]);
            }
            
            // Person Balance
            if ($person_id) {
                 $pdo->prepare('UPDATE other_expense_persons SET expense_usd = expense_usd + ?, expense_iqd = expense_iqd + ? WHERE id = ?')
                     ->execute([$obj['remaining_usd'], $obj['remaining_iqd'], $person_id]);
            }
            
            createDetailedNotification($pdo, $_SESSION['user_id'], 'insert', 'other_expenses', $eid, "خەرجی تر زیادکرا ($invoice_number)", null, [], [], getUserIP());
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
        exit;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵە: ' . $e->getMessage()]);
}
?>
