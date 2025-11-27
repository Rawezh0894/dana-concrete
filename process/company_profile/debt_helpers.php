<?php

function getCompanyDebtSnapshot(PDO $pdo, int $companyId): array
{
    $companyStmt = $pdo->prepare('SELECT opening_debt_usd, opening_debt_iqd FROM company WHERE id = ?');
    $companyStmt->execute([$companyId]);
    $company = $companyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        throw new RuntimeException('کۆمپانیا نەدۆزرایەوە!');
    }

    $purchasesStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(remaining_usd), 0) AS remaining_usd,
            COALESCE(SUM(remaining_iqd), 0) AS remaining_iqd
        FROM purchases
        WHERE company_id = ? AND payment_type = 'قەرز'
    ");
    $purchasesStmt->execute([$companyId]);
    $purchases = $purchasesStmt->fetch(PDO::FETCH_ASSOC);

    return [
        'opening_debt_usd' => (float)($company['opening_debt_usd'] ?? 0),
        'opening_debt_iqd' => (float)($company['opening_debt_iqd'] ?? 0),
        'remaining_usd' => (float)($purchases['remaining_usd'] ?? 0),
        'remaining_iqd' => (float)($purchases['remaining_iqd'] ?? 0),
    ];
}

function applyCompanyCurrencyReduction(PDO $pdo, int $companyId, string $currency, float $amount): void
{
    if ($amount <= 0) {
        return;
    }

    $openingColumn = $currency === 'usd' ? 'opening_debt_usd' : 'opening_debt_iqd';
    $remainingColumn = $currency === 'usd' ? 'remaining_usd' : 'remaining_iqd';
    $typeValue = $currency === 'usd' ? 'دۆلار' : 'دینار';

    $openingStmt = $pdo->prepare("SELECT {$openingColumn} FROM company WHERE id = ? FOR UPDATE");
    $openingStmt->execute([$companyId]);
    $openingDebt = (float)$openingStmt->fetchColumn();

    if ($openingDebt > 0) {
        $toDeduct = min($openingDebt, $amount);
        $pdo->prepare("UPDATE company SET {$openingColumn} = {$openingColumn} - ? WHERE id = ?")
            ->execute([$toDeduct, $companyId]);
        $amount -= $toDeduct;
    }

    if ($amount <= 0) {
        return;
    }

    $purchasesStmt = $pdo->prepare("
        SELECT id, {$remainingColumn} AS remaining
        FROM purchases
        WHERE company_id = ? 
          AND payment_type = 'قەرز'
          AND type = ?
          AND {$remainingColumn} > 0
        ORDER BY date ASC, id ASC
        FOR UPDATE
    ");
    $purchasesStmt->execute([$companyId, $typeValue]);

    foreach ($purchasesStmt->fetchAll(PDO::FETCH_ASSOC) as $purchase) {
        if ($amount <= 0) {
            break;
        }
        $toDeduct = min((float)$purchase['remaining'], $amount);
        if ($toDeduct <= 0) {
            continue;
        }

        $pdo->prepare("UPDATE purchases SET {$remainingColumn} = {$remainingColumn} - ? WHERE id = ?")
            ->execute([$toDeduct, $purchase['id']]);
        $amount -= $toDeduct;
    }

    // If anything remains due to rounding, subtract it from opening debt to keep totals consistent
    if ($amount > 0) {
        $pdo->prepare("UPDATE company SET {$openingColumn} = GREATEST({$openingColumn} - ?, 0) WHERE id = ?")
            ->execute([$amount, $companyId]);
    }
}

function restoreCompanyCurrencyAmount(PDO $pdo, int $companyId, string $currency, float $amount): void
{
    if ($amount <= 0) {
        return;
    }

    $openingColumn = $currency === 'usd' ? 'opening_debt_usd' : 'opening_debt_iqd';
    $remainingColumn = $currency === 'usd' ? 'remaining_usd' : 'remaining_iqd';
    $totalColumn = $currency === 'usd' ? 'price' : 'amount_iqd';
    $typeValue = $currency === 'usd' ? 'دۆلار' : 'دینار';

    $purchasesStmt = $pdo->prepare("
        SELECT id, {$remainingColumn} AS remaining, {$totalColumn} AS total
        FROM purchases
        WHERE company_id = ?
          AND payment_type = 'قەرز'
          AND type = ?
        ORDER BY date DESC, id DESC
        FOR UPDATE
    ");
    $purchasesStmt->execute([$companyId, $typeValue]);

    foreach ($purchasesStmt->fetchAll(PDO::FETCH_ASSOC) as $purchase) {
        if ($amount <= 0) {
            break;
        }

        $remaining = (float)$purchase['remaining'];
        $total = (float)$purchase['total'];
        $maxRestore = max($total - $remaining, 0);
        if ($maxRestore <= 0) {
            continue;
        }

        $toRestore = min($maxRestore, $amount);
        $pdo->prepare("UPDATE purchases SET {$remainingColumn} = {$remainingColumn} + ? WHERE id = ?")
            ->execute([$toRestore, $purchase['id']]);
        $amount -= $toRestore;
    }

    if ($amount > 0) {
        $pdo->prepare("UPDATE company SET {$openingColumn} = {$openingColumn} + ? WHERE id = ?")
            ->execute([$amount, $companyId]);
    }
}

