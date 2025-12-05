<?php
// Skip session for CLI execution
if (php_sapi_name() !== 'cli') {
    session_start();
}
require_once __DIR__ . '/../../config/db_conected.php';

try {
    // Check if discount_usd exists
    $checkUsd = $pdo->query("SHOW COLUMNS FROM `person_other_expenses_debt_payments` LIKE 'discount_usd'");
    if ($checkUsd->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `person_other_expenses_debt_payments` ADD COLUMN `discount_usd` decimal(15,2) DEFAULT 0.00 AFTER `amount_iqd`");
        echo "discount_usd added successfully\n";
    } else {
        echo "discount_usd already exists\n";
    }

    // Check if discount_iqd exists
    $checkIqd = $pdo->query("SHOW COLUMNS FROM `person_other_expenses_debt_payments` LIKE 'discount_iqd'");
    if ($checkIqd->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `person_other_expenses_debt_payments` ADD COLUMN `discount_iqd` decimal(15,2) DEFAULT 0.00 AFTER `discount_usd`");
        echo "discount_iqd added successfully\n";
    } else {
        echo "discount_iqd already exists\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

