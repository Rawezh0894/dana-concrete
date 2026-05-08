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

function applyPersonCurrencyReduction(PDO $pdo, int $personId, string $currency, float $amount, float $dollarRate = 0): void
{
    if ($amount <= 1e-6) {
        return;
    }

    $openingColumn = $currency === 'usd' ? 'opening_debt_usd' : 'opening_debt_iqd';
    $expenseColumn = $currency === 'usd' ? 'expense_usd' : 'expense_iqd';
    $expenseRemainingColumn = $currency === 'usd' ? 'remaining_usd' : 'remaining_iqd';
    $purchaseRemainingColumn = $currency === 'usd' ? 'remaining_amount_usd' : 'remaining_amount_iqd';

    // 1. Opening debt first
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

    if ($amount <= 1e-6) return;

    // 2. FIFO from other_expenses
    $deductedFromExpenses = 0;
    $expenseStmt = $pdo->prepare("
        SELECT id, {$expenseRemainingColumn} AS remaining
        FROM other_expenses
        WHERE person_id = ? AND payment_type = 'قەرز' AND {$expenseRemainingColumn} > 0
        ORDER BY date ASC, id ASC
        FOR UPDATE
    ");
    $expenseStmt->execute([$personId]);

    while ($amount > 1e-6 && ($row = $expenseStmt->fetch(PDO::FETCH_ASSOC))) {
        $remaining = (float)$row['remaining'];
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

    if ($amount <= 1e-6) return;

    // 3. FIFO from purchase_materials
    $purchaseStmt = $pdo->prepare("
        SELECT id, {$purchaseRemainingColumn} AS remaining
        FROM purchase_materials
        WHERE person_id = ? AND payment_type = 'قەرز' AND {$purchaseRemainingColumn} > 0
        ORDER BY purchase_date ASC, id ASC
        FOR UPDATE
    ");
    $purchaseStmt->execute([$personId]);

    while ($amount > 1e-6 && ($row = $purchaseStmt->fetch(PDO::FETCH_ASSOC))) {
        $remaining = (float)$row['remaining'];
        $toDeduct = min($remaining, $amount);
        $pdo->prepare("UPDATE purchase_materials SET {$purchaseRemainingColumn} = {$purchaseRemainingColumn} - ? WHERE id = ?")
            ->execute([$toDeduct, $row['id']]);
        $amount -= $toDeduct;
    }

    // 4. Cross-currency if still remaining
    if ($amount > 1e-6 && $dollarRate > 0) {
        $rateFactor = $dollarRate / 100;
        if ($currency === 'usd') {
            // Remaining USD to IQD
            applyPersonCurrencyReduction($pdo, $personId, 'iqd', $amount * $rateFactor, 0);
        } else {
            // Remaining IQD to USD
            applyPersonCurrencyReduction($pdo, $personId, 'usd', $amount / $rateFactor, 0);
        }
    }
}

function restorePersonCurrencyAmount(PDO $pdo, int $personId, string $currency, float $amount, float $dollarRate = 0): void
{
    if ($amount <= 1e-6) {
        return;
    }

    $openingColumn = $currency === 'usd' ? 'opening_debt_usd' : 'opening_debt_iqd';
    $expenseColumn = $currency === 'usd' ? 'expense_usd' : 'expense_iqd';
    $expenseRemainingColumn = $currency === 'usd' ? 'remaining_usd' : 'remaining_iqd';
    $expenseTotalColumn = $currency === 'usd' ? 'amount_usd' : 'amount_iqd';
    $purchaseRemainingColumn = $currency === 'usd' ? 'remaining_amount_usd' : 'remaining_amount_iqd';
    $purchaseTotalColumn = $currency === 'usd' ? 'total_price_usd' : 'total_price_iqd';

    // 1. Restore purchases first (LIFO)
    $purchaseStmt = $pdo->prepare("
        SELECT id, {$purchaseRemainingColumn} AS remaining, {$purchaseTotalColumn} AS total
        FROM purchase_materials
        WHERE person_id = ? AND payment_type = 'قەرز'
        ORDER BY purchase_date DESC, id DESC
        FOR UPDATE
    ");
    $purchaseStmt->execute([$personId]);

    while ($amount > 1e-6 && ($row = $purchaseStmt->fetch(PDO::FETCH_ASSOC))) {
        $used = max((float)$row['total'] - (float)$row['remaining'], 0);
        if ($used <= 1e-6) continue;
        $toRestore = min($used, $amount);
        $pdo->prepare("UPDATE purchase_materials SET {$purchaseRemainingColumn} = LEAST({$purchaseRemainingColumn} + ?, {$purchaseTotalColumn}) WHERE id = ?")
            ->execute([$toRestore, $row['id']]);
        $amount -= $toRestore;
    }

    if ($amount <= 1e-6) return;

    // 2. Restore other expenses (LIFO)
    $restoredExpenses = 0;
    $expenseStmt = $pdo->prepare("
        SELECT id, {$expenseRemainingColumn} AS remaining, {$expenseTotalColumn} AS total
        FROM other_expenses
        WHERE person_id = ? AND payment_type = 'قەرز'
        ORDER BY date DESC, id DESC
        FOR UPDATE
    ");
    $expenseStmt->execute([$personId]);

    while ($amount > 1e-6 && ($row = $expenseStmt->fetch(PDO::FETCH_ASSOC))) {
        $used = max((float)$row['total'] - (float)$row['remaining'], 0);
        if ($used <= 1e-6) continue;
        $toRestore = min($used, $amount);
        $pdo->prepare("UPDATE other_expenses SET {$expenseRemainingColumn} = LEAST({$expenseRemainingColumn} + ?, {$expenseTotalColumn}) WHERE id = ?")
            ->execute([$toRestore, $row['id']]);
        $amount -= $toRestore;
        $restoredExpenses += $toRestore;
    }

    if ($restoredExpenses > 0) {
        $pdo->prepare("UPDATE other_expense_persons SET {$expenseColumn} = {$expenseColumn} + ? WHERE id = ?")
            ->execute([$restoredExpenses, $personId]);
    }

    if ($amount <= 1e-6) return;

    // 3. Opening debt
    $openingStmt = $pdo->prepare("SELECT {$openingColumn} FROM other_expense_persons WHERE id = ? FOR UPDATE");
    $openingStmt->execute([$personId]);
    $openingDebtValue = (float)$openingStmt->fetchColumn();
    // For person profile, we actually have 'opening_debt' which can be any amount.
    // We'll just increase it.
    $pdo->prepare("UPDATE other_expense_persons SET {$openingColumn} = {$openingColumn} + ? WHERE id = ?")
        ->execute([$amount, $personId]);
    $amount = 0;

    // 4. Cross-currency (optional, but for person profile we usually just stop at opening debt)
}

