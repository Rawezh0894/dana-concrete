<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$person_id = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;

if (!$person_id) {
    echo json_encode(['success' => false, 'error' => 'Person ID is required']);
    exit;
}

try {
    // Get purchase materials for this person
    $stmt = $pdo->prepare("
        SELECT 
            pm.id,
            pm.receipt_number,
            pm.purchase_date,
            pm.currency_type,
            pm.payment_type,
            pm.total_price_usd,
            pm.total_price_iqd,
            pm.paid_amount_usd,
            pm.paid_amount_iqd,
            pm.remaining_amount_usd,
            pm.remaining_amount_iqd,
            pm.notes,
            pm.created_at,
            1 as materials_count
        FROM purchase_materials pm
        WHERE pm.person_id = ?
        ORDER BY pm.purchase_date DESC, pm.created_at DESC
    ");
    
    $stmt->execute([$person_id]);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedPurchases = [];
    foreach ($purchases as $purchase) {
        $formattedPurchases[] = [
            'id' => (int)$purchase['id'],
            'receipt_number' => $purchase['receipt_number'],
            'purchase_date' => $purchase['purchase_date'],
            'currency_type' => $purchase['currency_type'],
            'payment_type' => $purchase['payment_type'],
            'total_price_usd' => (float)$purchase['total_price_usd'],
            'total_price_iqd' => (float)$purchase['total_price_iqd'],
            'paid_amount_usd' => (float)$purchase['paid_amount_usd'],
            'paid_amount_iqd' => (float)$purchase['paid_amount_iqd'],
            'remaining_amount_usd' => (float)$purchase['remaining_amount_usd'],
            'remaining_amount_iqd' => (float)$purchase['remaining_amount_iqd'],
            'notes' => $purchase['notes'],
            'materials_count' => (int)$purchase['materials_count'],
            'created_at' => $purchase['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedPurchases
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}
?> 