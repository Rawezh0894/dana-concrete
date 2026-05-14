<?php
/**
 * Sync employee_expenses rows to cash_box (withdraw) by currency.
 * Uses employee_expense_id on cash_box for idempotent replace on update/delete.
 */

/**
 * @return string 'Y-m-d' for cash_box.date from expense_date (YYYY-MM or Y-m-d)
 */
function employee_expense_cash_box_date(string $expenseDate): string
{
    $expenseDate = trim($expenseDate);
    if (preg_match('/^\d{4}-\d{2}$/', $expenseDate)) {
        return $expenseDate . '-01';
    }
    $ts = strtotime($expenseDate);
    return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
}

function employee_expense_cash_box_note(
    int $expenseId,
    string $employeeName,
    string $expenseType,
    string $expenseDate,
    string $suffix
): string {
    $typeMap = [
        'salary' => 'مووچە',
        'bonus' => 'بەخشیش',
        'overtime' => 'کاروانحیسابی',
        'advance' => 'پێشەکی',
        'deduction' => 'کەمکردنەوە',
        'penalty' => 'سزا',
        'overtime_payment' => 'پێدانی کاروانحیسابی',
    ];
    $label = $typeMap[$expenseType] ?? $expenseType;
    $base = "خەرجی کارمەند: {$label} — {$employeeName} — مانگ {$expenseDate} — ID {$expenseId}{$suffix}";
    if (function_exists('mb_strlen') ? mb_strlen($base, 'UTF-8') : strlen($base) < 10) {
        $base .= ' — قاسە/کارمەند';
    }
    $max = 250;
    if (function_exists('mb_strlen') && mb_strlen($base, 'UTF-8') > $max) {
        $base = mb_substr($base, 0, $max - 3, 'UTF-8') . '...';
    } elseif (strlen($base) > $max) {
        $base = substr($base, 0, $max - 3) . '...';
    }
    return $base;
}

function employee_expense_delete_cash_box_rows(PDO $pdo, int $expenseId): void
{
    if ($expenseId <= 0) {
        return;
    }
    $chk = $pdo->query("SHOW COLUMNS FROM cash_box LIKE 'employee_expense_id'");
    if (!$chk || $chk->rowCount() === 0) {
        return;
    }
    $stmt = $pdo->prepare('DELETE FROM cash_box WHERE employee_expense_id = ?');
    $stmt->execute([$expenseId]);
}

/**
 * @param array<string,mixed> $row Keys: id, employee_id, expense_type, amount, amount_usd, amount_iqd, exchange_rate, expense_date, notes, created_by
 */
function employee_expense_sync_cash_box(PDO $pdo, array $row, string $employeeName): void
{
    $chk = $pdo->query("SHOW COLUMNS FROM cash_box LIKE 'employee_expense_id'");
    if (!$chk || $chk->rowCount() === 0) {
        return;
    }

    $id = (int) ($row['id'] ?? 0);
    if ($id <= 0) {
        return;
    }

    $amountUsd = round((float) ($row['amount_usd'] ?? 0), 2);
    $amountIqd = round((float) ($row['amount_iqd'] ?? 0), 2);
    $rate = (float) ($row['exchange_rate'] ?? 0);
    $ledger = round((float) ($row['amount'] ?? 0), 2);

    // Legacy: no split stored → treat full ledger as IQD cash from box
    if ($amountUsd <= 0 && $amountIqd <= 0 && $ledger > 0) {
        $amountIqd = $ledger;
        $rate = 0.0;
    }

    if ($amountUsd > 0 && $rate <= 0) {
        throw new RuntimeException('کاتێک بڕی دۆلار هەیە، نرخی گۆڕین (١ دۆلار = چەند دینار) پێویستە.');
    }

    if ($amountUsd <= 0 && $amountIqd <= 0) {
        employee_expense_delete_cash_box_rows($pdo, $id);
        return;
    }

    employee_expense_delete_cash_box_rows($pdo, $id);

    $date = employee_expense_cash_box_date((string) ($row['expense_date'] ?? ''));
    $type = (string) ($row['expense_type'] ?? '');
    $createdBy = isset($row['created_by']) ? (int) $row['created_by'] : null;
    $extra = $rate > 0 ? " — نرخ: {$rate} د.ع/١\$" : '';

    if ($amountUsd > 0) {
        $note = employee_expense_cash_box_note($id, $employeeName, $type, (string) $row['expense_date'], $extra . ' (دۆلار)');
        $ins = $pdo->prepare(
            'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`, `employee_expense_id`)
             VALUES (?, ?, 0, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$date, 'withdraw', $amountUsd, 'دۆلار', $note, $createdBy ?: null, $id]);
    }

    if ($amountIqd > 0) {
        $note = employee_expense_cash_box_note($id, $employeeName, $type, (string) $row['expense_date'], $extra . ' (دینار)');
        $ins = $pdo->prepare(
            'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`, `employee_expense_id`)
             VALUES (?, ?, ?, 0, ?, ?, ?, ?)'
        );
        $ins->execute([$date, 'withdraw', $amountIqd, 'دینار', $note, $createdBy ?: null, $id]);
    }
}

/**
 * Split batch payment totals across lines by IQD ledger weight.
 *
 * @param array $lines Each item: ['type' => string, 'amount' => float]
 * @return array List of rows with keys type, amount, amount_usd, amount_iqd, exchange_rate
 */
function employee_expense_split_payment_amounts(
    array $lines,
    float $payUsd,
    float $payIqd,
    float $exchangeRate
): array {
    $total = 0.0;
    foreach ($lines as $ln) {
        $total += (float) $ln['amount'];
    }
    if ($total <= 0) {
        return [];
    }

    $payUsd = round($payUsd, 2);
    $payIqd = round($payIqd, 2);

    if ($payUsd <= 0 && $payIqd <= 0) {
        $out = [];
        foreach ($lines as $ln) {
            $out[] = [
                'type' => $ln['type'],
                'amount' => $ln['amount'],
                'amount_usd' => 0.0,
                'amount_iqd' => (float) $ln['amount'],
                'exchange_rate' => 0.0,
            ];
        }
        return $out;
    }

    if ($payUsd > 0 && $exchangeRate <= 0) {
        throw new RuntimeException('نرخی گۆڕین پێویستە کاتێک پارەدان بە دۆلار هەیە.');
    }

    $equiv = $payIqd + $payUsd * $exchangeRate;
    if (abs($equiv - $total) > 1.0) {
        throw new RuntimeException(
            'کۆی پارەدان (' . number_format($equiv, 0) . ' د.ع هاوتای) دەبێت یەکسان بێت بە کۆی خەرجی (' . number_format($total, 0) . ' د.ع).'
        );
    }

    $n = count($lines);
    $accUsd = 0.0;
    $accIqd = 0.0;
    $result = [];

    foreach ($lines as $i => $ln) {
        $amt = (float) $ln['amount'];
        $frac = $amt / $total;
        if ($i === $n - 1) {
            $usdPart = round($payUsd - $accUsd, 2);
            $iqdPart = round($payIqd - $accIqd, 2);
        } else {
            $usdPart = round($payUsd * $frac, 2);
            $iqdPart = round($payIqd * $frac, 2);
            $accUsd += $usdPart;
            $accIqd += $iqdPart;
        }
        $result[] = [
            'type' => $ln['type'],
            'amount' => $amt,
            'amount_usd' => max(0, $usdPart),
            'amount_iqd' => max(0, $iqdPart),
            'exchange_rate' => $exchangeRate,
        ];
    }

    return $result;
}
