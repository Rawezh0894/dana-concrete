<?php

declare(strict_types=1);

/**
 * Personal loans to people outside the customer list — cash box integrated.
 */

function personal_loan_can_view(): bool
{
    return hasPermission('view_personal_loans')
        || hasPermission('view_cash_box');
}

function personal_loan_can_manage(): bool
{
    return hasPermission('manage_personal_loans')
        || hasPermission('add_cash_box');
}

function personal_loan_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `personal_loan_persons` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `name` VARCHAR(255) NOT NULL,
          `mobile` VARCHAR(50) DEFAULT NULL,
          `notes` VARCHAR(500) DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `personal_loans` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `person_id` INT UNSIGNED NOT NULL,
          `loan_usd` DECIMAL(14,2) NOT NULL DEFAULT 0,
          `loan_iqd` DECIMAL(20,2) NOT NULL DEFAULT 0,
          `remaining_usd` DECIMAL(14,2) NOT NULL DEFAULT 0,
          `remaining_iqd` DECIMAL(20,2) NOT NULL DEFAULT 0,
          `loan_date` DATE NOT NULL,
          `status` ENUM('active', 'paid_off', 'cancelled') NOT NULL DEFAULT 'active',
          `notes` VARCHAR(500) DEFAULT NULL,
          `created_by` INT UNSIGNED DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_personal_loans_person` (`person_id`),
          KEY `idx_personal_loans_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `personal_loan_repayments` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `loan_id` INT UNSIGNED NOT NULL,
          `received_usd` DECIMAL(14,2) NOT NULL DEFAULT 0,
          `received_iqd` DECIMAL(20,2) NOT NULL DEFAULT 0,
          `change_back_usd` DECIMAL(14,2) NOT NULL DEFAULT 0,
          `change_back_iq` DECIMAL(20,2) NOT NULL DEFAULT 0,
          `applied_usd` DECIMAL(14,2) NOT NULL DEFAULT 0,
          `applied_iqd` DECIMAL(20,2) NOT NULL DEFAULT 0,
          `dolar_rate` DECIMAL(14,2) NOT NULL DEFAULT 150000,
          `repayment_date` DATE NOT NULL,
          `notes` VARCHAR(500) DEFAULT NULL,
          `created_by` INT UNSIGNED DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_pl_repayments_loan` (`loan_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $chk = $pdo->query("SHOW COLUMNS FROM cash_box LIKE 'personal_loan_id'");
    if ($chk && $chk->rowCount() === 0) {
        $after = 'employee_loan_id';
        $chk2 = $pdo->query("SHOW COLUMNS FROM cash_box LIKE 'employee_loan_id'");
        if (!$chk2 || $chk2->rowCount() === 0) {
            $after = 'created_by';
        }
        $pdo->exec(
            "ALTER TABLE cash_box ADD COLUMN `personal_loan_id` INT UNSIGNED NULL DEFAULT NULL
             COMMENT 'Personal loan link' AFTER `{$after}`,
             ADD INDEX `idx_cash_box_personal_loan_id` (`personal_loan_id`)"
        );
    }

    $done = true;
}

function personal_loan_has_cash_column(PDO $pdo): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $chk = $pdo->query("SHOW COLUMNS FROM cash_box LIKE 'personal_loan_id'");
    $cached = $chk && $chk->rowCount() > 0;

    return $cached;
}

function personal_loan_rate_per_usd(float $dolarRate): float
{
    $r = $dolarRate > 0 ? $dolarRate / 100 : 1500.0;

    return $r > 0 ? $r : 1500.0;
}

/**
 * @return array{apply_usd: float, apply_iqd: float, net_usd: float, net_iqd: float}
 */
function personal_loan_compute_application(
    float $remainingUsd,
    float $remainingIqd,
    float $receivedUsd,
    float $receivedIqd,
    float $changeUsd,
    float $changeIq,
    float $dolarRate
): array {
    $ratePerUsd = personal_loan_rate_per_usd($dolarRate);

    $netUsd = round(max(0, $receivedUsd - $changeUsd), 2);
    $netIqd = round(max(0, $receivedIqd - $changeIq), 2);

    if ($netUsd <= 0 && $netIqd <= 0) {
        throw new RuntimeException('لانیکەم یەک بڕی وەرگیراو (دوای باقی) پێویستە.');
    }

    $remEquiv = $remainingUsd + ($remainingIqd / $ratePerUsd);
    $netEquiv = $netUsd + ($netIqd / $ratePerUsd);

    if ($netEquiv > $remEquiv + 0.05) {
        throw new RuntimeException('بڕی وەرگیراو (دوای باقی) زیاترە لە قەرزی ماوە.');
    }

    $toApplyEquiv = min($netEquiv, $remEquiv);
    $applyUsd = round(min($toApplyEquiv, $remainingUsd), 2);
    $leftEquiv = $toApplyEquiv - $applyUsd;
    $applyIqd = round(min($leftEquiv * $ratePerUsd, $remainingIqd), 2);

    return [
        'apply_usd' => $applyUsd,
        'apply_iqd' => $applyIqd,
        'net_usd' => $netUsd,
        'net_iqd' => $netIqd,
    ];
}

