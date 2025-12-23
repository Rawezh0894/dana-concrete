<?php

function getPersonDebtSnapshot(PDO $pdo, int $personId): array
{
    $stmt = $pdo->prepare('SELECT opening_debt_usd, opening_debt_iqd FROM other_expense_persons WHERE id = ?');
    $stmt->execute([$personId]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$person) {
        throw new RuntimeException('کەس نەدۆزرایەوە!');
    }

    $expensesStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(remaining_usd), 0) AS remaining_usd,
            COALESCE(SUM(remaining_iqd), 0) AS remaining_iqd
        FROM other_expenses
        WHERE person_id = ? AND payment_type = 'قەرز'
    ");
    $expensesStmt->execute([$personId]);
    $expenses = $expensesStmt->fetch(PDO::FETCH_ASSOC);

    $purchasesStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(remaining_amount_usd), 0) AS remaining_usd,
            COALESCE(SUM(remaining_amount_iqd), 0) AS remaining_iqd
        FROM purchase_materials
        WHERE person_id = ? AND payment_type = 'قەرز'
    ");
    $purchasesStmt->execute([$personId]);
    $purchases = $purchasesStmt->fetch(PDO::FETCH_ASSOC);

    $openingUsd = (float)($person['opening_debt_usd'] ?? 0);
    $openingIqd = (float)($person['opening_debt_iqd'] ?? 0);
    $expensesUsd = (float)($expenses['remaining_usd'] ?? 0);
    $expensesIqd = (float)($expenses['remaining_iqd'] ?? 0);
    $purchasesUsd = (float)($purchases['remaining_usd'] ?? 0);
    $purchasesIqd = (float)($purchases['remaining_iqd'] ?? 0);

    return [
        'opening_debt_usd' => $openingUsd,
        'opening_debt_iqd' => $openingIqd,
        'remaining_expenses_usd' => $expensesUsd,
        'remaining_expenses_iqd' => $expensesIqd,
        'remaining_purchases_usd' => $purchasesUsd,
        'remaining_purchases_iqd' => $purchasesIqd,
        'total_debt_usd' => $openingUsd + $expensesUsd + $purchasesUsd,
        'total_debt_iqd' => $openingIqd + $expensesIqd + $purchasesIqd,
    ];
}

