<?php
/**
 * Cash box integration for employee_expenses.
 * Withdrawals use employee_expense_id on cash_box for update/delete sync.
 */

/**
 * IQD equivalent of cash payout: (USD × rate) + IQD physical.
 */
function employee_expense_cash_iqd_equivalent(float $usd, float $iqd, float $ratePerOneUsd): float
{
    return round(($usd * $ratePerOneUsd) + $iqd, 2);
}

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
        'batch' => 'کۆی پارەدان',
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
 * Replace cash_box rows for one anchor expense: exact USD and IQD withdrawals from the form.
 * If both USD and IQD payment are zero, optionally one IQD withdrawal for $fallbackIqdWithdraw (legacy all-dinar from box).
 */
function employee_expense_replace_cash_withdrawals(
    PDO $pdo,
    int $anchorExpenseId,
    string $employeeName,
    string $expenseTypeKey,
    string $expenseDateStr,
    float $amountUsd,
    float $amountIqd,
    float $exchangeRate,
    ?string $notes,
    ?int $createdBy,
    float $fallbackIqdWithdraw
): void {
    $chk = $pdo->query("SHOW COLUMNS FROM cash_box LIKE 'employee_expense_id'");
    if (!$chk || $chk->rowCount() === 0) {
        return;
    }
    if ($anchorExpenseId <= 0) {
        return;
    }

    $amountUsd = round($amountUsd, 2);
    $amountIqd = round($amountIqd, 2);
    $exchangeRate = (float) $exchangeRate;

    employee_expense_delete_cash_box_rows($pdo, $anchorExpenseId);

    $date = employee_expense_cash_box_date($expenseDateStr);
    $extra = $exchangeRate > 0 ? " — نرخ: {$exchangeRate} د.ع/١\$" : '';

    if ($amountUsd > 0 || $amountIqd > 0) {
        if ($amountUsd > 0 && $exchangeRate <= 0) {
            throw new RuntimeException('کاتێک بڕی دۆلار هەیە، نرخی گۆڕینەوە پێویستە.');
        }
        if ($amountUsd > 0) {
            $note = employee_expense_cash_box_note($anchorExpenseId, $employeeName, $expenseTypeKey, $expenseDateStr, $extra . ' (دۆلار)');
            $ins = $pdo->prepare(
                'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`, `employee_expense_id`)
                 VALUES (?, ?, 0, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$date, 'withdraw', $amountUsd, 'دۆلار', $note, $createdBy ?: null, $anchorExpenseId]);
        }
        if ($amountIqd > 0) {
            $note = employee_expense_cash_box_note($anchorExpenseId, $employeeName, $expenseTypeKey, $expenseDateStr, $extra . ' (دینار)');
            $ins = $pdo->prepare(
                'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`, `employee_expense_id`)
                 VALUES (?, ?, ?, 0, ?, ?, ?, ?)'
            );
            $ins->execute([$date, 'withdraw', $amountIqd, 'دینار', $note, $createdBy ?: null, $anchorExpenseId]);
        }
        return;
    }

    if ($fallbackIqdWithdraw > 0) {
        $note = employee_expense_cash_box_note($anchorExpenseId, $employeeName, $expenseTypeKey, $expenseDateStr, ' (دینار — تەواوی دینار)');
        $ins = $pdo->prepare(
            'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`, `employee_expense_id`)
             VALUES (?, ?, ?, 0, ?, ?, ?, ?)'
        );
        $ins->execute([$date, 'withdraw', round($fallbackIqdWithdraw, 2), 'دینار', $note, $createdBy ?: null, $anchorExpenseId]);
    }
}

/**
 * Single expense row: sync cash from row fields (update/delete path).
 *
 * @param array<string,mixed> $row Keys: id, expense_type, amount, amount_usd, amount_iqd, exchange_rate, expense_date, created_by
 */
function employee_expense_sync_cash_box(PDO $pdo, array $row, string $employeeName): void
{
    $id = (int) ($row['id'] ?? 0);
    if ($id <= 0) {
        return;
    }

    $amountUsd = round((float) ($row['amount_usd'] ?? 0), 2);
    $amountIqd = round((float) ($row['amount_iqd'] ?? 0), 2);
    $rate = (float) ($row['exchange_rate'] ?? 0);
    $ledger = round((float) ($row['amount'] ?? 0), 2);
    $type = (string) ($row['expense_type'] ?? 'salary');

    if ($amountUsd > 0 || $amountIqd > 0) {
        employee_expense_replace_cash_withdrawals(
            $pdo,
            $id,
            $employeeName,
            $type,
            (string) ($row['expense_date'] ?? ''),
            $amountUsd,
            $amountIqd,
            $rate,
            isset($row['notes']) ? (string) $row['notes'] : null,
            isset($row['created_by']) ? (int) $row['created_by'] : null,
            0.0
        );
        return;
    }

    // Legacy: no USD/IQD in form → full ledger leaves cash as IQD only
    if ($ledger > 0) {
        employee_expense_replace_cash_withdrawals(
            $pdo,
            $id,
            $employeeName,
            $type,
            (string) ($row['expense_date'] ?? ''),
            0.0,
            0.0,
            0.0,
            isset($row['notes']) ? (string) $row['notes'] : null,
            isset($row['created_by']) ? (int) $row['created_by'] : null,
            $ledger
        );
    } else {
        employee_expense_delete_cash_box_rows($pdo, $id);
    }
}
