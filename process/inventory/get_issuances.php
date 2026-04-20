<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT iss.*, i.name as item_name, c.name as car_name 
                         FROM inv_issuance iss 
                         JOIN inv_items i ON iss.item_id = i.id 
                         JOIN cars c ON iss.vehicle_id = c.id 
                         ORDER BY iss.issued_date DESC, iss.id DESC
                         LIMIT 50");
    $issuances = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $issuances]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
