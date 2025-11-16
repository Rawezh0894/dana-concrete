<?php
require_once '../../config/db_conected.php';

$person_id = intval($_POST['person_id'] ?? 0);
$date = $_POST['date'] ?? date('Y-m-d');
$amount_usd = max(0, floatval($_POST['amount_usd'] ?? 0));
$amount_iqd = max(0, floatval($_POST['amount_iqd'] ?? 0));
$discount_usd = max(0, floatval($_POST['discount_usd'] ?? 0));
$discount_iqd = max(0, floatval($_POST['discount_iqd'] ?? 0));
$note = $_POST['note'] ?? '';

if (
    !$person_id ||
    ($amount_usd <= 0 && $amount_iqd <= 0 && $discount_usd <= 0 && $discount_iqd <= 0)
) {
    echo json_encode(['success' => false, 'msg' => 'زانیاری پێویست نەبوو']);
    exit;
}

try {
    $pdo->beginTransaction();

    // وەرگرتنی قەرزی سەرەتایی
    $stmt = $pdo->prepare("SELECT opening_debt_usd, opening_debt_iqd FROM other_expense_persons WHERE id=? FOR UPDATE");
    $stmt->execute([$person_id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check not exceeding current debt (opening + remaining in other_expenses + remaining in purchase_materials)
    $stmt = $pdo->prepare("SELECT SUM(remaining_usd) as rem_usd, SUM(remaining_iqd) as rem_iqd FROM other_expenses WHERE person_id=? AND payment_type='قەرز'");
    $stmt->execute([$person_id]);
    $rem_expenses = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT SUM(remaining_amount_usd) as rem_usd, SUM(remaining_amount_iqd) as rem_iqd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز'");
    $stmt->execute([$person_id]);
    $rem_purchases = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_usd_available = round(floatval($person['opening_debt_usd']) + floatval($rem_expenses['rem_usd']) + floatval($rem_purchases['rem_usd']), 2);
    $total_iqd_available = round(floatval($person['opening_debt_iqd']) + floatval($rem_expenses['rem_iqd']) + floatval($rem_purchases['rem_iqd']), 2);
    $total_usd_reduction = round($amount_usd + $discount_usd, 2);
    $total_iqd_reduction = round($amount_iqd + $discount_iqd, 2);
    if (
        ($total_usd_reduction > 0 && $total_usd_reduction - $total_usd_available > 0.0001) ||
        ($total_iqd_reduction > 0 && $total_iqd_reduction - $total_iqd_available > 0.0001)
    ) {
        echo json_encode(['success' => false, 'msg' => 'نابێت بڕی پارەی گەرەوا زیاتر بێت لە بڕی قەرز!']);
        $pdo->rollBack();
        exit;
    }

    $remain_usd = $total_usd_reduction;
    $remain_iqd = $total_iqd_reduction;

    $deduct_opening_usd = 0;
    $deduct_opening_iqd = 0;
    $deduct_expenses_usd = 0;
    $deduct_expenses_iqd = 0;
    $deduct_purchases_usd = 0;
    $deduct_purchases_iqd = 0;

    // 1. سەرەتا opening_debt_usd کەم بکە
    $opening_usd = floatval($person['opening_debt_usd']);
    $deduct_opening_usd = min($opening_usd, $remain_usd);
    if ($deduct_opening_usd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET opening_debt_usd = opening_debt_usd - ? WHERE id=?")->execute([$deduct_opening_usd, $person_id]);
        $remain_usd -= $deduct_opening_usd;
    }
    
    // 2. Calculate total remaining for expenses and purchases to distribute proportionally
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(remaining_usd), 0) as total FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_usd > 0");
    $stmt->execute([$person_id]);
    $total_expenses_usd = floatval($stmt->fetchColumn());
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(remaining_amount_usd), 0) as total FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_usd > 0");
    $stmt->execute([$person_id]);
    $total_purchases_usd = floatval($stmt->fetchColumn());
    
    $total_debt_usd = $total_expenses_usd + $total_purchases_usd;
    
    // 3. Distribute payment proportionally between expenses and purchases
    if ($remain_usd > 0 && $total_debt_usd > 0) {
        // Calculate proportional amounts
        $expenses_portion = $total_expenses_usd > 0 ? ($remain_usd * $total_expenses_usd / $total_debt_usd) : 0;
        $purchases_portion = $total_purchases_usd > 0 ? ($remain_usd * $total_purchases_usd / $total_debt_usd) : 0;
        
        // Process expenses (FIFO)
        if ($expenses_portion > 0) {
            $stmt = $pdo->prepare("SELECT id, remaining_usd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_usd > 0 ORDER BY date ASC, id ASC FOR UPDATE");
            $stmt->execute([$person_id]);
            $expenses_remaining = $expenses_portion;
            foreach ($stmt as $row) {
                if ($expenses_remaining <= 0) break;
                $to_deduct = min(floatval($row['remaining_usd']), $expenses_remaining);
                if ($to_deduct <= 0) continue;
                $pdo->prepare("UPDATE other_expenses SET remaining_usd = remaining_usd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                $expenses_remaining -= $to_deduct;
                $deduct_expenses_usd += $to_deduct;
                $remain_usd -= $to_deduct;
            }
        }
        
        // Process purchases (FIFO)
        if ($purchases_portion > 0) {
            $stmt = $pdo->prepare("SELECT id, remaining_amount_usd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_usd > 0 ORDER BY purchase_date ASC, id ASC FOR UPDATE");
            $stmt->execute([$person_id]);
            $purchases_remaining = $purchases_portion;
            foreach ($stmt as $row) {
                if ($purchases_remaining <= 0) break;
                $to_deduct = min(floatval($row['remaining_amount_usd']), $purchases_remaining);
                if ($to_deduct <= 0) continue;
                $pdo->prepare("UPDATE purchase_materials SET remaining_amount_usd = remaining_amount_usd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                $purchases_remaining -= $to_deduct;
                $deduct_purchases_usd += $to_deduct;
                $remain_usd -= $to_deduct;
            }
        }
        
        // If there's any remainder after proportional distribution, apply it FIFO to both
        if ($remain_usd > 0) {
            // First try expenses
            $stmt = $pdo->prepare("SELECT id, remaining_usd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_usd > 0 ORDER BY date ASC, id ASC FOR UPDATE");
            $stmt->execute([$person_id]);
            foreach ($stmt as $row) {
                if ($remain_usd <= 0) break;
                $to_deduct = min(floatval($row['remaining_usd']), $remain_usd);
                if ($to_deduct <= 0) continue;
                $pdo->prepare("UPDATE other_expenses SET remaining_usd = remaining_usd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                $remain_usd -= $to_deduct;
                $deduct_expenses_usd += $to_deduct;
            }
            
            // Then purchases
            if ($remain_usd > 0) {
                $stmt = $pdo->prepare("SELECT id, remaining_amount_usd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_usd > 0 ORDER BY purchase_date ASC, id ASC FOR UPDATE");
                $stmt->execute([$person_id]);
                foreach ($stmt as $row) {
                    if ($remain_usd <= 0) break;
                    $to_deduct = min(floatval($row['remaining_amount_usd']), $remain_usd);
                    if ($to_deduct <= 0) continue;
                    $pdo->prepare("UPDATE purchase_materials SET remaining_amount_usd = remaining_amount_usd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                    $remain_usd -= $to_deduct;
                    $deduct_purchases_usd += $to_deduct;
                }
            }
        }
    } else {
        // Fallback to old FIFO method if no debt exists
        // 2. FIFO لە other_expenses.remaining_usd
        if ($remain_usd > 0) {
            $stmt = $pdo->prepare("SELECT id, remaining_usd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_usd > 0 ORDER BY date ASC, id ASC FOR UPDATE");
            $stmt->execute([$person_id]);
            foreach ($stmt as $row) {
                if ($remain_usd <= 0) break;
                $to_deduct = min(floatval($row['remaining_usd']), $remain_usd);
                if ($to_deduct <= 0) continue;
                $pdo->prepare("UPDATE other_expenses SET remaining_usd = remaining_usd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                $remain_usd -= $to_deduct;
                $deduct_expenses_usd += $to_deduct;
            }
        }
        
        // 3. FIFO لە purchase_materials.remaining_amount_usd
        if ($remain_usd > 0) {
            $stmt = $pdo->prepare("SELECT id, remaining_amount_usd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_usd > 0 ORDER BY purchase_date ASC, id ASC FOR UPDATE");
            $stmt->execute([$person_id]);
            foreach ($stmt as $row) {
                if ($remain_usd <= 0) break;
                $to_deduct = min(floatval($row['remaining_amount_usd']), $remain_usd);
                if ($to_deduct <= 0) continue;
                $pdo->prepare("UPDATE purchase_materials SET remaining_amount_usd = remaining_amount_usd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                $remain_usd -= $to_deduct;
                $deduct_purchases_usd += $to_deduct;
            }
        }
    }

    // IQD - Same proportional distribution logic
    $opening_iqd = floatval($person['opening_debt_iqd']);
    $deduct_opening_iqd = min($opening_iqd, $remain_iqd);
    if ($deduct_opening_iqd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET opening_debt_iqd = opening_debt_iqd - ? WHERE id=?")->execute([$deduct_opening_iqd, $person_id]);
        $remain_iqd -= $deduct_opening_iqd;
    }
    
    // Calculate total remaining for expenses and purchases to distribute proportionally
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(remaining_iqd), 0) as total FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_iqd > 0");
    $stmt->execute([$person_id]);
    $total_expenses_iqd = floatval($stmt->fetchColumn());
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(remaining_amount_iqd), 0) as total FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_iqd > 0");
    $stmt->execute([$person_id]);
    $total_purchases_iqd = floatval($stmt->fetchColumn());
    
    $total_debt_iqd = $total_expenses_iqd + $total_purchases_iqd;
    
    // Distribute payment proportionally between expenses and purchases
    if ($remain_iqd > 0 && $total_debt_iqd > 0) {
        // Calculate proportional amounts
        $expenses_portion = $total_expenses_iqd > 0 ? ($remain_iqd * $total_expenses_iqd / $total_debt_iqd) : 0;
        $purchases_portion = $total_purchases_iqd > 0 ? ($remain_iqd * $total_purchases_iqd / $total_debt_iqd) : 0;
        
        // Process expenses (FIFO)
        if ($expenses_portion > 0) {
            $stmt = $pdo->prepare("SELECT id, remaining_iqd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_iqd > 0 ORDER BY date ASC, id ASC FOR UPDATE");
            $stmt->execute([$person_id]);
            $expenses_remaining = $expenses_portion;
            foreach ($stmt as $row) {
                if ($expenses_remaining <= 0) break;
                $to_deduct = min(floatval($row['remaining_iqd']), $expenses_remaining);
                if ($to_deduct <= 0) continue;
                $pdo->prepare("UPDATE other_expenses SET remaining_iqd = remaining_iqd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                $expenses_remaining -= $to_deduct;
                $deduct_expenses_iqd += $to_deduct;
                $remain_iqd -= $to_deduct;
            }
        }
        
        // Process purchases (FIFO)
        if ($purchases_portion > 0) {
            $stmt = $pdo->prepare("SELECT id, remaining_amount_iqd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_iqd > 0 ORDER BY purchase_date ASC, id ASC FOR UPDATE");
            $stmt->execute([$person_id]);
            $purchases_remaining = $purchases_portion;
            foreach ($stmt as $row) {
                if ($purchases_remaining <= 0) break;
                $to_deduct = min(floatval($row['remaining_amount_iqd']), $purchases_remaining);
                if ($to_deduct <= 0) continue;
                $pdo->prepare("UPDATE purchase_materials SET remaining_amount_iqd = remaining_amount_iqd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                $purchases_remaining -= $to_deduct;
                $deduct_purchases_iqd += $to_deduct;
                $remain_iqd -= $to_deduct;
            }
        }
        
        // If there's any remainder after proportional distribution, apply it FIFO to both
        if ($remain_iqd > 0) {
            // First try expenses
            $stmt = $pdo->prepare("SELECT id, remaining_iqd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_iqd > 0 ORDER BY date ASC, id ASC FOR UPDATE");
            $stmt->execute([$person_id]);
            foreach ($stmt as $row) {
                if ($remain_iqd <= 0) break;
                $to_deduct = min(floatval($row['remaining_iqd']), $remain_iqd);
                if ($to_deduct <= 0) continue;
                $pdo->prepare("UPDATE other_expenses SET remaining_iqd = remaining_iqd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                $remain_iqd -= $to_deduct;
                $deduct_expenses_iqd += $to_deduct;
            }
            
            // Then purchases
            if ($remain_iqd > 0) {
                $stmt = $pdo->prepare("SELECT id, remaining_amount_iqd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_iqd > 0 ORDER BY purchase_date ASC, id ASC FOR UPDATE");
                $stmt->execute([$person_id]);
                foreach ($stmt as $row) {
                    if ($remain_iqd <= 0) break;
                    $to_deduct = min(floatval($row['remaining_amount_iqd']), $remain_iqd);
                    if ($to_deduct <= 0) continue;
                    $pdo->prepare("UPDATE purchase_materials SET remaining_amount_iqd = remaining_amount_iqd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                    $remain_iqd -= $to_deduct;
                    $deduct_purchases_iqd += $to_deduct;
                }
            }
        }
    } else {
        // Fallback to old FIFO method if no debt exists
        if ($remain_iqd > 0) {
            $stmt = $pdo->prepare("SELECT id, remaining_iqd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_iqd > 0 ORDER BY date ASC, id ASC FOR UPDATE");
            $stmt->execute([$person_id]);
            foreach ($stmt as $row) {
                if ($remain_iqd <= 0) break;
                $to_deduct = min(floatval($row['remaining_iqd']), $remain_iqd);
                if ($to_deduct <= 0) continue;
                $pdo->prepare("UPDATE other_expenses SET remaining_iqd = remaining_iqd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                $remain_iqd -= $to_deduct;
                $deduct_expenses_iqd += $to_deduct;
            }
        }
        
        // FIFO لە purchase_materials.remaining_amount_iqd
        if ($remain_iqd > 0) {
            $stmt = $pdo->prepare("SELECT id, remaining_amount_iqd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_iqd > 0 ORDER BY purchase_date ASC, id ASC FOR UPDATE");
            $stmt->execute([$person_id]);
            foreach ($stmt as $row) {
                if ($remain_iqd <= 0) break;
                $to_deduct = min(floatval($row['remaining_amount_iqd']), $remain_iqd);
                if ($to_deduct <= 0) continue;
                $pdo->prepare("UPDATE purchase_materials SET remaining_amount_iqd = remaining_amount_iqd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
                $remain_iqd -= $to_deduct;
                $deduct_purchases_iqd += $to_deduct;
            }
        }
    }

    // Track how much was paid from other_expenses for summary update
    if ($deduct_expenses_usd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET expense_usd = GREATEST(expense_usd - ?, 0) WHERE id=?")->execute([$deduct_expenses_usd, $person_id]);
    }
    if ($deduct_expenses_iqd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET expense_iqd = GREATEST(expense_iqd - ?, 0) WHERE id=?")->execute([$deduct_expenses_iqd, $person_id]);
    }

    // تۆمارکردنی مامەڵەکە
    $stmt = $pdo->prepare("INSERT INTO person_other_expenses_debt_payments (person_id, date, amount_usd, amount_iqd, discount_usd, discount_iqd, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$person_id, $date, $amount_usd, $amount_iqd, $discount_usd, $discount_iqd, $note]);
    $inserted_id = $pdo->lastInsertId();
    require_once __DIR__ . '/../../includes/notify.php';
    notify('insert', 'person_other_expenses_debt_payments', $inserted_id, 'پارەدان بۆ قەرزی کەسانی تر زیادکرا (کەس: ' . $person_id . ')');
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}


