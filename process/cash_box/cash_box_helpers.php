<?php

declare(strict_types=1);

/**
 * Removes DB-level block on withdrawals when cash box balance is low/negative.
 * Call once per request (idempotent).
 */
function cash_box_ensure_no_withdraw_balance_block(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $pdo->exec('DROP TRIGGER IF EXISTS `trg_before_withdraw_cash_box`');
    } catch (PDOException $e) {
        error_log('cash_box_ensure_no_withdraw_balance_block: ' . $e->getMessage());
    }
}
