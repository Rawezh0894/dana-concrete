<?php
/**
 * Ensures concrete_formulas.type accepts NORMAL and SOFT (idempotent).
 */
function ensure_concrete_formula_type_enum(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM concrete_formulas LIKE 'type'");
        $col = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$col || empty($col['Type'])) {
            return;
        }
        $typeDef = $col['Type'];
        if (stripos($typeDef, 'NORMAL') !== false && stripos($typeDef, 'SOFT') !== false) {
            return;
        }
        $pdo->exec(
            "ALTER TABLE concrete_formulas MODIFY COLUMN `type` ENUM(
                'عەرزی تێکەڵ',
                'عەرزی سادە',
                'سەقف',
                'پایە',
                'NORMAL',
                'SOFT'
            ) NOT NULL"
        );
    } catch (Throwable $e) {
        error_log('ensure_concrete_formula_type_enum: ' . $e->getMessage());
    }
}
