<?php

declare(strict_types=1);

/**
 * Employee loans: issuance (cash box) and repayment (FIFO per currency).
 */

function employee_loan_has_cash_loan_id_column(PDO $pdo): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $chk = $pdo->query("SHOW COLUMNS FROM cash_box LIKE 'employee_loan_id'");
    $cached = $chk && $chk->rowCount() > 0;

    return $cached;
}

/**
 * @return array{usd: float, iqd: float}
 */
function employee_loan_outstanding_totals(PDO $pdo, int $employeeId): array
{
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(remaining_usd), 0) AS u, COALESCE(SUM(remaining_iqd), 0) AS i
         FROM employee_loans
         WHERE employee_id = ? AND status = 'active'"
    );
    $stmt->execute([$employeeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['u' => 0, 'i' => 0];

    return [
        'usd' => round((float) $row['u'], 2),
        'iqd' => round((float) $row['i'], 2),
    ];
}

/**
 * Insert withdraw row(s) for loan issuance; sets employee_loan_id when column exists.
 *
 * @throws RuntimeException on insufficient balance
 */
function employee_loan_insert_cash_withdrawals(
    PDO $pdo,
    int $loanId,
    string $employeeName,
    string $loanDateYmd,
    float $loanUsd,
    float $loanIqd,
    ?int $createdBy
): void {
    $loanUsd = round($loanUsd, 2);
    $loanIqd = round($loanIqd, 2);
    if ($loanUsd <= 0 && $loanIqd <= 0) {
        return;
    }

    if ($loanUsd > 0) {
        $balStmt = $pdo->query("
            SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount_usd ELSE -amount_usd END), 0)
            FROM cash_box WHERE currency='دۆلار'
        ");
        $usdBal = (float) $balStmt->fetchColumn();
        if ($loanUsd > $usdBal + 0.0001) {
            throw new RuntimeException('باڵانسی دۆلاری قاسە پێبوو نییە بۆ قەرز.');
        }
    }
    if ($loanIqd > 0) {
        $balStmt = $pdo->query("
            SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount_iqd ELSE -amount_iqd END), 0)
            FROM cash_box WHERE currency='دینار'
        ");
        $iqdBal = (float) $balStmt->fetchColumn();
        if ($loanIqd > $iqdBal + 0.0001) {
            throw new RuntimeException('باڵانسی دیناری قاسە پێبوو نییە بۆ قەرز.');
        }
    }

    $hasLoanCol = employee_loan_has_cash_loan_id_column($pdo);
    $noteBase = 'Employee Loan Issued — قەرزی کارمەند: ' . $employeeName . ' — Loan ID ' . $loanId;

    if ($loanUsd > 0) {
        if ($hasLoanCol) {
            $ins = $pdo->prepare(
                'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`, `employee_loan_id`)
                 VALUES (?, ?, 0, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$loanDateYmd, 'withdraw', $loanUsd, 'دۆلار', $noteBase . ' ($)', $createdBy ?: null, $loanId]);
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
                 VALUES (?, ?, 0, ?, ?, ?, ?)'
            );
            $ins->execute([$loanDateYmd, 'withdraw', $loanUsd, 'دۆلار', $noteBase . ' ($)', $createdBy ?: null]);
        }
    }
    if ($loanIqd > 0) {
        if ($hasLoanCol) {
            $ins = $pdo->prepare(
                'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`, `employee_loan_id`)
                 VALUES (?, ?, ?, 0, ?, ?, ?, ?)'
            );
            $ins->execute([$loanDateYmd, 'withdraw', $loanIqd, 'دینار', $noteBase . ' (د.ع)', $createdBy ?: null, $loanId]);
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
                 VALUES (?, ?, ?, 0, ?, ?, ?)'
            );
            $ins->execute([$loanDateYmd, 'withdraw', $loanIqd, 'دینار', $noteBase . ' (د.ع)', $createdBy ?: null]);
        }
    }
}

/**
 * FIFO per currency; merge allocations per loan_id; insert loan_repayments + update remainings.
 *
 * @throws RuntimeException
 */