function personal_loan_check_cash_balance(PDO $pdo, float $usdWithdraw, float $iqdWithdraw): void
{
    if ($usdWithdraw > 0) {
        $bal = (float) $pdo->query(
            "SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount_usd ELSE -amount_usd END), 0)
             FROM cash_box WHERE currency='دۆلار'"
        )->fetchColumn();
        if ($usdWithdraw > $bal + 0.0001) {
            throw new RuntimeException('باڵانسی دۆلاری قاسە پێبوو نییە.');
        }
    }
    if ($iqdWithdraw > 0) {
        $bal = (float) $pdo->query(
            "SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount_iqd ELSE -amount_iqd END), 0)
             FROM cash_box WHERE currency='دینار'"
        )->fetchColumn();
        if ($iqdWithdraw > $bal + 0.0001) {
            throw new RuntimeException('باڵانسی دیناری قاسە پێبوو نییە.');
        }
    }
}

function personal_loan_insert_cash_row(
    PDO $pdo,
    string $dateYmd,
    string $type,
    float $usd,
    float $iqd,
    string $currency,
    string $note,
    ?int $createdBy,
    ?int $loanId
): void {
    $hasCol = personal_loan_has_cash_column($pdo);
    if ($hasCol && $loanId) {
        $ins = $pdo->prepare(
            'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`, `personal_loan_id`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $dateYmd,
            $type,
            $currency === 'دینار' ? $iqd : 0,
            $currency === 'دۆلار' ? $usd : 0,
            $currency,
            $note,
            $createdBy,
            $loanId,
        ]);
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $dateYmd,
            $type,
            $currency === 'دینار' ? $iqd : 0,
            $currency === 'دۆلار' ? $usd : 0,
            $currency,
            $note,
            $createdBy,
        ]);
    }
}

/**
 * Issue loan: withdraw from cash box, increase remaining.
 */
