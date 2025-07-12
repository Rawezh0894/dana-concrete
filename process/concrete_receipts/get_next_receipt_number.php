<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

function getNextReceiptNumber($pdo) {
    $stmt = $pdo->query("SELECT receipt_number FROM concrete_receipts ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if (!$last) return 'A-0001';
    if (!preg_match('/^([A-Z])-([0-9]{4})$/', $last, $m)) return 'A-0001';
    $prefix = $m[1];
    $num = intval($m[2]);
    if ($num < 9999) {
        $num++;
        return sprintf('%s-%04d', $prefix, $num);
    } else {
        // Move to next letter
        if ($prefix === 'Z') return 'A-0001'; // wrap around
        $nextPrefix = chr(ord($prefix) + 1);
        return sprintf('%s-0001', $nextPrefix);
    }
}

echo json_encode(['success' => true, 'next' => getNextReceiptNumber($pdo)]); 