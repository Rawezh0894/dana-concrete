<?php
require_once '../../config/db_conected.php';
require_once __DIR__ . '/concrete_receipt_helper.php';
header('Content-Type: application/json; charset=utf-8');

// NOTE: This value is only a client-side suggestion. The authoritative number
// is assigned atomically on insert (add_concerete_receipts.php), which recovers
// from concurrent collisions, so two users can safely receive the same
// suggestion here without producing duplicates.
echo json_encode(['success' => true, 'next' => concreteReceiptNextNumber($pdo)]);
