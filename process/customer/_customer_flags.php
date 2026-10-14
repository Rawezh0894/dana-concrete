<?php
/**
 * Parse checkbox / flag fields from POST (handles string, int, bool, or array).
 */
function customer_post_flag(string $key): int
{
    if (!isset($_POST[$key])) {
        return 0;
    }
    $value = $_POST[$key];
    if (is_array($value)) {
        $value = end($value);
    }
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }
    $value = strtolower(trim((string)$value));
    return in_array($value, ['1', 'true', 'on', 'yes'], true) ? 1 : 0;
}
