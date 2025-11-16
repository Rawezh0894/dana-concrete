<?php
require_once '../../config/db_conected.php';

$debt_id = intval($_POST['debt_id'] ?? 0);
$person_id = intval($_POST['person_id'] ?? 0);
$date = $_POST['date'] ?? date('Y-m-d');
$amount_usd = max(0, floatval($_POST['amount_usd'] ?? 0));
$amount_iqd = max(0, floatval($_POST['amount_iqd'] ?? 0));
$discount_usd = max(0, floatval($_POST['discount_usd'] ?? 0));
$discount_iqd = max(0, floatval($_POST['discount_iqd'] ?? 0));
$note = $_POST['note'] ?? '';

if (
    !$debt_id ||
    !$person_id ||
    ($amount_usd <= 0 && $amount_iqd <= 0 && $discount_usd <= 0 && $discount_iqd <= 0)
) {
    echo json_encode(['success' => false, 'msg' => 'زانیاری پێویست نەبوو']);
    exit;
}

try {
    $pdo->beginTransaction();

    // قەرزی کۆن وەرگرەوە
    $stmt = $pdo->prepare("SELECT amount_usd, amount_iqd, discount_usd, discount_iqd FROM person_other_expenses_debt_payments WHERE id=? AND person_id=? FOR UPDATE");
    $stmt->execute([$debt_id, $person_id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$old) {
        throw new Exception('قەرزەکە بوونی نییە!');
    }

    $old_amount_usd = floatval($old['amount_usd']);
    $old_amount_iqd = floatval($old['amount_iqd']);
    $old_discount_usd = floatval($old['discount_usd']);
    $old_discount_iqd = floatval($old['discount_iqd']);
    $old_total_usd = $old_amount_usd + $old_discount_usd;
    $old_total_iqd = $old_amount_iqd + $old_discount_iqd;

    // وەرگرتنی کەس
    $stmt = $pdo->prepare("SELECT opening_debt_usd, opening_debt_iqd, expense_usd, expense_iqd FROM other_expense_persons WHERE id=? FOR UPDATE");
    $stmt->execute([$person_id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$person) {
        throw new Exception('کەس نەدۆزرایەوە');
    }

    /**
     * گەڕاندنەوەی قەرزی کۆن
     */
    $restore_expenses_usd = 0;
    $restore_expenses_iqd = 0;

    $remain_restore_usd = $old_total_usd;
    if ($remain_restore_usd > 0) {
        // Restore purchases first (LIFO)
        $stmt = $pdo->prepare("SELECT id, total_price_usd, remaining_amount_usd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' ORDER BY purchase_date DESC, id DESC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_restore_usd <= 0) break;
            $used = max(0, floatval($row['total_price_usd']) - floatval($row['remaining_amount_usd']));
            if ($used <= 0) continue;
            $to_add = min($used, $remain_restore_usd);
            $pdo->prepare("UPDATE purchase_materials SET remaining_amount_usd = remaining_amount_usd + ? WHERE id=?")->execute([$to_add, $row['id']]);
            $remain_restore_usd -= $to_add;
        }
    }
    if ($remain_restore_usd > 0) {
        // Restore other expenses (LIFO)
        $stmt = $pdo->prepare("SELECT id, amount_usd, remaining_usd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' ORDER BY date DESC, id DESC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_restore_usd <= 0) break;
            $used = max(0, floatval($row['amount_usd']) - floatval($row['remaining_usd']));
            if ($used <= 0) continue;
            $to_add = min($used, $remain_restore_usd);
            $pdo->prepare("UPDATE other_expenses SET remaining_usd = remaining_usd + ? WHERE id=?")->execute([$to_add, $row['id']]);
            $remain_restore_usd -= $to_add;
            $restore_expenses_usd += $to_add;
        }
    }
    if ($remain_restore_usd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET opening_debt_usd = opening_debt_usd + ? WHERE id=?")->execute([$remain_restore_usd, $person_id]);
        $remain_restore_usd = 0;
    }
    if ($restore_expenses_usd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET expense_usd = expense_usd + ? WHERE id=?")->execute([$restore_expenses_usd, $person_id]);
    }

    $remain_restore_iqd = $old_total_iqd;
    if ($remain_restore_iqd > 0) {
        // Restore purchases first (LIFO)
        $stmt = $pdo->prepare("SELECT id, total_price_iqd, remaining_amount_iqd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' ORDER BY purchase_date DESC, id DESC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_restore_iqd <= 0) break;
            $used = max(0, floatval($row['total_price_iqd']) - floatval($row['remaining_amount_iqd']));
            if ($used <= 0) continue;
            $to_add = min($used, $remain_restore_iqd);
            $pdo->prepare("UPDATE purchase_materials SET remaining_amount_iqd = remaining_amount_iqd + ? WHERE id=?")->execute([$to_add, $row['id']]);
            $remain_restore_iqd -= $to_add;
        }
    }
    if ($remain_restore_iqd > 0) {
        $stmt = $pdo->prepare("SELECT id, amount_iqd, remaining_iqd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' ORDER BY date DESC, id DESC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_restore_iqd <= 0) break;
            $used = max(0, floatval($row['amount_iqd']) - floatval($row['remaining_iqd']));
            if ($used <= 0) continue;
            $to_add = min($used, $remain_restore_iqd);
            $pdo->prepare("UPDATE other_expenses SET remaining_iqd = remaining_iqd + ? WHERE id=?")->execute([$to_add, $row['id']]);
            $remain_restore_iqd -= $to_add;
            $restore_expenses_iqd += $to_add;
        }
    }
    if ($remain_restore_iqd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET opening_debt_iqd = opening_debt_iqd + ? WHERE id=?")->execute([$remain_restore_iqd, $person_id]);
        $remain_restore_iqd = 0;
    }
    if ($restore_expenses_iqd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET expense_iqd = expense_iqd + ? WHERE id=?")->execute([$restore_expenses_iqd, $person_id]);
    }

    /**
     * چێککردن بۆ قەرزی نوێ
     */
    $stmt = $pdo->prepare("SELECT SUM(remaining_usd) as rem_usd, SUM(remaining_iqd) as rem_iqd FROM other_expenses WHERE person_id=? AND payment_type='قەرز'");
    $stmt->execute([$person_id]);
    $rem_expenses = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT SUM(remaining_amount_usd) as rem_usd, SUM(remaining_amount_iqd) as rem_iqd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز'");
    $stmt->execute([$person_id]);
    $rem_purchases = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_available_usd = round(floatval($person['opening_debt_usd']) + floatval($rem_expenses['rem_usd']) + floatval($rem_purchases['rem_usd']), 2);
    $total_available_iqd = round(floatval($person['opening_debt_iqd']) + floatval($rem_expenses['rem_iqd']) + floatval($rem_purchases['rem_iqd']), 2);

    $total_reduction_usd = round($amount_usd + $discount_usd, 2);
    $total_reduction_iqd = round($amount_iqd + $discount_iqd, 2);

    if (
        ($total_reduction_usd > 0 && $total_reduction_usd - $total_available_usd > 0.0001) ||
        ($total_reduction_iqd > 0 && $total_reduction_iqd - $total_available_iqd > 0.0001)
    ) {
        throw new Exception('نابێت بڕی پارە/داشکاندن زیاتر بێت لە بڕی قەرز!');
    }

    /**
     * پشکنینی نوێ و کەمکردن
     */
    $remain_reduce_usd = $total_reduction_usd;
    $deduct_opening_usd = 0;
    $deduct_expenses_usd = 0;
    $deduct_purchases_usd = 0;

    if ($remain_reduce_usd > 0) {
        $deduct_opening_usd = min(floatval($person['opening_debt_usd']), $remain_reduce_usd);
        if ($deduct_opening_usd > 0) {
            $pdo->prepare("UPDATE other_expense_persons SET opening_debt_usd = opening_debt_usd - ? WHERE id=?")->execute([$deduct_opening_usd, $person_id]);
            $remain_reduce_usd -= $deduct_opening_usd;
        }
    }
    
    // FIFO لە other_expenses.remaining_usd (یەکەم expenses)
    if ($remain_reduce_usd > 0) {
        $stmt = $pdo->prepare("SELECT id, remaining_usd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_usd > 0 ORDER BY date ASC, id ASC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_reduce_usd <= 0) break;
            $to_deduct = min(floatval($row['remaining_usd']), $remain_reduce_usd);
            if ($to_deduct <= 0) continue;
            $pdo->prepare("UPDATE other_expenses SET remaining_usd = remaining_usd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
            $remain_reduce_usd -= $to_deduct;
            $deduct_expenses_usd += $to_deduct;
        }
    }
    
    // FIFO لە purchase_materials.remaining_amount_usd (پاشان purchases)
    if ($remain_reduce_usd > 0) {
        $stmt = $pdo->prepare("SELECT id, remaining_amount_usd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_usd > 0 ORDER BY purchase_date ASC, id ASC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_reduce_usd <= 0) break;
            $to_deduct = min(floatval($row['remaining_amount_usd']), $remain_reduce_usd);
            if ($to_deduct <= 0) continue;
            $pdo->prepare("UPDATE purchase_materials SET remaining_amount_usd = remaining_amount_usd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
            $remain_reduce_usd -= $to_deduct;
            $deduct_purchases_usd += $to_deduct;
        }
    }
    if ($deduct_expenses_usd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET expense_usd = GREATEST(expense_usd - ?, 0) WHERE id=?")->execute([$deduct_expenses_usd, $person_id]);
    }

    $remain_reduce_iqd = $total_reduction_iqd;
    $deduct_opening_iqd = 0;
    $deduct_expenses_iqd = 0;
    $deduct_purchases_iqd = 0;

    if ($remain_reduce_iqd > 0) {
        $deduct_opening_iqd = min(floatval($person['opening_debt_iqd']), $remain_reduce_iqd);
        if ($deduct_opening_iqd > 0) {
            $pdo->prepare("UPDATE other_expense_persons SET opening_debt_iqd = opening_debt_iqd - ? WHERE id=?")->execute([$deduct_opening_iqd, $person_id]);
            $remain_reduce_iqd -= $deduct_opening_iqd;
        }
    }
    
    // FIFO لە other_expenses.remaining_iqd (یەکەم expenses)
    if ($remain_reduce_iqd > 0) {
        $stmt = $pdo->prepare("SELECT id, remaining_iqd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_iqd > 0 ORDER BY date ASC, id ASC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_reduce_iqd <= 0) break;
            $to_deduct = min(floatval($row['remaining_iqd']), $remain_reduce_iqd);
            if ($to_deduct <= 0) continue;
            $pdo->prepare("UPDATE other_expenses SET remaining_iqd = remaining_iqd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
            $remain_reduce_iqd -= $to_deduct;
            $deduct_expenses_iqd += $to_deduct;
        }
    }
    
    // FIFO لە purchase_materials.remaining_amount_iqd (پاشان purchases)
    if ($remain_reduce_iqd > 0) {
        $stmt = $pdo->prepare("SELECT id, remaining_amount_iqd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_iqd > 0 ORDER BY purchase_date ASC, id ASC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_reduce_iqd <= 0) break;
            $to_deduct = min(floatval($row['remaining_amount_iqd']), $remain_reduce_iqd);
            if ($to_deduct <= 0) continue;
            $pdo->prepare("UPDATE purchase_materials SET remaining_amount_iqd = remaining_amount_iqd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
            $remain_reduce_iqd -= $to_deduct;
            $deduct_purchases_iqd += $to_deduct;
        }
    }
    if ($deduct_expenses_iqd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET expense_iqd = GREATEST(expense_iqd - ?, 0) WHERE id=?")->execute([$deduct_expenses_iqd, $person_id]);
    }

    // نوێکردنەوەی تۆمار
    $stmt = $pdo->prepare("UPDATE person_other_expenses_debt_payments SET date=?, amount_usd=?, amount_iqd=?, discount_usd=?, discount_iqd=?, note=? WHERE id=?");
    $stmt->execute([$date, $amount_usd, $amount_iqd, $discount_usd, $discount_iqd, $note, $debt_id]);

    require_once __DIR__ . '/../../includes/notify.php';
    notify('update', 'person_other_expenses_debt_payments', $debt_id, 'پارەدانی قەرزی کەسانی تر نوێکرایەوە (کەس: ' . $person_id . ')');

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}
