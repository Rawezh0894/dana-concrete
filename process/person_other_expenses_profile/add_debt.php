<?php
require_once '../../config/db_conected.php';

$person_id = intval($_POST['person_id'] ?? 0);
$date = $_POST['date'] ?? date('Y-m-d');
$amount_usd = floatval($_POST['amount_usd'] ?? 0);
$amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
$note = $_POST['note'] ?? '';

if (!$person_id || ($amount_usd <= 0 && $amount_iqd <= 0)) {
    echo json_encode(['success' => false, 'msg' => 'زانیاری پێویست نەبوو']);
    exit;
}

try {
    $pdo->beginTransaction();

    // وەرگرتنی قەرزی سەرەتایی
    $stmt = $pdo->prepare("SELECT opening_debt_usd, opening_debt_iqd FROM other_expense_persons WHERE id=? FOR UPDATE");
    $stmt->execute([$person_id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check not exceeding current debt (opening + remaining in other_expenses)
    $stmt = $pdo->prepare("SELECT SUM(remaining_usd) as rem_usd, SUM(remaining_iqd) as rem_iqd FROM other_expenses WHERE person_id=? AND payment_type='قەرز'");
    $stmt->execute([$person_id]);
    $rem = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_usd = floatval($person['opening_debt_usd']) + floatval($rem['rem_usd']);
    $total_iqd = floatval($person['opening_debt_iqd']) + floatval($rem['rem_iqd']);
    if (($amount_usd > 0 && $amount_usd > $total_usd) || ($amount_iqd > 0 && $amount_iqd > $total_iqd)) {
        echo json_encode(['success' => false, 'msg' => 'نابێت بڕی پارەی گەرەوا زیاتر بێت لە بڕی قەرز!']);
        $pdo->rollBack();
        exit;
    }

    $remain_usd = $amount_usd;
    $remain_iqd = $amount_iqd;

    // 1. سەرەتا opening_debt_usd کەم بکە
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

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}


