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

    $stmt = $pdo->prepare("SELECT IFNULL(SUM(remaining_usd), 0) AS rem_usd, IFNULL(SUM(remaining_iqd), 0) AS rem_iqd FROM other_expenses WHERE person_id=? AND payment_type='قەرز'");
    $stmt->execute([$person_id]);
    $rem_expenses = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT IFNULL(SUM(remaining_amount_usd), 0) AS rem_usd, IFNULL(SUM(remaining_amount_iqd), 0) AS rem_iqd FROM purchase_materials WHERE person_id=? AND payment_type='قەرز'");
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

