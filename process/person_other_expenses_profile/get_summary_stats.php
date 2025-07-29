<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $person_id = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;
    
    if (!$person_id) {
        throw new Exception('Person ID is required');
    }

    // Get total USD expenses
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount_usd), 0) as total_usd 
        FROM other_expenses 
        WHERE person_id = ?
    ");
    $stmt->execute([$person_id]);
    $total_usd = $stmt->fetch(PDO::FETCH_ASSOC)['total_usd'];

    // Get total IQD expenses
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount_iqd), 0) as total_iqd 
        FROM other_expenses 
        WHERE person_id = ?
    ");
    $stmt->execute([$person_id]);
    $total_iqd = $stmt->fetch(PDO::FETCH_ASSOC)['total_iqd'];

    // Get total expenses count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_count 
        FROM other_expenses 
        WHERE person_id = ?
    ");
    $stmt->execute([$person_id]);
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_count'];

    echo json_encode([
        'success' => true,
        'data' => [
            'total_usd' => floatval($total_usd),
            'total_iqd' => floatval($total_iqd),
            'total_count' => intval($total_count)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 