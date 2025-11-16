<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!hasPermission('view_person_other_expenses_profile')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}

$person_id = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;

if ($person_id <= 0) {
    echo json_encode(['success' => false, 'msg' => 'ناسێندرا']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT opening_debt_usd, opening_debt_iqd FROM other_expense_persons WHERE id=?");
    $stmt->execute([$person_id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$person) {
        echo json_encode(['success' => false, 'msg' => 'کەس نەدۆزرایەوە']);
        exit;
    }

    // Get remaining amounts from other_expenses (same as get_summary_stats.php - no payment_type filter)
    $stmt = $pdo->prepare("
        SELECT 
            IFNULL(SUM(remaining_usd), 0) AS rem_usd,
            IFNULL(SUM(remaining_iqd), 0) AS rem_iqd
        FROM other_expenses 
        WHERE person_id=?
    ");
    $stmt->execute([$person_id]);
    $rem_expenses = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get remaining amounts from purchase_materials (same calculation as get_summary_stats.php)
    // Use total_price - paid_amount instead of remaining_amount to match summary stats
    // No payment_type filter to match get_summary_stats.php
    $stmt = $pdo->prepare("
        SELECT 
            IFNULL(SUM(GREATEST(total_price_usd - paid_amount_usd, 0)), 0) AS rem_usd,
            IFNULL(SUM(GREATEST(total_price_iqd - paid_amount_iqd, 0)), 0) AS rem_iqd
        FROM purchase_materials 
        WHERE person_id=?
    ");
    $stmt->execute([$person_id]);
    $rem_purchases = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_usd = floatval($person['opening_debt_usd']) + floatval($rem_expenses['rem_usd']) + floatval($rem_purchases['rem_usd']);
    $total_iqd = floatval($person['opening_debt_iqd']) + floatval($rem_expenses['rem_iqd']) + floatval($rem_purchases['rem_iqd']);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_debt_usd' => round($total_usd, 2),
            'total_debt_iqd' => round($total_iqd, 2)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}

