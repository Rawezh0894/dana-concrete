<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $today = date('Ymd');
    $stmt = $pdo->prepare("SELECT receipt_number FROM service_receipts WHERE receipt_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$today . '-%']);
    $last_receipt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last_receipt) {
        $last_number = $last_receipt['receipt_number'];
        $parts = explode('-', $last_number);
        if (count($parts) == 2 && is_numeric($parts[1])) {
            $next_seq = str_pad($parts[1] + 1, 3, '0', STR_PAD_LEFT);
            $next_number = $today . '-' . $next_seq;
        } else {
            $next_number = $today . '-001';
        }
    } else {
        $next_number = $today . '-001';
    }
    
    echo json_encode(['success' => true, 'next' => $next_number]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
