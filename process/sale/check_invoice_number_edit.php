<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$invoice_number = trim($_POST['invoice_number'] ?? '');
$current_id = trim($_POST['current_id'] ?? '');

if (empty($invoice_number)) {
    echo json_encode(['success' => false, 'error' => 'Invoice number is required']);
    exit;
}

try {
    // Check if invoice number already exists (excluding current record)
    if (!empty($current_id)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sales WHERE invoice_number = ? AND id != ?");
        $stmt->execute([$invoice_number, $current_id]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sales WHERE invoice_number = ?");
        $stmt->execute([$invoice_number]);
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode([
            'success' => false, 
            'exists' => true, 
            'error' => 'ئەم ژمارەی پسوڵە پێشتر تۆمارکراوە، تکایە ژمارەیەکی تر هەڵبژێرە'
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'exists' => false, 
            'message' => 'ژمارەی پسوڵەکە بەردەستە'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
