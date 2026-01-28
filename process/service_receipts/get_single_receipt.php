<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM service_receipts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($receipt) {
        echo json_encode(['success' => true, 'data' => $receipt]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Receipt not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
