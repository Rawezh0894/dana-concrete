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

$receipt_number = trim($_POST['receipt_number'] ?? '');

if (empty($receipt_number)) {
    echo json_encode(['success' => false, 'error' => 'Receipt number is required']);
    exit;
}

try {
    // Check if receipt number already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchase_materials WHERE receipt_number = ?");
    $stmt->execute([$receipt_number]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode([
            'success' => false, 
            'exists' => true, 
            'error' => 'ژمارەی پسووڵەکە هەیە، تکایە ژمارەیەکی تر هەڵبژێرە'
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'exists' => false, 
            'message' => 'ژمارەی پسووڵەکە بەردەستە'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?> 