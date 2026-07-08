<?php

declare(strict_types=1);

/**
 * Shared helpers for concrete receipt numbering + concurrency safety.
 *
 * Concurrency model:
 *  - A UNIQUE index on concrete_receipts.receipt_number is the definitive guard
 *    against duplicates (added idempotently by ensureConcreteReceiptUniqueIndex).
 *  - Auto-generated numbers are inserted inside a retry loop that catches the
 *    duplicate-key error and recomputes the next number, so concurrent writers
 *    never collide.
 */

/**
 * Compute the next receipt number based on the highest existing value.
 * Format: <A-Z>-<0001..9999>, rolling over to the next letter.
 */
function concreteReceiptNextNumber(PDO $pdo): string
{
    $stmt = $pdo->query("SELECT receipt_number FROM concrete_receipts ORDER BY id DESC LIMIT 1");
    $last = $stmt ? $stmt->fetchColumn() : false;

    if (!$last || !preg_match('/^([A-Z])-([0-9]{4})$/', (string) $last, $m)) {
        return 'A-0001';
    }

    $prefix = $m[1];
    $num = (int) $m[2];

    if ($num < 9999) {
        return sprintf('%s-%04d', $prefix, $num + 1);
    }

    if ($prefix === 'Z') {
        return 'A-0001';
    }

    return sprintf('%s-0001', chr(ord($prefix) + 1));
}

/**
 * Returns true when the value looks like an auto-generated number (or empty),
 * meaning the server is free to recompute it on collision.
 */
function concreteReceiptIsAutoNumber(?string $value): bool
{
    $value = trim((string) $value);
    return $value === '' || (bool) preg_match('/^[A-Z]-[0-9]{4}$/', $value);
}

/**
 * Idempotently add a UNIQUE index on receipt_number.
 * If duplicates already exist the ALTER would fail, so we detect that first and
 * skip (logging a warning) instead of breaking the request.
 */
function ensureConcreteReceiptUniqueIndex(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $idx = $pdo->query("SHOW INDEX FROM concrete_receipts WHERE Key_name = 'uq_receipt_number'");
        if ($idx && $idx->rowCount() > 0) {
            return;
        }

        $dupStmt = $pdo->query(
            "SELECT COUNT(*) FROM (
                SELECT receipt_number
                FROM concrete_receipts
                GROUP BY receipt_number
                HAVING COUNT(*) > 1
            ) d"
        );
        $dupCount = $dupStmt ? (int) $dupStmt->fetchColumn() : 0;

        if ($dupCount > 0) {
            error_log(
                'ensureConcreteReceiptUniqueIndex: skipped — ' . $dupCount
                . ' duplicate receipt_number value(s) exist. Clean them, then add UNIQUE index.'
            );
            return;
        }

        $pdo->exec("ALTER TABLE concrete_receipts ADD UNIQUE KEY `uq_receipt_number` (`receipt_number`)");
    } catch (Throwable $e) {
        error_log('ensureConcreteReceiptUniqueIndex: ' . $e->getMessage());
    }
}

/**
 * Detects a duplicate-key / integrity constraint violation from a PDOException.
 */
function concreteReceiptIsDuplicateError(PDOException $e): bool
{
    if ($e->getCode() === '23000') {
        return true;
    }
    $info = $e->errorInfo ?? [];
    // MySQL 1062 = ER_DUP_ENTRY
    return isset($info[1]) && (int) $info[1] === 1062;
}
