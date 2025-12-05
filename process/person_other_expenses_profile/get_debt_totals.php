<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/debt_helpers.php';

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
    $snapshot = getPersonDebtSnapshot($pdo, $person_id);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_debt_usd' => round($snapshot['total_debt_usd'], 2),
            'total_debt_iqd' => round($snapshot['total_debt_iqd'], 2)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
