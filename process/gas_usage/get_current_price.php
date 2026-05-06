<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT average_price, amount FROM bins_silos WHERE material_type = 'گاز' LIMIT 1");
    $stmt->execute();
    $gas = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($gas) {
        echo json_encode([
            'success' => true,
            'price' => $gas['average_price'],
            'available_amount' => $gas['amount']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'msg' => 'داتای گاز لە کۆگا نەدۆزرایەوە'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'msg' => 'هەڵە لە وەرگرتنی نرخی گاز: ' . $e->getMessage()
    ]);
}
