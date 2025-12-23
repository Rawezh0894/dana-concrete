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
    // Get purchase materials grouped by receipt number for this person
    // Fixed: Calculate remaining amount correctly as total_price - paid_amount
    $stmt = $pdo->prepare("
        SELECT 
            pm.receipt_number,
            pm.purchase_date,
            pm.currency_type,
            pm.payment_type,
            pm.notes,
            pm.created_at,
            COUNT(pm.id) as materials_count,
            SUM(pm.total_price_usd) as total_price_usd,
            SUM(pm.total_price_iqd) as total_price_iqd,
            SUM(pm.paid_amount_usd) as paid_amount_usd,
            SUM(pm.paid_amount_iqd) as paid_amount_iqd,
            SUM(pm.remaining_amount_usd) as remaining_amount_usd,
            SUM(pm.remaining_amount_iqd) as remaining_amount_iqd
        FROM purchase_materials pm
        WHERE pm.person_id = ?
        GROUP BY pm.receipt_number, pm.purchase_date, pm.currency_type, pm.payment_type, pm.notes, pm.created_at
        ORDER BY pm.purchase_date DESC, pm.created_at DESC
    ");
    
    $stmt->execute([$person_id]);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedPurchases = [];
    foreach ($purchases as $purchase) {
        $formattedPurchases[] = [
            'receipt_number' => $purchase['receipt_number'],
            'purchase_date' => $purchase['purchase_date'],
            'currency_type' => $purchase['currency_type'],
            'payment_type' => $purchase['payment_type'],
            'total_price_usd' => (float)$purchase['total_price_usd'],
            'total_price_iqd' => (float)$purchase['total_price_iqd'],
            'paid_amount_usd' => (float)$purchase['paid_amount_usd'],
            'paid_amount_iqd' => (float)$purchase['paid_amount_iqd'],
            'remaining_amount_usd' => max(0, (float)$purchase['remaining_amount_usd']), // Ensure non-negative
            'remaining_amount_iqd' => max(0, (float)$purchase['remaining_amount_iqd']), // Ensure non-negative
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