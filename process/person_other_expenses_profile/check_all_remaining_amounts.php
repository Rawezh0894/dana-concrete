<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Get summary of all remaining amounts across all persons
    $stmt = $pdo->prepare("
        SELECT 
            pm.person_id,
            oep.name as person_name,
            COUNT(DISTINCT pm.receipt_number) as receipts_count,
            COUNT(pm.id) as items_count,
            SUM(pm.remaining_amount_usd) as total_stored_remaining_usd,
            SUM(pm.remaining_amount_iqd) as total_stored_remaining_iqd,
            SUM(pm.total_price_usd - pm.paid_amount_usd) as total_calculated_remaining_usd,
            SUM(pm.total_price_iqd - pm.paid_amount_iqd) as total_calculated_remaining_iqd,
            SUM(ABS((pm.total_price_usd - pm.paid_amount_usd) - pm.remaining_amount_usd)) as total_usd_difference,
            SUM(ABS((pm.total_price_iqd - pm.paid_amount_iqd) - pm.remaining_amount_iqd)) as total_iqd_difference
        FROM purchase_materials pm
        LEFT JOIN other_expense_persons oep ON pm.person_id = oep.id
        GROUP BY pm.person_id, oep.name
        HAVING total_usd_difference > 0.01 OR total_iqd_difference > 0.01
        ORDER BY total_usd_difference DESC, total_iqd_difference DESC
    ");
    
    $stmt->execute();
    $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate overall totals
    $overall_summary = [
        'total_persons_with_issues' => count($persons),
        'total_receipts_with_issues' => 0,
        'total_items_with_issues' => 0,
        'total_usd_difference' => 0,
        'total_iqd_difference' => 0
    ];
    
    foreach ($persons as $person) {
        $overall_summary['total_receipts_with_issues'] += $person['receipts_count'];
        $overall_summary['total_items_with_issues'] += $person['items_count'];
        $overall_summary['total_usd_difference'] += $person['total_usd_difference'];
        $overall_summary['total_iqd_difference'] += $person['total_iqd_difference'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'persons' => $persons,
            'overall_summary' => $overall_summary
        ]
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
