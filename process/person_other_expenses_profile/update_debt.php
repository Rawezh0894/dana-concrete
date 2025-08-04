<?php
require_once '../../config/db_conected.php';

$debt_id = intval($_POST['debt_id'] ?? 0);
$person_id = intval($_POST['person_id'] ?? 0);
$date = $_POST['date'] ?? date('Y-m-d');
$amount_usd = floatval($_POST['amount_usd'] ?? 0);
$amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
$note = $_POST['note'] ?? '';

if (!$debt_id || !$person_id || ($amount_usd <= 0 && $amount_iqd <= 0)) {
    echo json_encode(['success' => false, 'msg' => 'زانیاری پێویست نەبوو']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. گەڕاندنەوەی قەرزی کۆن (LIFO)
    $stmt = $pdo->prepare("SELECT amount_usd, amount_iqd FROM person_other_expenses_debt_payments WHERE id=? AND person_id=? FOR UPDATE");
    $stmt->execute([$debt_id, $person_id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$old) throw new Exception('قەرزەکە بوونی نییە!');

    $old_usd = floatval($old['amount_usd']);
    $old_iqd = floatval($old['amount_iqd']);

    // Find how much was originally deducted from opening_debt
    $stmt = $pdo->prepare("SELECT opening_debt_usd, opening_debt_iqd FROM other_expense_persons WHERE id=?");
    $stmt->execute([$person_id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);
    $opening_before_usd = floatval($person['opening_debt_usd']) + $old_usd;
    $opening_before_iqd = floatval($person['opening_debt_iqd']) + $old_iqd;
    $deduct_opening_usd = min($opening_before_usd, $old_usd);
    $deduct_opening_iqd = min($opening_before_iqd, $old_iqd);
    $deduct_expenses_usd = $old_usd - $deduct_opening_usd;
    $deduct_expenses_iqd = $old_iqd - $deduct_opening_iqd;

    // 1. Restore to opening_debt_usd ONLY the amount originally deducted from opening_debt
    if ($deduct_opening_usd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET opening_debt_usd = opening_debt_usd + ? WHERE id=?")->execute([$deduct_opening_usd, $person_id]);
    }
    // 2. Restore to purchase_materials (LIFO) ONLY the amount originally deducted from purchases
    $remain_usd = $deduct_expenses_usd;
    if ($remain_usd > 0) {
        $stmt = $pdo->prepare("SELECT id FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' ORDER BY purchase_date DESC, id DESC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_usd <= 0) break;
            $to_add = $remain_usd; // All remaining amount goes to the next purchase (since we don't track per-purchase deduction)
            $pdo->prepare("UPDATE purchase_materials SET remaining_amount_usd = remaining_amount_usd + ? WHERE id=?")->execute([$to_add, $row['id']]);
            $remain_usd -= $to_add;
        }
    }
    
    // 3. Restore to other_expenses (LIFO) ONLY the amount originally deducted from expenses
    if ($remain_usd > 0) {
        $stmt = $pdo->prepare("SELECT id FROM other_expenses WHERE person_id=? AND payment_type='قەرز' ORDER BY date DESC, id DESC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_usd <= 0) break;
            $to_add = $remain_usd; // All remaining amount goes to the next expense (since we don't track per-expense deduction)
            $pdo->prepare("UPDATE other_expenses SET remaining_usd = remaining_usd + ? WHERE id=?")->execute([$to_add, $row['id']]);
            $remain_usd -= $to_add;
        }
    }

    // IQD
    if ($deduct_opening_iqd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET opening_debt_iqd = opening_debt_iqd + ? WHERE id=?")->execute([$deduct_opening_iqd, $person_id]);
    }
    // 2. Restore to purchase_materials (LIFO) ONLY the amount originally deducted from purchases
    $remain_iqd = $deduct_expenses_iqd;
    if ($remain_iqd > 0) {
        $stmt = $pdo->prepare("SELECT id FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' ORDER BY purchase_date DESC, id DESC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_iqd <= 0) break;
            $to_add = $remain_iqd; // All remaining amount goes to the next purchase (since we don't track per-purchase deduction)
            $pdo->prepare("UPDATE purchase_materials SET remaining_amount_iqd = remaining_amount_iqd + ? WHERE id=?")->execute([$to_add, $row['id']]);
            $remain_iqd -= $to_add;
        }
    }
    
    // 3. Restore to other_expenses (LIFO) ONLY the amount originally deducted from expenses
    if ($remain_iqd > 0) {
        $stmt = $pdo->prepare("SELECT id FROM other_expenses WHERE person_id=? AND payment_type='قەرز' ORDER BY date DESC, id DESC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_iqd <= 0) break;
            $to_add = $remain_iqd;
            $pdo->prepare("UPDATE other_expenses SET remaining_iqd = remaining_iqd + ? WHERE id=?")->execute([$to_add, $row['id']]);
            $remain_iqd -= $to_add;
        }
    }

    // 2. سڕینەوەی قەرزی کۆن
    $pdo->prepare("DELETE FROM person_other_expenses_debt_payments WHERE id=?")->execute([$debt_id]);

    // 3. زیادکردنی قەرزی نوێ بە هەمان میکانیزم add_debt.php
    // وەک add_debt.php
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
    
    $total_usd = floatval($person['opening_debt_usd']) + floatval($rem_expenses['rem_usd']) + floatval($rem_purchases['rem_usd']);
    $total_iqd = floatval($person['opening_debt_iqd']) + floatval($rem_expenses['rem_iqd']) + floatval($rem_purchases['rem_iqd']);
    if (($amount_usd > 0 && $amount_usd > $total_usd) || ($amount_iqd > 0 && $amount_iqd > $total_iqd)) {
        throw new Exception('نابێت بڕی پارەی گەرەوا زیاتر بێت لە بڕی قەرز!');
    }

    $remain_usd = $amount_usd;
    $remain_iqd = $amount_iqd;

    // 1. سەرەتا opening_debt_usd کم بکە
    $opening_usd = floatval($person['opening_debt_usd']);
    $deduct_opening_usd = min($opening_usd, $remain_usd);
    if ($deduct_opening_usd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET opening_debt_usd = opening_debt_usd - ? WHERE id=?")->execute([$deduct_opening_usd, $person_id]);
        $remain_usd -= $deduct_opening_usd;
    }
    // 2. FIFO لە other_expenses.remaining_usd
    if ($remain_usd > 0) {
        $stmt = $pdo->prepare("SELECT id, remaining_usd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_usd > 0 ORDER BY date ASC, id ASC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_usd <= 0) break;
            $to_deduct = min($row['remaining_usd'], $remain_usd);
            $pdo->prepare("UPDATE other_expenses SET remaining_usd = remaining_usd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
            $remain_usd -= $to_deduct;
        }
    }
    
    // 3. FIFO لە purchase_materials.remaining_amount_usd
    if ($remain_usd > 0) {
        $stmt = $pdo->prepare("SELECT id, remaining_amount_usd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_usd > 0 ORDER BY purchase_date ASC, id ASC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_usd <= 0) break;
            $to_deduct = min($row['remaining_amount_usd'], $remain_usd);
            $pdo->prepare("UPDATE purchase_materials SET remaining_amount_usd = remaining_amount_usd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
            $remain_usd -= $to_deduct;
        }
    }

    // IQD
    $opening_iqd = floatval($person['opening_debt_iqd']);
    $deduct_opening_iqd = min($opening_iqd, $remain_iqd);
    if ($deduct_opening_iqd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET opening_debt_iqd = opening_debt_iqd - ? WHERE id=?")->execute([$deduct_opening_iqd, $person_id]);
        $remain_iqd -= $deduct_opening_iqd;
    }
    if ($remain_iqd > 0) {
        $stmt = $pdo->prepare("SELECT id, remaining_iqd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' AND remaining_iqd > 0 ORDER BY date ASC, id ASC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_iqd <= 0) break;
            $to_deduct = min($row['remaining_iqd'], $remain_iqd);
            $pdo->prepare("UPDATE other_expenses SET remaining_iqd = remaining_iqd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
            $remain_iqd -= $to_deduct;
        }
    }
    
    // 3. FIFO لە purchase_materials.remaining_amount_iqd
    if ($remain_iqd > 0) {
        $stmt = $pdo->prepare("SELECT id, remaining_amount_iqd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز' AND remaining_amount_iqd > 0 ORDER BY purchase_date ASC, id ASC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_iqd <= 0) break;
            $to_deduct = min($row['remaining_amount_iqd'], $remain_iqd);
            $pdo->prepare("UPDATE purchase_materials SET remaining_amount_iqd = remaining_amount_iqd - ? WHERE id=?")->execute([$to_deduct, $row['id']]);
            $remain_iqd -= $to_deduct;
        }
    }

    // Track how much was paid from other_expenses for summary update
    $paid_from_expenses_usd = $amount_usd - $deduct_opening_usd;
    $paid_from_expenses_iqd = $amount_iqd - $deduct_opening_iqd;
    if ($paid_from_expenses_usd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET expense_usd = GREATEST(expense_usd - ?, 0) WHERE id=?")->execute([$paid_from_expenses_usd, $person_id]);
    }
    if ($paid_from_expenses_iqd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET expense_iqd = GREATEST(expense_iqd - ?, 0) WHERE id=?")->execute([$paid_from_expenses_iqd, $person_id]);
    }

    // تۆمارکردنی مامەڵەکە
    $stmt = $pdo->prepare("INSERT INTO person_other_expenses_debt_payments (person_id, date, amount_usd, amount_iqd, note) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$person_id, $date, $amount_usd, $amount_iqd, $note]);
    require_once __DIR__ . '/../../includes/notify.php';
    notify('update', 'person_other_expenses_debt_payments', $debt_id, 'پارەدانی قەرزی کەسانی تر نوێکرایەوە (کەس: ' . $person_id . ')');
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}