function personal_loan_issue(
    PDO $pdo,
    int $personId,
    float $loanUsd,
    float $loanIqd,
    string $loanDateYmd,
    ?string $notes,
    ?int $createdBy
): int {
    $loanUsd = round($loanUsd, 2);
    $loanIqd = round($loanIqd, 2);
    if ($loanUsd <= 0 && $loanIqd <= 0) {
        throw new RuntimeException('لانیکەم یەک بڕ (دۆلار یان دینار) پێویستە.');
    }

    $stmt = $pdo->prepare('SELECT id, name FROM personal_loan_persons WHERE id = ?');
    $stmt->execute([$personId]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$person) {
        throw new RuntimeException('کەس نەدۆزرایەوە.');
    }
    $personName = (string) $person['name'];

    personal_loan_check_cash_balance($pdo, $loanUsd, $loanIqd);

    $ins = $pdo->prepare(
        'INSERT INTO personal_loans (person_id, loan_usd, loan_iqd, remaining_usd, remaining_iqd, loan_date, status, notes, created_by)
         VALUES (?, ?, ?, ?, ?, ?, \'active\', ?, ?)'
    );
    $ins->execute([
        $personId,
        $loanUsd,
        $loanIqd,
        $loanUsd,
        $loanIqd,
        $loanDateYmd,
        $notes !== '' && $notes !== null ? $notes : null,
        $createdBy,
    ]);
    $loanId = (int) $pdo->lastInsertId();

    $noteBase = 'Personal Loan Issued — قەرزی کەس: ' . $personName . ' — Loan ID ' . $loanId;
    if ($loanUsd > 0) {
        personal_loan_insert_cash_row(
            $pdo,
            $loanDateYmd,
            'withdraw',
            $loanUsd,
            0,
            'دۆلار',
            $noteBase . ' ($)',
            $createdBy,
            $loanId
        );
    }
    if ($loanIqd > 0) {
        personal_loan_insert_cash_row(
            $pdo,
            $loanDateYmd,
            'withdraw',
            0,
            $loanIqd,
            'دینار',
            $noteBase . ' (د.ع)',
            $createdBy,
            $loanId
        );
    }

    return $loanId;
}

/**
 * Repayment with optional change (deposit gross, withdraw change).
 */
function personal_loan_apply_repayment(
    PDO $pdo,
    int $loanId,
    float $receivedUsd,
    float $receivedIqd,
    float $changeUsd,
    float $changeIq,
    float $dolarRate,
    string $repaymentDateYmd,
    ?string $notes,
    ?int $createdBy
): void {
    $receivedUsd = round($receivedUsd, 2);
    $receivedIqd = round($receivedIqd, 2);
    $changeUsd = round($changeUsd, 2);
    $changeIq = round($changeIq, 2);

    $stmt = $pdo->prepare(
        'SELECT pl.id, pl.remaining_usd, pl.remaining_iqd, pl.status, p.name AS person_name
         FROM personal_loans pl
         INNER JOIN personal_loan_persons p ON p.id = pl.person_id
         WHERE pl.id = ?'
    );
    $stmt->execute([$loanId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('قەرز نەدۆزرایەوە.');
    }
    if (($row['status'] ?? '') !== 'active') {
        throw new RuntimeException('ئەم قەرزە چالاک نییە.');
    }

    $remUsd = round((float) $row['remaining_usd'], 2);
    $remIqd = round((float) $row['remaining_iqd'], 2);
    $personName = (string) $row['person_name'];

    $calc = personal_loan_compute_application(
        $remUsd,
        $remIqd,
        $receivedUsd,
        $receivedIqd,
        $changeUsd,
        $changeIq,
        $dolarRate
    );

    personal_loan_check_cash_balance($pdo, $changeUsd, $changeIq);

    $noteBase = 'Personal Loan Repayment — گەڕاندنەوەی قەرز — ' . $personName . ' — Loan ID ' . $loanId;

    if ($receivedUsd > 0) {
        personal_loan_insert_cash_row(
            $pdo,
            $repaymentDateYmd,
            'deposit',
            $receivedUsd,
            0,
            'دۆلار',
            $noteBase . ' — وەرگرتن ($)',
            $createdBy,
            $loanId
        );
    }
    if ($receivedIqd > 0) {
        personal_loan_insert_cash_row(
            $pdo,
            $repaymentDateYmd,
            'deposit',
            0,
            $receivedIqd,
            'دینار',
            $noteBase . ' — وەرگرتن (د.ع)',
            $createdBy,
            $loanId
        );
    }
    if ($changeUsd > 0) {
        personal_loan_insert_cash_row(
            $pdo,
            $repaymentDateYmd,
            'withdraw',
            $changeUsd,
            0,
            'دۆلار',
            $noteBase . ' — باقی ($)',
            $createdBy,
            $loanId
        );
    }
    if ($changeIq > 0) {
        personal_loan_insert_cash_row(
            $pdo,
            $repaymentDateYmd,
            'withdraw',
            0,
            $changeIq,
            'دینار',
            $noteBase . ' — باقی (د.ع)',
            $createdBy,
            $loanId
        );
    }

    $insRep = $pdo->prepare(
        'INSERT INTO personal_loan_repayments
         (loan_id, received_usd, received_iqd, change_back_usd, change_back_iq, applied_usd, applied_iqd, dolar_rate, repayment_date, notes, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insRep->execute([
        $loanId,
        $receivedUsd,
        $receivedIqd,
        $changeUsd,
        $changeIq,
        $calc['apply_usd'],
        $calc['apply_iqd'],
        $dolarRate,
        $repaymentDateYmd,
        $notes !== '' && $notes !== null ? $notes : null,
        $createdBy,
    ]);

    $upd = $pdo->prepare(
        'UPDATE personal_loans SET remaining_usd = remaining_usd - ?, remaining_iqd = remaining_iqd - ? WHERE id = ?'
    );
    $upd->execute([$calc['apply_usd'], $calc['apply_iqd'], $loanId]);

    $mark = $pdo->prepare(
        "UPDATE personal_loans SET status = 'paid_off' WHERE id = ? AND remaining_usd <= 0.01 AND remaining_iqd <= 0.01"
    );
    $mark->execute([$loanId]);
}

/**
 * @return array{total_usd: float, total_iqd: float}
 */
function personal_loan_outstanding_totals(PDO $pdo): array
{
    $row = $pdo->query(
        "SELECT COALESCE(SUM(remaining_usd), 0) AS u, COALESCE(SUM(remaining_iqd), 0) AS i
         FROM personal_loans WHERE status = 'active'"
    )->fetch(PDO::FETCH_ASSOC) ?: ['u' => 0, 'i' => 0];

    return [
        'total_usd' => round((float) $row['u'], 2),
        'total_iqd' => round((float) $row['i'], 2),
    ];
}
