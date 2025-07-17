<?php
require_once '../../config/db_conected.php';

$id = intval($_POST['id'] ?? 0); // id of debt payment
if (!$id) {
    echo json_encode(['success' => false, 'msg' => 'زانیاری پێویست نەبوو']);
    exit;
}

try {
    $pdo->beginTransaction();

    // وەرگرتنی amount و person_id
    $stmt = $pdo->prepare("SELECT person_id, amount_usd, amount_iqd FROM person_other_expenses_debt_payments WHERE id=? FOR UPDATE");
    $stmt->execute([$id]);
    $debt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$debt) throw new Exception('Debt not found');

    $person_id = $debt['person_id'];
    $amount_usd = floatval($debt['amount_usd']);
    $amount_iqd = floatval($debt['amount_iqd']);

    $remain_usd = $amount_usd;
    $remain_iqd = $amount_iqd;

    // 1. LIFO بۆ other_expenses.remaining_usd
    if ($remain_usd > 0) {
        $stmt = $pdo->prepare("SELECT id, amount_usd, remaining_usd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' ORDER BY date DESC, id DESC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_usd <= 0) break;
            $used = $row['amount_usd'] - $row['remaining_usd'];
            $to_add = min($used, $remain_usd);
            if ($to_add > 0) {
                $pdo->prepare("UPDATE other_expenses SET remaining_usd = remaining_usd + ? WHERE id=?")->execute([$to_add, $row['id']]);
                $remain_usd -= $to_add;
            }
        }
    }
    // 2. بڕی ماوە بگەڕێندرێتەوە بۆ opening_debt_usd
    if ($remain_usd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET opening_debt_usd = opening_debt_usd + ? WHERE id=?")->execute([$remain_usd, $person_id]);
    }

    // IQD
    if ($remain_iqd > 0) {
        $stmt = $pdo->prepare("SELECT id, amount_iqd, remaining_iqd FROM other_expenses WHERE person_id=? AND payment_type='قەرز' ORDER BY date DESC, id DESC FOR UPDATE");
        $stmt->execute([$person_id]);
        foreach ($stmt as $row) {
            if ($remain_iqd <= 0) break;
            $used = $row['amount_iqd'] - $row['remaining_iqd'];
            $to_add = min($used, $remain_iqd);
            if ($to_add > 0) {
                $pdo->prepare("UPDATE other_expenses SET remaining_iqd = remaining_iqd + ? WHERE id=?")->execute([$to_add, $row['id']]);
                $remain_iqd -= $to_add;
            }
        }
    }
    if ($remain_iqd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET opening_debt_iqd = opening_debt_iqd + ? WHERE id=?")->execute([$remain_iqd, $person_id]);
    }

    // After restoring to other_expenses and opening_debt, also restore to expense_usd/iqd
    $paid_from_expenses_usd = $amount_usd - $remain_usd;
    $paid_from_expenses_iqd = $amount_iqd - $remain_iqd;
    if ($paid_from_expenses_usd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET expense_usd = expense_usd + ? WHERE id=?")->execute([$paid_from_expenses_usd, $person_id]);
    }
    if ($paid_from_expenses_iqd > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET expense_iqd = expense_iqd + ? WHERE id=?")->execute([$paid_from_expenses_iqd, $person_id]);
    }

    // سڕینەوەی تۆمارەکە
    $pdo->prepare("DELETE FROM person_other_expenses_debt_payments WHERE id=?")->execute([$id]);
    require_once __DIR__ . '/../../includes/notify.php';
    notify('delete', 'person_other_expenses_debt_payments', $id, 'پارەدانی قەرزی کەسانی تر سڕایەوە (کەس: ' . $person_id . ')');
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'msg' => 'هەڵە: ' . $e->getMessage()]);
}
