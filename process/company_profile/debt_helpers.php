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

function applyCompanyCurrencyReduction(PDO $pdo, int $companyId, string $currency, float $amount, float $dollarRate = 0): void
{
    if ($amount <= 0) {
        return;
    }

    $rateFactor = $dollarRate > 0 ? ($dollarRate / 100) : 0;

    // 1. Reduce SAME currency debt
    $amount = reduceSpecificCurrencyDebt($pdo, $companyId, $currency, $amount);

    // 2. If amount remains and we have a rate, reduce OTHER currency debt
    if ($amount > 0 && $rateFactor > 0) {
        if ($currency === 'usd') {
            // Convert remaining USD to IQD
            $remainingIQD = $amount * $rateFactor;
            reduceSpecificCurrencyDebt($pdo, $companyId, 'iqd', $remainingIQD);
        } else {
            // Convert remaining IQD to USD
            $remainingUSD = $amount / $rateFactor;
            reduceSpecificCurrencyDebt($pdo, $companyId, 'usd', $remainingUSD);
        }
    }
}

function reduceSpecificCurrencyDebt(PDO $pdo, int $companyId, string $currency, float $amount): float
{
    if ($amount <= 0) return 0;

    $openingColumn = $currency === 'usd' ? 'opening_debt_usd' : 'opening_debt_iqd';
    $remainingColumn = $currency === 'usd' ? 'remaining_usd' : 'remaining_iqd';
    $typeValue = $currency === 'usd' ? 'دۆلار' : 'دینار';

    // A. Deduct from Opening Debt
    $openingStmt = $pdo->prepare("SELECT {$openingColumn} FROM company WHERE id = ? FOR UPDATE");
    $openingStmt->execute([$companyId]);
    $openingDebt = (float)$openingStmt->fetchColumn();

    if ($openingDebt > 0) {
        $toDeduct = min($openingDebt, $amount);
        $pdo->prepare("UPDATE company SET {$openingColumn} = {$openingColumn} - ? WHERE id = ?")
            ->execute([$toDeduct, $companyId]);
        $amount -= $toDeduct;
    }

    if ($amount <= 1e-6) return 0;

    // B. Deduct from Purchases
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
        if ($amount <= 1e-6) break;
        $toDeduct = min((float)$purchase['remaining'], $amount);
        if ($toDeduct <= 1e-6) continue;

        $pdo->prepare("UPDATE purchases SET {$remainingColumn} = {$remainingColumn} - ? WHERE id = ?")
            ->execute([$toDeduct, $purchase['id']]);
        $amount -= $toDeduct;
    }

    return $amount;
}

function restoreCompanyCurrencyAmount(PDO $pdo, int $companyId, string $currency, float $amount, float $dollarRate = 0): void
{
    if ($amount <= 0) return;

    $rateFactor = $dollarRate > 0 ? ($dollarRate / 100) : 0;

    // 1. Restore SAME currency first
    // Note: Restoration is tricky because we don't know exactly what was reduced.
    // For simplicity, we restore same then other.
    
    $amount = restoreSpecificCurrencyAmount($pdo, $companyId, $currency, $amount);

    if ($amount > 0 && $rateFactor > 0) {
        if ($currency === 'usd') {
            $remainingIQD = $amount * $rateFactor;
            restoreSpecificCurrencyAmount($pdo, $companyId, 'iqd', $remainingIQD);
        } else {
            $remainingUSD = $amount / $rateFactor;
            restoreSpecificCurrencyAmount($pdo, $companyId, 'usd', $remainingUSD);
        }
    }
}

function restoreSpecificCurrencyAmount(PDO $pdo, int $companyId, string $currency, float $amount): float
{
    if ($amount <= 0) return 0;

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
        if ($amount <= 1e-6) break;
        $remaining = (float)$purchase['remaining'];
        $total = (float)$purchase['total'];
        $maxRestore = max($total - $remaining, 0);
        if ($maxRestore <= 1e-6) continue;

        $toRestore = min($maxRestore, $amount);
        $pdo->prepare("UPDATE purchases SET {$remainingColumn} = {$remainingColumn} + ? WHERE id = ?")
            ->execute([$toRestore, $purchase['id']]);
        $amount -= $toRestore;
    }

    if ($amount > 1e-6) {
        $pdo->prepare("UPDATE company SET {$openingColumn} = {$openingColumn} + ? WHERE id = ?")
            ->execute([$amount, $companyId]);
        $amount = 0;
    }

    return $amount;
}