function applyPersonCurrencyReduction(PDO $pdo, int $personId, string $currency, float $amount): void
{
    if ($amount <= 0) {
        return;
    }

    $openingColumn = $currency === 'usd' ? 'opening_debt_usd' : 'opening_debt_iqd';
    $expenseColumn = $currency === 'usd' ? 'expense_usd' : 'expense_iqd';
    $expenseRemainingColumn = $currency === 'usd' ? 'remaining_usd' : 'remaining_iqd';
    $purchaseRemainingColumn = $currency === 'usd' ? 'remaining_amount_usd' : 'remaining_amount_iqd';
    $typeValue = $currency === 'usd' ? 'دۆلار' : 'دینار';

    // Opening debt first
    $openingStmt = $pdo->prepare("SELECT {$openingColumn} FROM other_expense_persons WHERE id = ? FOR UPDATE");
    $openingStmt->execute([$personId]);
    $openingDebt = (float)$openingStmt->fetchColumn();

    if ($openingDebt > 0) {
        $toDeduct = min($openingDebt, $amount);
        if ($toDeduct > 0) {
            $pdo->prepare("UPDATE other_expense_persons SET {$openingColumn} = {$openingColumn} - ? WHERE id = ?")
                ->execute([$toDeduct, $personId]);
            $amount -= $toDeduct;
        }
    }

    if ($amount <= 0) {
        return;
    }

    // Then FIFO from other_expenses
    $deductedFromExpenses = 0;
    $expenseStmt = $pdo->prepare("
        SELECT id, {$expenseRemainingColumn} AS remaining
        FROM other_expenses
        WHERE person_id = ? AND payment_type = 'قەرز' AND {$expenseRemainingColumn} > 0
        ORDER BY date ASC, id ASC
        FOR UPDATE
    ");
    $expenseStmt->execute([$personId]);

    while ($amount > 0 && ($row = $expenseStmt->fetch(PDO::FETCH_ASSOC))) {
        $remaining = (float)$row['remaining'];
        if ($remaining <= 0) {
            continue;
        }
        $toDeduct = min($remaining, $amount);
        $pdo->prepare("UPDATE other_expenses SET {$expenseRemainingColumn} = {$expenseRemainingColumn} - ? WHERE id = ?")
            ->execute([$toDeduct, $row['id']]);
        $amount -= $toDeduct;
        $deductedFromExpenses += $toDeduct;
    }

    if ($deductedFromExpenses > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET {$expenseColumn} = GREATEST({$expenseColumn} - ?, 0) WHERE id = ?")
            ->execute([$deductedFromExpenses, $personId]);
    }

    if ($amount <= 0) {
        return;
    }

    // Finally FIFO from purchase_materials
    $purchaseStmt = $pdo->prepare("
        SELECT id, {$purchaseRemainingColumn} AS remaining
        FROM purchase_materials
        WHERE person_id = ? AND payment_type = 'قەرز' AND {$purchaseRemainingColumn} > 0
        ORDER BY purchase_date ASC, id ASC
        FOR UPDATE
    ");
    $purchaseStmt->execute([$personId]);

    while ($amount > 0 && ($row = $purchaseStmt->fetch(PDO::FETCH_ASSOC))) {
        $remaining = (float)$row['remaining'];
        if ($remaining <= 0) {
            continue;
        }
        $toDeduct = min($remaining, $amount);
        $pdo->prepare("UPDATE purchase_materials SET {$purchaseRemainingColumn} = {$purchaseRemainingColumn} - ? WHERE id = ?")
            ->execute([$toDeduct, $row['id']]);
        $amount -= $toDeduct;
    }

    // If anything is left due to rounding, subtract it from opening debt as a safeguard
    if ($amount > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET {$openingColumn} = GREATEST({$openingColumn} - ?, 0) WHERE id = ?")
            ->execute([$amount, $personId]);
    }
}

function restorePersonCurrencyAmount(PDO $pdo, int $personId, string $currency, float $amount): void
{
    if ($amount <= 0) {
        return;
    }

    $openingColumn = $currency === 'usd' ? 'opening_debt_usd' : 'opening_debt_iqd';
    $expenseColumn = $currency === 'usd' ? 'expense_usd' : 'expense_iqd';
    $expenseRemainingColumn = $currency === 'usd' ? 'remaining_usd' : 'remaining_iqd';
    $expenseTotalColumn = $currency === 'usd' ? 'amount_usd' : 'amount_iqd';
    $purchaseRemainingColumn = $currency === 'usd' ? 'remaining_amount_usd' : 'remaining_amount_iqd';
    $purchaseTotalColumn = $currency === 'usd' ? 'total_price_usd' : 'total_price_iqd';
    $typeValue = $currency === 'usd' ? 'دۆلار' : 'دینار';

    // Restore purchases first (LIFO - Last In First Out)
    // Get all purchases that have been paid (remaining < total)
    $purchaseStmt = $pdo->prepare("
        SELECT id, {$purchaseRemainingColumn} AS remaining, {$purchaseTotalColumn} AS total
        FROM purchase_materials
        WHERE person_id = ? AND payment_type = 'قەرز'
        ORDER BY purchase_date DESC, id DESC
        FOR UPDATE
    ");
    $purchaseStmt->execute([$personId]);

    $restoredPurchases = 0;
    while ($amount > 0 && ($row = $purchaseStmt->fetch(PDO::FETCH_ASSOC))) {
        $total = (float)$row['total'];
        $remaining = (float)$row['remaining'];
        $used = max($total - $remaining, 0);
        if ($used <= 0) {
            continue;
        }
        $toRestore = min($used, $amount);
        if ($toRestore > 0) {
            $pdo->prepare("UPDATE purchase_materials SET {$purchaseRemainingColumn} = LEAST({$purchaseRemainingColumn} + ?, {$purchaseTotalColumn}) WHERE id = ?")
                ->execute([$toRestore, $row['id']]);
            $amount -= $toRestore;
            $restoredPurchases += $toRestore;
        }
    }

    // Then restore other expenses (LIFO)
    $restoredExpenses = 0;
    $expenseStmt = $pdo->prepare("
        SELECT id, {$expenseRemainingColumn} AS remaining, {$expenseTotalColumn} AS total
        FROM other_expenses
        WHERE person_id = ? AND payment_type = 'قەرز'
        ORDER BY date DESC, id DESC
        FOR UPDATE
    ");
    $expenseStmt->execute([$personId]);

    while ($amount > 0 && ($row = $expenseStmt->fetch(PDO::FETCH_ASSOC))) {
        $total = (float)$row['total'];
        $remaining = (float)$row['remaining'];
        $used = max($total - $remaining, 0);
        if ($used <= 0) {
            continue;
        }
        $toRestore = min($used, $amount);
        if ($toRestore > 0) {
            $pdo->prepare("UPDATE other_expenses SET {$expenseRemainingColumn} = LEAST({$expenseRemainingColumn} + ?, {$expenseTotalColumn}) WHERE id = ?")
                ->execute([$toRestore, $row['id']]);
            $amount -= $toRestore;
            $restoredExpenses += $toRestore;
        }
    }

    if ($restoredExpenses > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET {$expenseColumn} = {$expenseColumn} + ? WHERE id = ?")
            ->execute([$restoredExpenses, $personId]);
    }

    // Any remaining amount increases opening debt
    if ($amount > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET {$openingColumn} = {$openingColumn} + ? WHERE id = ?")
            ->execute([$amount, $personId]);
    }
}

