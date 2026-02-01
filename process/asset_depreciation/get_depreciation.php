<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM asset_depreciation ORDER BY depreciation_date DESC");
    $data = $stmt->fetchAll();

    $total_iqd = 0;
    $total_usd = 0;

    foreach ($data as $row) {
        $total_iqd += $row['amount_iqd'];
        $total_usd += $row['amount_usd'];
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'totals' => [
            'iqd' => $total_iqd,
            'usd' => $total_usd
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}
?>