function employee_loan_apply_repayment(
    PDO $pdo,
    int $employeeId,
    float $deductUsd,
    float $deductIqd,
    int $expenseId
): void {
    $deductUsd = round($deductUsd, 2);
    $deductIqd = round($deductIqd, 2);
    if ($deductUsd <= 0 && $deductIqd <= 0) {
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT id, remaining_usd, remaining_iqd FROM employee_loans
         WHERE employee_id = ? AND status = 'active' AND (remaining_usd > 0 OR remaining_iqd > 0)
         ORDER BY loan_date ASC, id ASC"
    );
    $stmt->execute([$employeeId]);
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$loans) {
        throw new RuntimeException('هیچ قەرزی چالاک نییە بۆ ئەم کارمەندە.');
    }

    $alloc = [];
    $needUsd = $deductUsd;
    foreach ($loans as $L) {
        if ($needUsd <= 0) {
            break;
        }
        $rem = round((float) $L['remaining_usd'], 2);
        if ($rem <= 0) {
            continue;
        }
        $take = min($needUsd, $rem);
        $id = (int) $L['id'];
        if (!isset($alloc[$id])) {
            $alloc[$id] = ['usd' => 0.0, 'iqd' => 0.0];
        }
        $alloc[$id]['usd'] += $take;
        $needUsd -= $take;
    }
    if ($needUsd > 0.01) {
        throw new RuntimeException('بڕی کەمکردنەوەی قەرز بە دۆلار زیاترە لە قەرزی ماوە.');
    }

    $needIqd = $deductIqd;
    foreach ($loans as $L) {
        if ($needIqd <= 0) {
            break;
        }
        $rem = round((float) $L['remaining_iqd'], 2);
        if ($rem <= 0) {
            continue;
        }
        $take = min($needIqd, $rem);
        $id = (int) $L['id'];
        if (!isset($alloc[$id])) {
            $alloc[$id] = ['usd' => 0.0, 'iqd' => 0.0];
        }
        $alloc[$id]['iqd'] += $take;
        $needIqd -= $take;
    }
    if ($needIqd > 0.01) {
        throw new RuntimeException('بڕی کەمکردنەوەی قەرز بە دینار زیاترە لە قەرزی ماوە.');
    }

    $insRep = $pdo->prepare(
        'INSERT INTO loan_repayments (loan_id, expense_id, deducted_usd, deducted_iqd) VALUES (?, ?, ?, ?)'
    );
    $upd = $pdo->prepare(
        'UPDATE employee_loans SET remaining_usd = remaining_usd - ?, remaining_iqd = remaining_iqd - ? WHERE id = ?'
    );
    $markPaid = $pdo->prepare(
        "UPDATE employee_loans SET status = 'paid_off' WHERE id = ? AND remaining_usd <= 0.01 AND remaining_iqd <= 0.01"
    );

    foreach ($alloc as $loanId => $parts) {
        $u = round($parts['usd'], 2);
        $i = round($parts['iqd'], 2);
        if ($u <= 0 && $i <= 0) {
            continue;
        }
        $insRep->execute([$loanId, $expenseId, $u, $i]);
        $upd->execute([$u, $i, $loanId]);
        $markPaid->execute([$loanId]);
    }
}

/**
 * Reverse repayments tied to an expense (e.g. on delete). Restores loan balances.
 */
function employee_loan_reverse_repayments_for_expense(PDO $pdo, int $expenseId): void
{
    $chk = $pdo->query("SHOW TABLES LIKE 'loan_repayments'");
    if (!$chk || $chk->rowCount() === 0) {
        return;
    }
    $stmt = $pdo->prepare('SELECT loan_id, deducted_usd, deducted_iqd FROM loan_repayments WHERE expense_id = ?');
    $stmt->execute([$expenseId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        return;
    }

    $upd = $pdo->prepare(
        'UPDATE employee_loans SET remaining_usd = remaining_usd + ?, remaining_iqd = remaining_iqd + ?,
         status = CASE WHEN status = \'paid_off\' THEN \'active\' ELSE status END
         WHERE id = ?'
    );
    foreach ($rows as $r) {
        $upd->execute([
            round((float) $r['deducted_usd'], 2),
            round((float) $r['deducted_iqd'], 2),
            (int) $r['loan_id'],
        ]);
    }
    $del = $pdo->prepare('DELETE FROM loan_repayments WHERE expense_id = ?');
    $del->execute([$expenseId]);
}

function employee_loan_delete_cash_rows_for_loan(PDO $pdo, int $loanId): void
{
    if ($loanId <= 0) {
        return;
    }
    if (!employee_loan_has_cash_loan_id_column($pdo)) {
        return;
    }
    $stmt = $pdo->prepare('DELETE FROM cash_box WHERE employee_loan_id = ?');
    $stmt->execute([$loanId]);
}
