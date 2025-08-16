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
    $sql = "
        SELECT 
            pm.receipt_number,
            pm.purchase_date,
            pm.currency_type,
            pm.payment_type,
            pm.notes,
            pm.created_at,
            COUNT(pm.id) as materials_count,
            SUM(COALESCE(pm.total_price_usd, 0)) as total_price_usd,
            SUM(COALESCE(pm.total_price_iqd, 0)) as total_price_iqd,
            SUM(COALESCE(pm.paid_amount_usd, 0)) as paid_amount_usd,
            SUM(COALESCE(pm.paid_amount_iqd, 0)) as paid_amount_iqd,
            SUM(COALESCE(pm.remaining_amount_usd, 0)) as remaining_amount_usd,
            SUM(COALESCE(pm.remaining_amount_iqd, 0)) as remaining_amount_iqd
        FROM purchase_materials pm
        WHERE pm.person_id = ?
        GROUP BY pm.receipt_number, pm.purchase_date, pm.currency_type, pm.payment_type, pm.notes, pm.created_at
        ORDER BY pm.purchase_date DESC, pm.created_at DESC
    ";
    
    // Debug: Log the SQL query and parameters
    error_log("SQL Query: " . $sql);
    error_log("Parameters: person_id = $person_id");
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$person_id]);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the raw data to see what's happening
    error_log("Raw purchase data for person $person_id: " . json_encode($purchases));
    
    // Format the data
    $formattedPurchases = [];
    foreach ($purchases as $purchase) {
        // Debug: Log each purchase to see the values
        error_log("Processing purchase receipt {$purchase['receipt_number']}: total_price_usd = {$purchase['total_price_usd']}, materials_count = {$purchase['materials_count']}");
        
        // Debug: Log the raw purchase data
        error_log("Raw purchase data: " . json_encode($purchase));
        
        $formattedPurchases[] = [
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
    
    // Debug: Log the final formatted purchases
    error_log("Final formatted purchases: " . json_encode($formattedPurchases));
    
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